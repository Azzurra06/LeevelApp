<?php
declare(strict_types=1);
// Matières suivies : notes, chapitres (niveau), quiz de chapitre, fichiers déposés, blocages signalés.

const CHAPTER_STATUS = ['todo' => 'À travailler', 'in_progress' => 'En cours', 'reinforce' => 'À renforcer', 'acquired' => 'Acquis'];
const CHAPTER_TONE = ['todo' => 'err', 'in_progress' => 'sage', 'reinforce' => 'warn', 'acquired' => 'ok'];
const FILE_MAX_BYTES = 10 * 1024 * 1024;
const FILE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/heic' => 'heic', 'image/heif' => 'heic', 'application/pdf' => 'pdf'];
/** Messagerie : photos, PDF et documents courants. */
const MSG_FILE_TYPES = FILE_TYPES + [
    'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'application/vnd.ms-powerpoint' => 'ppt', 'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'application/vnd.oasis.opendocument.text' => 'odt', 'application/vnd.oasis.opendocument.spreadsheet' => 'ods', 'text/plain' => 'txt',
    'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'video/mp4' => 'mp4', 'video/quicktime' => 'mov'];
const MSG_FILES_MAX = 5;

function subjects_available(): bool
{
    static $ok = null;
    if ($ok === null) { try { one('SELECT 1 FROM student_subjects LIMIT 1'); $ok = true; } catch (PDOException) { $ok = false; } }
    return $ok;
}

/** Matières d'un élève avec notes, moyenne et chapitres. */
function subjects_of(int $studentId): array
{
    if (!subjects_available()) return [];
    $out = [];
    foreach (all('SELECT * FROM student_subjects WHERE student_id=? ORDER BY position, id', [$studentId]) as $sub) {
        $sub['grades'] = all('SELECT g.*, u.role author_role FROM subject_grades g LEFT JOIN users u ON u.id=g.created_by WHERE g.subject_id=? ORDER BY g.graded_on, g.id', [$sub['id']]);
        $sub['avg'] = grades_average($sub['grades']);
        $sub['trend'] = grades_trend($sub['grades']);
        $sub['chapters'] = all('SELECT * FROM subject_chapters WHERE subject_id=? ORDER BY is_current DESC, FIELD(status,"in_progress","reinforce","todo","acquired"), id', [$sub['id']]);
        $sub['resources'] = subject_resources((int)$sub['id']);
        $sub['comments'] = subject_comments((int)$sub['id']);
        $out[] = $sub;
    }
    return $out;
}

/** Ressources d'une matière (liens, fichiers), les plus récentes d'abord. */
function subject_resources(int $subjectId, bool $sharedOnly = false): array
{
    try {
        return all('SELECT r.*, c.title chapter, f.original file_name, f.mime file_mime, f.size file_size FROM subject_resources r
                    LEFT JOIN subject_chapters c ON c.id=r.chapter_id LEFT JOIN student_files f ON f.id=r.file_id
                    WHERE r.subject_id=?' . ($sharedOnly ? ' AND r.shared=1' : '') . ' ORDER BY r.id DESC', [$subjectId]);
    } catch (PDOException) { return []; }
}
/** Fil de commentaires d'une matière, du plus ancien au plus récent. */
function subject_comments(int $subjectId, bool $sharedOnly = false): array
{
    try {
        return all('SELECT m.*, c.title chapter, u.display_name, u.role, u.avatar, u.id uid FROM subject_comments m
                    LEFT JOIN subject_chapters c ON c.id=m.chapter_id LEFT JOIN users u ON u.id=m.user_id
                    WHERE m.subject_id=?' . ($sharedOnly ? ' AND m.shared=1' : '') . ' ORDER BY m.created_at, m.id', [$subjectId]);
    } catch (PDOException) { return []; }
}
/** Ajoute une ressource (lien ou fichier) à une matière. Renvoie null si ajoutée, sinon l'erreur. */
function subject_resource_add(array $subject, array $user, int $studentId): ?string
{
    $kind = ($_POST['kind'] ?? 'link') === 'file' ? 'file' : 'link';
    $title = post_str('title', 160); $body = post_str('body', 2000);
    $chapter = post_int('chapter_id') ? one('SELECT id FROM subject_chapters WHERE id=? AND subject_id=?', [post_int('chapter_id'), $subject['id']]) : null;
    $shared = (int)!empty($_POST['shared']);
    $url = null; $fileId = null;
    if ($kind === 'link') {
        $url = trim(post_str('url', 500));
        if ($url !== '' && !preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
        if (!filter_var($url, FILTER_VALIDATE_URL)) return 'Adresse du lien invalide.';
        if ($title === '') $title = mb_substr((string)parse_url($url, PHP_URL_HOST), 0, 160);
    } else {
        $f = $_FILES['rfile'] ?? [];
        if ($err = upload_check($f, MSG_FILE_TYPES, 'photo, PDF, Word, Excel, PowerPoint, texte, audio ou vidéo')) return $err;
        $r = store_upload($f, MSG_FILE_TYPES);
        if (is_string($r)) return $r;
        [$stored, $original, $mime, $size] = $r;
        if ($title === '') $title = mb_substr(pathinfo($original, PATHINFO_FILENAME), 0, 160);
        q('INSERT INTO student_files (student_id, subject_id, user_id, stored, original, mime, size, note) VALUES (?,?,?,?,?,?,?,?)', [$studentId, $subject['id'], $user['id'], $stored, $original, $mime, $size, $title]);
        $fileId = (int)(one('SELECT id FROM student_files WHERE stored=?', [$stored])['id'] ?? 0) ?: null;
    }
    q('INSERT INTO subject_resources (subject_id, chapter_id, kind, title, url, file_id, body, shared, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
        [$subject['id'], $chapter['id'] ?? null, $kind, $title, $url, $fileId, $body ?: null, $shared, $user['id']]);
    return null;
}
/** Ajoute un commentaire au fil d'une matière. */
function subject_comment_add(array $subject, array $user, bool $shared = true): ?string
{
    $body = post_str('body', 2000);
    if ($body === '') return 'Écrivez un commentaire.';
    $chapter = post_int('chapter_id') ? one('SELECT id FROM subject_chapters WHERE id=? AND subject_id=?', [post_int('chapter_id'), $subject['id']]) : null;
    q('INSERT INTO subject_comments (subject_id, chapter_id, user_id, body, shared) VALUES (?,?,?,?,?)', [$subject['id'], $chapter['id'] ?? null, $user['id'], $body, (int)$shared]);
    return null;
}
/** Lien vers un fichier ou un lien de ressource, avec affichage plein écran pour les photos et PDF. */
function resource_link_attrs(array $r): string
{
    if ($r['kind'] === 'link') return 'href="' . e($r['url']) . '" target="_blank" rel="noopener noreferrer"';
    $u = url('file', ['id' => $r['file_id']]); $mime = (string)$r['file_mime'];
    $lb = in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true) ? 'img' : ($mime === 'application/pdf' ? 'pdf' : '');
    return 'href="' . e($u) . '"' . ($lb ? ' data-lightbox="' . $lb . '" data-dl="' . e($u) . '" data-name="' . e($r['file_name'] ?: $r['title']) . '"' : ' download="' . e($r['file_name'] ?: $r['title']) . '"');
}

/** Écart entre la moyenne actuelle et la moyenne de départ (null si l'une manque). */
function avg_delta(array $sub): ?float
{
    return $sub['avg'] !== null && ($sub['start_avg'] ?? null) !== null ? round($sub['avg'] - (float)$sub['start_avg'], 2) : null;
}
/** « Départ 9,5 → 12,13 (+2,63) » */
function start_avg_html(array $sub): string
{
    if (($sub['start_avg'] ?? null) === null) return '';
    $d = avg_delta($sub);
    return '<span class="start-avg">Départ <strong>' . fmt_grade((float)$sub['start_avg']) . '</strong>/20'
        . ($d !== null ? ' <span class="' . ($d > 0 ? 'up' : ($d < 0 ? 'down' : '')) . '">' . ($d > 0 ? '+' : '') . fmt_grade($d) . '</span>' : '') . '</span>';
}

/** Moyenne pondérée ramenée sur 20 (null sans note). */
function grades_average(array $grades): ?float
{
    $sum = 0.0; $w = 0.0;
    foreach ($grades as $g) { if ((float)$g['out_of'] <= 0) continue; $sum += (float)$g['value'] / (float)$g['out_of'] * 20 * (float)$g['coef']; $w += (float)$g['coef']; }
    return $w > 0 ? round($sum / $w, 2) : null;
}

/** Tendance : moyenne des 2 dernières notes comparée aux précédentes (+1 / 0 / -1). */
function grades_trend(array $grades): int
{
    if (count($grades) < 3) return 0;
    $last = grades_average(array_slice($grades, -2)); $before = grades_average(array_slice($grades, 0, -2));
    return $last - $before >= 1 ? 1 : ($before - $last >= 1 ? -1 : 0);
}

function fmt_grade(?float $v): string { return $v === null ? '-' : rtrim(rtrim(number_format($v, 2, ',', ''), '0'), ','); }

/** Petite courbe SVG des notes (sur 20). */
function grades_sparkline(array $grades, int $w = 240, int $h = 64): string
{
    $pts = array_map(fn($g) => (float)$g['out_of'] > 0 ? (float)$g['value'] / (float)$g['out_of'] * 20 : 0, $grades);
    if (count($pts) < 2) return '';
    $n = count($pts); $pad = 6;
    $xy = [];
    foreach ($pts as $i => $v) $xy[] = [round($pad + $i * ($w - 2 * $pad) / ($n - 1), 1), round($h - $pad - $v / 20 * ($h - 2 * $pad), 1)];
    $line = implode(' ', array_map(fn($p) => $p[0] . ',' . $p[1], $xy));
    $area = $pad . ',' . ($h - $pad) . ' ' . $line . ' ' . ($w - $pad) . ',' . ($h - $pad);
    $y10 = round($h - $pad - 10 / 20 * ($h - 2 * $pad), 1);
    $dots = implode('', array_map(fn($p) => '<circle cx="' . $p[0] . '" cy="' . $p[1] . '" r="3"/>', $xy));
    return '<svg class="spark" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" role="img" aria-label="Évolution des notes">'
        . '<line x1="' . $pad . '" x2="' . ($w - $pad) . '" y1="' . $y10 . '" y2="' . $y10 . '" class="spark-mid"/>'
        . '<polygon points="' . $area . '" class="spark-area"/><polyline points="' . $line . '" class="spark-line"/><g class="spark-dots">' . $dots . '</g></svg>';
}

/** Matière appartenant à l'élève (ou null). */
function subject_of_student(int $subjectId, int $studentId): ?array
{
    return subjects_available() ? one('SELECT * FROM student_subjects WHERE id=? AND student_id=?', [$subjectId, $studentId]) : null;
}
function chapter_of_student(int $chapterId, int $studentId): ?array
{
    return subjects_available() ? one('SELECT c.*, ss.name subject FROM subject_chapters c JOIN student_subjects ss ON ss.id=c.subject_id WHERE c.id=? AND ss.student_id=?', [$chapterId, $studentId]) : null;
}

function chapter_questions(int $chapterId): array
{
    return array_map(fn($q) => $q + ['options' => json_decode($q['options_json'], true) ?: []], all('SELECT * FROM chapter_questions WHERE chapter_id=? ORDER BY id', [$chapterId]));
}

/** Réponses saisies (opt[] + bonne réponse cochée) : [options, index] ou message d'erreur. */
function quiz_options_from_post(): array|string
{
    $raw = array_map(fn($x) => mb_substr(trim((string)$x), 0, 200), (array)($_POST['opt'] ?? []));
    $correct = $raw[post_int('answer')] ?? '';
    $opts = array_values(array_filter($raw, fn($x) => $x !== ''));
    if (post_str('question', 500) === '' || count($opts) < 2) return 'Une question et au moins deux réponses sont nécessaires.';
    $idx = $correct === '' ? false : array_search($correct, $opts, true);
    if ($idx === false) return 'Cochez la bonne réponse parmi les réponses remplies.';
    return [$opts, (int)$idx];
}

/** Corrige un quiz : renvoie [score, total, détail]. */
function quiz_grade(array $questions, array $answers): array
{
    $score = 0; $detail = [];
    foreach ($questions as $q) {
        $given = isset($answers[$q['id']]) && is_numeric($answers[$q['id']]) ? (int)$answers[$q['id']] : null;
        $ok = $given !== null && $given === (int)$q['answer'];
        if ($ok) $score++;
        $detail[$q['id']] = ['given' => $given, 'ok' => $ok];
    }
    return [$score, count($questions), $detail];
}

/** Ajout d'une note (contrôle commun tutrice / élève / parent). */
function grade_add_from_post(array $subject, array $user): ?string
{
    $raw = str_replace(',', '.', post_str('value', 10)); $outRaw = str_replace(',', '.', post_str('out_of', 10) ?: '20');
    $coefRaw = str_replace(',', '.', post_str('coef', 6) ?: '1');
    if (!is_numeric($raw) || !is_numeric($outRaw) || (float)$outRaw <= 0 || (float)$outRaw > 100 || (float)$raw < 0 || (float)$raw > (float)$outRaw) return 'Note invalide.';
    if (!is_numeric($coefRaw) || (float)$coefRaw <= 0 || (float)$coefRaw > 20) return 'Coefficient invalide.';
    $date = post_str('graded_on', 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) > time() + 86400) $date = date('Y-m-d');
    q('INSERT INTO subject_grades (subject_id, value, out_of, coef, label, graded_on, created_by) VALUES (?,?,?,?,?,?,?)',
        [$subject['id'], round((float)$raw, 2), round((float)$outRaw, 2), round((float)$coefRaw, 2), post_str('label', 120) ?: null, $date, $user['id']]);
    return null;
}

// ---------- Fichiers ----------
function files_dir(): string
{
    $dir = APP_ROOT . '/storage/files';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
        if (!is_file(APP_ROOT . '/storage/.htaccess')) file_put_contents(APP_ROOT . '/storage/.htaccess', "Require all denied\nDeny from all\n");
    }
    return $dir;
}

/** Vérifie un fichier déposé : null si valide, sinon le message d'erreur. */
function upload_check(array $file, array $types, string $formats): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return in_array($file['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'Fichier trop lourd (10 Mo maximum).' : 'Aucun fichier reçu.';
    if ($file['size'] > FILE_MAX_BYTES) return 'Fichier trop lourd (10 Mo maximum).';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    return isset($types[$mime]) ? null : 'Format non accepté (' . $formats . ').';
}
/** Enregistre un fichier vérifié ; les photos sont réduites (1800 px) et réencodées en JPEG (métadonnées supprimées).
 *  Renvoie [stored, original, mime, size] ou un message d'erreur. */
function store_upload(array $file, array $types): array|string
{
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $base = bin2hex(random_bytes(12));
    $original = mb_substr(preg_replace('/[^\p{L}\p{N} ._()-]/u', '_', basename((string)$file['name'])) ?: 'fichier', 0, 160);
    $stored = null;
    if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) && function_exists('imagecreatefromstring')) {
        $info = @getimagesize($file['tmp_name']);
        $src = $info && $info[0] * $info[1] <= 60_000_000 ? @imagecreatefromstring((string)file_get_contents($file['tmp_name'])) : false;
        if ($src) {
            $w = imagesx($src); $h = imagesy($src); $k = min(1, 1800 / max($w, $h));
            $dst = imagecreatetruecolor(max(1, (int)round($w * $k)), max(1, (int)round($h * $k)));
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
            if (imagejpeg($dst, files_dir() . "/$base.jpg", 82)) { $stored = "$base.jpg"; $mime = 'image/jpeg'; }
            imagedestroy($src); imagedestroy($dst);
        }
    }
    if (!$stored) {
        $stored = $base . '.' . $types[$mime];
        if (!move_uploaded_file($file['tmp_name'], files_dir() . '/' . $stored)) return 'Enregistrement impossible.';
    }
    return [$stored, $original, $mime, (int)filesize(files_dir() . '/' . $stored)];
}
function save_student_file(int $studentId, ?int $subjectId, array $user, array $file, string $note): ?string
{
    if ($err = upload_check($file, FILE_TYPES, 'photo JPEG, PNG, WebP, HEIC ou PDF')) return $err;
    $r = store_upload($file, FILE_TYPES);
    if (is_string($r)) return $r;
    [$stored, $original, $mime, $size] = $r;
    q('INSERT INTO student_files (student_id, subject_id, user_id, stored, original, mime, size, note) VALUES (?,?,?,?,?,?,?,?)',
        [$studentId, $subjectId, $user['id'], $stored, $original, $mime, $size, $note ?: null]);
    return null;
}

function delete_student_file(array $f): void
{
    if (preg_match('/^[a-f0-9]{24}\.[a-z]{3,4}$/', $f['stored'])) @unlink(files_dir() . '/' . $f['stored']);
    q('DELETE FROM student_files WHERE id=?', [$f['id']]);
}

/** Sert un fichier si le demandeur a accès au dossier de l'élève. */
function serve_student_file(array $viewer, int $fileId): never
{
    $f = subjects_available() ? one('SELECT * FROM student_files WHERE id=?', [$fileId]) : null;
    $ok = $f && one('SELECT 1 FROM students s JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=? WHERE s.id=?', [$viewer['id'], $f['student_id']]);
    if ($ok && $viewer['role'] === 'student') $ok = (bool)one('SELECT 1 FROM students WHERE id=? AND user_id=?', [$f['student_id'], $viewer['id']]);
    $path = $ok && preg_match('/^[a-f0-9]{24}\.[a-z]{3,4}$/', $f['stored']) ? files_dir() . '/' . $f['stored'] : null;
    if (!$path || !is_file($path)) { http_response_code(404); exit('Fichier introuvable.'); }
    header_remove('Cache-Control');
    header('Content-Type: ' . $f['mime']);
    header('X-Content-Type-Options: nosniff');
    header('Content-Security-Policy: default-src \'none\'; img-src \'self\'; style-src \'unsafe-inline\'; sandbox');
    header('Content-Disposition: ' . (str_starts_with($f['mime'], 'image/') || $f['mime'] === 'application/pdf' ? 'inline' : 'attachment') . '; filename="' . rawurlencode($f['original']) . '"');
    header('Cache-Control: private, max-age=3600');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

function student_files(int $studentId, ?int $subjectId = null, int $limit = 24): array
{
    if (!subjects_available()) return [];
    $sql = 'SELECT f.*, u.display_name author, u.role author_role, ss.name subject FROM student_files f LEFT JOIN users u ON u.id=f.user_id LEFT JOIN student_subjects ss ON ss.id=f.subject_id
            WHERE f.student_id=?' . ($subjectId ? ' AND f.subject_id=' . (int)$subjectId : '');
    // les fichiers joints comme « ressource » d'une matière sont affichés dans les ressources, pas ici
    try { return all($sql . ' AND f.id NOT IN (SELECT file_id FROM subject_resources WHERE file_id IS NOT NULL) ORDER BY f.id DESC LIMIT ' . (int)$limit, [$studentId]); }
    catch (PDOException) { return all($sql . ' ORDER BY f.id DESC LIMIT ' . (int)$limit, [$studentId]); }
}

function student_blockers(int $studentId, ?int $subjectId = null, int $limit = 10): array
{
    if (!subjects_available()) return [];
    return all('SELECT b.*, ss.name subject, c.title chapter FROM subject_blockers b LEFT JOIN student_subjects ss ON ss.id=b.subject_id LEFT JOIN subject_chapters c ON c.id=b.chapter_id
                WHERE b.student_id=?' . ($subjectId ? ' AND b.subject_id=' . (int)$subjectId : '') . ' ORDER BY b.id DESC LIMIT ' . (int)$limit, [$studentId]);
}

function blockers_unseen(int $studentId): int
{
    if (!subjects_available()) return 0;
    return (int)(one('SELECT COUNT(*) n FROM subject_blockers WHERE student_id=? AND seen_at IS NULL', [$studentId])['n'] ?? 0);
}

/** Dernière séance passée, avec bilan et ressenti de l'élève. */
function last_session_recap(int $studentId): ?array
{
    try {
        $se = one('SELECT * FROM sessions WHERE student_id=? AND status IN ("done","planned") AND starts_at < NOW() ORDER BY starts_at DESC LIMIT 1', [$studentId]);
        if (!$se) return null;
        $se['report'] = one('SELECT * FROM session_reports WHERE session_id=?', [$se['id']]);
        $se['feedback'] = one('SELECT * FROM session_feedback WHERE session_id=?', [$se['id']]);
        return $se;
    } catch (PDOException) { return null; }
}

/** Suggestions de matières à suivre (difficultés signalées dans les questionnaires). */
function subject_suggestions(int $studentId): array
{
    $lv = (require APP_ROOT . '/app/questionnaires/_levels.php')['subjects'];
    $keys = [];
    foreach (['student' => ['s_hard_subjects', 's_least_subjects'], 'parent' => ['p_averages']] as $code => $qkeys) {
        $sub = qn_latest($code, $studentId);
        if (!$sub) continue;
        $a = qn_answers((int)$sub['id']);
        foreach ($qkeys as $k) foreach ((array)($a[$k] ?? []) as $v) {
            if (is_array($v)) { if (!empty($v['subject']) && ($v['avg'] === null || $v['avg'] < 12)) $keys[] = $v['subject']; }
            elseif (is_string($v)) $keys[] = $v;
        }
    }
    $have = array_column(subjects_of($studentId), 'name');
    $names = array_values(array_unique(array_filter(array_map(fn($k) => $lv[$k][0] ?? null, $keys))));
    return array_values(array_diff($names, $have));
}


// ---------- Devoirs et révisions ----------
const STUDY_KINDS = ['devoir' => 'Devoir', 'revision' => 'Révision'];
function study_tasks(int $studentId): array
{
    try {
        return all('SELECT t.*, ss.name subject, u.display_name author, u.role author_role FROM study_tasks t
                    LEFT JOIN student_subjects ss ON ss.id=t.subject_id LEFT JOIN users u ON u.id=t.created_by
                    WHERE t.student_id=? ORDER BY t.due_date, t.id', [$studentId]);
    } catch (PDOException) { return []; }
}
/** Élève ou tutrice de l'élève : peut modifier le planning ; parent : lecture seule. */
function study_student_for(array $u, int $sid): ?array
{
    if ($u['role'] === 'student') return one('SELECT * FROM students WHERE id=? AND user_id=?', [$sid, $u['id']]);
    if ($u['role'] === 'tutor') return one('SELECT s.* FROM students s JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=? AND fm.member_role="tutor" WHERE s.id=?', [$u['id'], $sid]);
    return null;
}
function study_post(array $u, array $s): string
{
    $act = (string)($_POST['action'] ?? '');
    $t = post_int('task_id') ? one('SELECT * FROM study_tasks WHERE id=? AND student_id=?', [post_int('task_id'), $s['id']]) : null;
    if ($act === 'toggle' && $t) { q('UPDATE study_tasks SET done_at=' . ($t['done_at'] ? 'NULL' : 'NOW()') . ' WHERE id=?', [$t['id']]); return $t['done_at'] ? 'Remis à faire.' : 'Bravo, c\'est fait !'; }
    if ($act === 'delete' && $t) { q('DELETE FROM study_tasks WHERE id=?', [$t['id']]); return 'Supprimé.'; }
    if ($act === 'save') {
        $title = post_str('title', 200); $date = post_str('due_date', 10);
        if ($title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return '!Indiquez au moins un intitulé et une date.';
        $kind = post_enum('kind', STUDY_KINDS, 'devoir');
        $subj = post_int('subject_id') ? one('SELECT id FROM student_subjects WHERE id=? AND student_id=?', [post_int('subject_id'), $s['id']]) : null;
        if ($t) q('UPDATE study_tasks SET kind=?, title=?, details=?, due_date=?, subject_id=? WHERE id=?', [$kind, $title, post_str('details', 2000) ?: null, $date, $subj['id'] ?? null, $t['id']]);
        else q('INSERT INTO study_tasks (student_id, subject_id, kind, title, details, due_date, created_by) VALUES (?,?,?,?,?,?,?)', [$s['id'], $subj['id'] ?? null, $kind, $title, post_str('details', 2000) ?: null, $date, $u['id']]);
        return $t ? 'Modifié.' : 'Ajouté au planning.';
    }
    return '';
}
