<?php
declare(strict_types=1);
// Moteur commun des questionnaires : configuration, sauvegarde, conditions, validation.

const SKIP = '__skip';

function qn_config(string $code): array
{
    static $cache = [];
    if (!in_array($code, ['parent', 'student'], true)) throw new InvalidArgumentException('questionnaire inconnu');
    return $cache[$code] ??= require APP_ROOT . "/app/questionnaires/$code.php";
}
function qn_questions(array $cfg): array
{
    $out = [];
    foreach ($cfg['steps'] as $i => $st) foreach ($st['questions'] as $qq) $out[$qq['key']] = $qq + ['step' => $i];
    return $out;
}
function qn_id(string $code): int
{
    $r = one('SELECT id FROM questionnaires WHERE code=?', [$code]);
    if (!$r) throw new RuntimeException('Questionnaire non installé');
    return (int)$r['id'];
}

/** Soumission existante ou nouvelle (brouillon). */
function qn_submission(string $code, int $studentId, int $userId, bool $create = true): ?array
{
    $qid = qn_id($code);
    $s = one('SELECT * FROM questionnaire_submissions WHERE questionnaire_id=? AND student_id=? AND user_id=?', [$qid, $studentId, $userId]);
    if (!$s && $create) {
        q('INSERT INTO questionnaire_submissions (questionnaire_id, student_id, user_id) VALUES (?,?,?)', [$qid, $studentId, $userId]);
        $s = one('SELECT * FROM questionnaire_submissions WHERE id=?', [db()->lastInsertId()]);
    }
    return $s;
}
/** Dernière soumission d'un questionnaire pour un élève (tous répondants confondus). */
function qn_latest(string $code, int $studentId): ?array
{
    return one('SELECT qs.*, u.display_name FROM questionnaire_submissions qs JOIN questionnaires qn ON qn.id=qs.questionnaire_id
                JOIN users u ON u.id=qs.user_id WHERE qn.code=? AND qs.student_id=? ORDER BY qs.status="completed" DESC, qs.updated_at DESC LIMIT 1', [$code, $studentId]);
}
function qn_answers(int $submissionId): array
{
    $out = [];
    foreach (all('SELECT qkey, value_json FROM questionnaire_responses WHERE submission_id=?', [$submissionId]) as $r) {
        $out[$r['qkey']] = json_decode($r['value_json'], true);
    }
    return $out;
}

function qn_visible(array $question, array $answers): bool
{
    if (empty($question['show_if'])) return true;
    $v = $answers[$question['show_if']['key']] ?? null;
    $vals = is_array($v) ? $v : [$v];
    return (bool)array_intersect($vals, $question['show_if']['in']);
}

/** Nettoie une valeur selon le type de question ; null = vide (supprimer). */
function qn_clean(array $question, mixed $v): mixed
{
    if ($v === SKIP && !empty($question['sensitive'])) return SKIP;
    switch ($question['type']) {
        case 'single':
        case 'select':
            if (is_int($v)) $v = (string)$v;
            return is_string($v) && isset($question['options'][$v]) ? $v : null;
        case 'multi_prio':
            // Tableau ordonné : le premier élément est la priorité. Une ancienne réponse simple devient [valeur].
            if (is_string($v)) $v = [$v];
            if (!is_array($v)) return null;
            $v = array_values(array_unique(array_filter($v, fn($x) => is_string($x) && isset($question['options'][$x]))));
            return $v ?: null;
        case 'multi':
            if (is_string($v)) $v = [$v]; // ancienne réponse à choix unique
            if (!is_array($v)) return null;
            $v = array_values(array_unique(array_filter($v, fn($x) => is_string($x) && isset($question['options'][$x]))));
            return $v ?: null;
        case 'scale':
            $n = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
            return $n === false ? null : $n;
        case 'grades':
            // Lignes { matière, moyenne /20, commentaire } ; une ancienne réponse en texte libre est conservée
            if (is_string($v)) { $v = trim(mb_substr($v, 0, 2000)); return $v === '' ? null : $v; }
            if (!is_array($v)) return null;
            $rows = [];
            foreach ($v as $row) {
                if (!is_array($row)) continue;
                $subject = isset($question['options'][$row['subject'] ?? '']) ? $row['subject'] : '';
                $avgRaw = str_replace(',', '.', trim((string)($row['avg'] ?? '')));
                $avg = $avgRaw !== '' && is_numeric($avgRaw) && (float)$avgRaw >= 0 && (float)$avgRaw <= 20 ? round((float)$avgRaw, 2) : null;
                $comment = trim(mb_substr((string)($row['comment'] ?? ''), 0, 200));
                if ($subject === '' && $avg === null && $comment === '') continue;
                $rows[] = ['subject' => $subject, 'avg' => $avg, 'comment' => $comment];
                if (count($rows) >= 15) break;
            }
            return $rows ?: null;
        case 'text':
        case 'textarea':
            if (!is_string($v)) return null;
            $v = trim(mb_substr($v, 0, $question['type'] === 'text' ? 200 : 2000));
            return $v === '' ? null : $v;
    }
    return null;
}

/** Enregistre les réponses fournies (clé => valeur). Ne touche pas aux autres clés. */
function qn_save(array $submission, string $code, array $input, ?int $step = null): void
{
    $questions = qn_questions(qn_config($code));
    $pdo = db();
    $pdo->beginTransaction();
    foreach ($input as $key => $val) {
        if (!is_string($key) || !isset($questions[$key])) continue;
        $clean = qn_clean($questions[$key], $val);
        if ($clean === null) {
            q('DELETE FROM questionnaire_responses WHERE submission_id=? AND qkey=?', [$submission['id'], $key]);
        } else {
            q('INSERT INTO questionnaire_responses (submission_id, qkey, value_json) VALUES (?,?,?)
               ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)', [$submission['id'], $key, json_encode($clean, JSON_UNESCAPED_UNICODE)]);
        }
    }
    if ($step !== null) {
        $max = count(qn_config($code)['steps']);
        q('UPDATE questionnaire_submissions SET current_step=?, updated_at=NOW() WHERE id=?', [max(0, min($step, $max)), $submission['id']]);
    } else {
        q('UPDATE questionnaire_submissions SET updated_at=NOW() WHERE id=?', [$submission['id']]);
    }
    $pdo->commit();
}

function qn_complete(array $submission): void
{
    q('UPDATE questionnaire_submissions SET status="completed", completed_at=COALESCE(completed_at, NOW()) WHERE id=?', [$submission['id']]);
}

/** Reporte prénom, classe et établissement du questionnaire élève dans le dossier. */
function student_profile_sync(int $studentId, array $a): void
{
    if (isset($a['s_class']) && is_string($a['s_class']) && isset(CLASSES[$a['s_class']])) q('UPDATE students SET grade=? WHERE id=?', [$a['s_class'], $studentId]);
    if (array_key_exists('s_school', $a) && is_string($a['s_school'])) q('UPDATE students SET school=? WHERE id=?', [trim(mb_substr($a['s_school'], 0, 120)) ?: null, $studentId]);
    if (isset($a['s_first_name']) && is_string($a['s_first_name']) && trim($a['s_first_name']) !== '') q('UPDATE students SET first_name=? WHERE id=?', [trim(mb_substr($a['s_first_name'], 0, 80)), $studentId]);
}
/** Réponses élève complétées par le dossier (affichage seulement : prénom, classe, établissement). */
function student_profile_defaults(array $s, array $answers): array
{
    return $answers + array_filter(['s_first_name' => $s['first_name'] ?? null, 's_class' => $s['grade'] ?? null, 's_school' => $s['school'] ?? null], fn($x) => $x !== null && $x !== '');
}

/** Valeur lisible d'une réponse. */
function qn_display(array $question, mixed $v): string
{
    if ($v === null) return '-';
    if ($v === SKIP) return 'Préfère ne pas répondre';
    return match ($question['type']) {
        'single', 'select' => $question['options'][$v] ?? (string)$v,
        'multi_prio' => implode(', ', array_map(fn($x, $i) => ($question['options'][$x] ?? $x) . ($i === 0 ? ' (priorité)' : ''), (array)$v, array_keys((array)$v))),
        'multi' => implode(', ', array_map(fn($x) => $question['options'][$x] ?? $x, (array)$v)),
        'scale' => "$v / 5 (" . ($question['min'] ?? '1') . ' → ' . ($question['max'] ?? '5') . ')',
        'grades' => is_array($v) ? implode(' ; ', array_map(fn($r) => trim(($question['options'][$r['subject']] ?? 'Matière ?') . ($r['avg'] !== null ? ' : ' . str_replace('.', ',', (string)$r['avg']) . '/20' : '') . ($r['comment'] !== '' ? ' (' . $r['comment'] . ')' : '')), $v)) : (string)$v,
        default => (string)$v,
    };
}

/** Taux de complétion (questions visibles répondues). */
function qn_progress(string $code, array $answers): array
{
    $total = 0; $done = 0;
    foreach (qn_questions(qn_config($code)) as $k => $qq) {
        if (!qn_visible($qq, $answers)) continue;
        $total++;
        if (array_key_exists($k, $answers)) $done++;
    }
    return [$done, $total];
}

/** Synchronise la copie de référence des questions en base (installation). */
function qn_sync_db(): void
{
    foreach (['parent', 'student'] as $code) {
        $cfg = qn_config($code);
        q('INSERT INTO questionnaires (code, title, respondent_role, version) VALUES (?,?,?,?)
           ON DUPLICATE KEY UPDATE title=VALUES(title), version=VALUES(version)', [$code, $cfg['title'], $cfg['role'], $cfg['version']]);
        $qid = qn_id($code);
        $pos = 0;
        foreach (qn_questions($cfg) as $k => $qq) {
            q('INSERT INTO questionnaire_questions (questionnaire_id, qkey, step_no, qtype, label, options_json, condition_json, is_sensitive, position)
               VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE step_no=VALUES(step_no), qtype=VALUES(qtype), label=VALUES(label),
               options_json=VALUES(options_json), condition_json=VALUES(condition_json), is_sensitive=VALUES(is_sensitive), position=VALUES(position)',
                [$qid, $k, $qq['step'], $qq['type'], $qq['label'], isset($qq['options']) ? json_encode($qq['options'], JSON_UNESCAPED_UNICODE) : null,
                 isset($qq['show_if']) ? json_encode($qq['show_if']) : null, (int)!empty($qq['sensitive']), $pos++]);
        }
    }
}
