<?php
declare(strict_types=1);
// Suivi : bilans de séance, retours élève, contrôles à venir, bilans mensuels, objectifs de l'année.

const TEST_KINDS = ['controle' => 'Contrôle', 'devoir' => 'Devoir maison', 'oral' => 'Oral', 'examen_blanc' => 'Brevet / bac blanc', 'examen' => 'Examen', 'autre' => 'Autre'];
const MOODS = ['good' => 'Bien', 'ok' => 'Moyen', 'hard' => 'Difficile'];
const YEAR_STATUS = ['todo' => 'Pas commencé', 'in_progress' => 'En route', 'rework' => 'En route', 'acquired' => 'Atteint'];

function tests_upcoming(int $studentId, int $limit = 8): array
{
    try {
        return all('SELECT t.*, u.display_name author, u.role author_role FROM upcoming_tests t LEFT JOIN users u ON u.id=t.created_by
                    WHERE t.student_id=? AND t.test_date >= CURDATE() ORDER BY t.test_date LIMIT ' . (int)$limit, [$studentId]);
    } catch (PDOException) { return []; }
}

/** Ajout / suppression d'un contrôle (tutrice, parent ou élève du dossier). */
function handle_test_action(array $u): never
{
    $s = student_for($u, post_int('sid'));
    $back = ['tutor' => 't', 'parent' => 'p', 'student' => 's'][$u['role']];
    if (!empty($_POST['delete_id'])) {
        $t = one('SELECT * FROM upcoming_tests WHERE id=? AND student_id=?', [post_int('delete_id'), $s['id']]);
        if ($t && ($u['role'] === 'tutor' || (int)$t['created_by'] === (int)$u['id'])) { q('DELETE FROM upcoming_tests WHERE id=?', [$t['id']]); flash('Contrôle retiré.'); }
        redirect($back, ['sid' => $s['id']]);
    }
    $date = post_str('test_date', 10);
    $d = DateTime::createFromFormat('Y-m-d', $date);
    $subject = post_str('subject', 80);
    if (!$d || $d->format('Y-m-d') !== $date || $date < date('Y-m-d', strtotime('-1 day'))) flash('Choisissez une date à venir.', 'err');
    elseif ($subject === '') flash('Indiquez la matière.', 'err');
    else {
        q('INSERT INTO upcoming_tests (student_id, test_date, subject, kind, title, created_by) VALUES (?,?,?,?,?,?)',
            [$s['id'], $date, $subject, post_enum('kind', TEST_KINDS, 'controle'), post_str('title', 160) ?: null, $u['id']]);
        flash('Contrôle ajouté.');
    }
    redirect($back, ['sid' => $s['id']]);
}

function session_report(int $sessionId): ?array
{
    try { return one('SELECT * FROM session_reports WHERE session_id=?', [$sessionId]); } catch (PDOException) { return null; }
}
function session_feedback_of(int $sessionId): ?array
{
    try { return one('SELECT * FROM session_feedback WHERE session_id=?', [$sessionId]); } catch (PDOException) { return null; }
}

/** Séances passées (non annulées) sans bilan de la tutrice. */
function sessions_missing_report(int $studentId): array
{
    try {
        return all('SELECT s.* FROM sessions s LEFT JOIN session_reports r ON r.session_id=s.id
                    WHERE s.student_id=? AND s.status IN ("done","planned") AND s.starts_at < NOW() AND (r.session_id IS NULL OR (r.worked_on IS NULL AND r.parent_summary IS NULL))
                    ORDER BY s.starts_at DESC LIMIT 10', [$studentId]);
    } catch (PDOException) { return []; }
}

/** Dernière séance passée sans retour de l'élève (dans les 14 derniers jours). */
function session_awaiting_feedback(int $studentId): ?array
{
    try {
        return one('SELECT s.* FROM sessions s LEFT JOIN session_feedback f ON f.session_id=s.id
                    WHERE s.student_id=? AND s.status IN ("done","planned") AND s.starts_at < NOW() AND s.starts_at > NOW() - INTERVAL 14 DAY AND f.session_id IS NULL
                    ORDER BY s.starts_at DESC LIMIT 1', [$studentId]);
    } catch (PDOException) { return null; }
}

/** Bilans de séance envoyés au parent. */
function sent_reports(int $studentId, int $limit = 3): array
{
    try {
        return all('SELECT r.*, s.starts_at, s.title FROM session_reports r JOIN sessions s ON s.id=r.session_id
                    WHERE s.student_id=? AND r.sent_at IS NOT NULL ORDER BY s.starts_at DESC LIMIT ' . (int)$limit, [$studentId]);
    } catch (PDOException) { return []; }
}

/** Éléments automatiques d'un mois (à partir des séances et de leurs bilans). */
function month_overview(int $studentId, string $month): array
{
    $start = $month . '-01'; $end = date('Y-m-d', strtotime($start . ' +1 month'));
    $sessions = all('SELECT * FROM sessions WHERE student_id=? AND starts_at >= ? AND starts_at < ? ORDER BY starts_at', [$studentId, $start, $end]);
    $done = array_values(array_filter($sessions, fn($x) => $x['status'] === 'done' || ($x['status'] === 'planned' && strtotime($x['starts_at']) < time())));
    $reports = []; $moods = ['good' => 0, 'ok' => 0, 'hard' => 0]; $toDeepen = [];
    foreach ($done as $se) {
        $r = session_report((int)$se['id']); $f = session_feedback_of((int)$se['id']);
        if ($r && ($r['worked_on'] || $r['parent_summary'])) $reports[] = ['date' => $se['starts_at'], 'worked_on' => $r['worked_on'], 'summary' => $r['parent_summary']];
        if ($f && $f['mood']) $moods[$f['mood']]++;
        if ($f && $f['to_deepen']) $toDeepen[] = $f['to_deepen'];
    }
    $goals = all('SELECT title, status, progress FROM goals WHERE student_id=? ORDER BY horizon DESC, status', [$studentId]);
    return [
        'count' => count($done), 'minutes' => array_sum(array_map(fn($x) => (int)$x['duration_min'], $done)),
        'cancelled' => count(array_filter($sessions, fn($x) => in_array($x['status'], ['cancelled', 'postponed'], true))),
        'reports' => $reports, 'moods' => $moods, 'to_deepen' => $toDeepen,
        'acquired' => array_values(array_filter($goals, fn($g) => $g['status'] === 'acquired')),
        'in_progress' => array_values(array_filter($goals, fn($g) => in_array($g['status'], ['in_progress', 'rework'], true))),
    ];
}

/** Brouillon de bilan mensuel proposé à la tutrice. */
function month_draft(array $s, array $ov, string $month): string
{
    $names = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $label = $names[(int)substr($month, 5, 2)] . ' ' . substr($month, 0, 4);
    $t = "En $label, {$s['first_name']} a suivi {$ov['count']} séance(s) (" . fdur($ov['minutes']) . ").\n\n";
    if ($ov['reports']) {
        $t .= "Ce que nous avons travaillé :\n";
        foreach ($ov['reports'] as $r) if ($r['worked_on']) $t .= '- ' . preg_replace('/\s+/', ' ', $r['worked_on']) . "\n";
        $t .= "\n";
    }
    if ($ov['acquired']) $t .= 'Acquis ce mois-ci : ' . implode(', ', array_column($ov['acquired'], 'title')) . ".\n";
    if ($ov['in_progress']) $t .= 'En cours : ' . implode(', ', array_column($ov['in_progress'], 'title')) . ".\n";
    if ($ov['to_deepen']) $t .= "\nPoints à approfondir (selon {$s['first_name']}) : " . implode(' ; ', array_map(fn($x) => preg_replace('/\s+/', ' ', $x), $ov['to_deepen'])) . ".\n";
    return trim($t) . "\n\nPour le mois prochain : ";
}

function monthly_published(int $studentId, int $limit = 1): array
{
    try { return all('SELECT * FROM monthly_reports WHERE student_id=? AND published_at IS NOT NULL ORDER BY month DESC LIMIT ' . (int)$limit, [$studentId]); }
    catch (PDOException) { return []; }
}

function year_goals(int $studentId): array
{
    try { return all('SELECT * FROM goals WHERE student_id=? AND horizon="year" ORDER BY FIELD(status,"in_progress","rework","todo","acquired"), id', [$studentId]); }
    catch (PDOException) { return []; }
}

function month_label(string $month): string
{
    $names = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return $names[(int)substr($month, 5, 2)] . ' ' . substr($month, 0, 4);
}

/** Libellé lisible d'une entrée du journal de l'élève (type + valeur). */
function entry_label(array $h): string
{
    $type = ['help_request' => "Demande d'aide", 'self_eval' => 'Autoévaluation', 'task_done' => 'Tâche terminée', 'feeling' => 'Humeur de la semaine'][$h['entry_type']] ?? $h['entry_type'];
    $moods = ['good' => '😄 Ça va bien', 'ok' => '😐 Moyen', 'hard' => '😣 C\'est dur'];
    $val = $h['value'] ? ($h['entry_type'] === 'feeling' ? ($moods[$h['value']] ?? $h['value']) : (FEELINGS[$h['value']] ?? $moods[$h['value']] ?? $h['value'])) : '';
    return $type . ($val !== '' ? ' · ' . $val : '');
}
function entry_icon(string $type): string
{
    return ['help_request' => 'circle-help', 'self_eval' => 'check', 'task_done' => 'flag', 'feeling' => 'smile'][$type] ?? 'check';
}
