<?php
declare(strict_types=1);
// Matières suivies : notes, chapitres (niveau), quiz de chapitre, fichiers déposés, blocages signalés.

const CHAPTER_STATUS = ['todo' => 'À travailler', 'in_progress' => 'En cours', 'reinforce' => 'À renforcer', 'acquired' => 'Acquis'];
const CHAPTER_TONE = ['todo' => 'err', 'in_progress' => 'sage', 'reinforce' => 'warn', 'acquired' => 'ok'];
const FILE_MAX_BYTES = 10 * 1024 * 1024;
const FILE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/heic' => 'heic', 'image/heif' => 'heic', 'application/pdf' => 'pdf'];

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
        $out[] = $sub;
    }
    return $out;
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

/** Enregistre un fichier déposé ; les photos sont réduites (1800 px) et réencodées en JPEG (métadonnées supprimées). */
function save_student_file(int $studentId, ?int $subjectId, array $user, array $file, string $note): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return ($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE ? 'Fichier trop lourd.' : 'Aucun fichier reçu.';
    if ($file['size'] > FILE_MAX_BYTES) return 'Fichier trop lourd (10 Mo maximum).';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!isset(FILE_TYPES[$mime])) return 'Format accepté : photo (JPEG, PNG, WebP, HEIC) ou PDF.';
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
        $stored = $base . '.' . FILE_TYPES[$mime];
        if (!move_uploaded_file($file['tmp_name'], files_dir() . '/' . $stored)) return 'Enregistrement impossible.';
    }
    q('INSERT INTO student_files (student_id, subject_id, user_id, stored, original, mime, size, note) VALUES (?,?,?,?,?,?,?,?)',
        [$studentId, $subjectId, $user['id'], $stored, $original, $mime, (int)filesize(files_dir() . '/' . $stored), $note ?: null]);
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
    return all('SELECT f.*, u.display_name author, u.role author_role, ss.name subject FROM student_files f LEFT JOIN users u ON u.id=f.user_id LEFT JOIN student_subjects ss ON ss.id=f.subject_id
                WHERE f.student_id=?' . ($subjectId ? ' AND f.subject_id=' . (int)$subjectId : '') . ' ORDER BY f.id DESC LIMIT ' . (int)$limit, [$studentId]);
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
