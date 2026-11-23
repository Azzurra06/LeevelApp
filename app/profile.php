<?php
declare(strict_types=1);
// Profils (coordonnées, photo) et messagerie interne famille <-> tutrice.

const AVATAR_MAX_BYTES = 4 * 1024 * 1024;

function avatar_dir(): string
{
    $dir = APP_ROOT . '/storage/avatars';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
        file_put_contents(APP_ROOT . '/storage/.htaccess', "Require all denied\nDeny from all\n");
    }
    return $dir;
}

function avatar_url(?array $u): ?string
{
    return !empty($u['avatar']) ? url('avatar', ['u' => $u['id'], 'v' => substr((string)$u['avatar'], 0, 8)]) : null;
}

/** Photo ronde ou initiales. */
function avatar_html(?array $u, string $size = 'md'): string
{
    $src = avatar_url($u);
    if ($src) return '<img class="avatar ' . e($size) . '" src="' . e($src) . '" alt="">';
    $initials = mb_strtoupper(mb_substr(trim((string)($u['display_name'] ?? '?')), 0, 1));
    return '<span class="avatar ' . e($size) . ' initials" aria-hidden="true">' . e($initials) . '</span>';
}

/** Enregistre une photo : vérifiée, recadrée en carré 256 px, réencodée en JPEG (métadonnées supprimées). */
function save_avatar(int $userId, array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return 'Aucune image reçue.';
    if ($file['size'] > AVATAR_MAX_BYTES) return 'Image trop lourde (4 Mo maximum).';
    if (!function_exists('imagecreatefromstring')) return 'Le traitement d\'images n\'est pas disponible sur le serveur.';
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return 'Format accepté : JPEG, PNG ou WebP.';
    if ($info[0] * $info[1] > 40_000_000) return 'Image trop grande.';
    $src = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
    if (!$src) return 'Image illisible.';
    $w = imagesx($src); $h = imagesy($src); $side = min($w, $h);
    $dst = imagecreatetruecolor(256, 256);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), 256, 256, $side, $side);
    $name = bin2hex(random_bytes(12)) . '.jpg';
    $ok = imagejpeg($dst, avatar_dir() . '/' . $name, 85);
    imagedestroy($src); imagedestroy($dst);
    if (!$ok) return 'Enregistrement impossible.';
    remove_avatar($userId);
    q('UPDATE users SET avatar=? WHERE id=?', [$name, $userId]);
    return null;
}

function remove_avatar(int $userId): void
{
    $old = one('SELECT avatar FROM users WHERE id=?', [$userId])['avatar'] ?? null;
    if ($old && preg_match('/^[a-f0-9]{24}\.jpg$/', $old)) @unlink(avatar_dir() . '/' . $old);
    q('UPDATE users SET avatar=NULL WHERE id=?', [$userId]);
}

/** Sert une photo si le demandeur partage un dossier avec son propriétaire (ou c'est la sienne). */
function serve_avatar(array $viewer, int $ownerId): never
{
    $ok = $viewer['id'] === $ownerId || one('SELECT 1 FROM family_members a JOIN family_members b ON a.family_id=b.family_id WHERE a.user_id=? AND b.user_id=?', [$viewer['id'], $ownerId]);
    $name = $ok ? (one('SELECT avatar FROM users WHERE id=?', [$ownerId])['avatar'] ?? null) : null;
    $path = $name && preg_match('/^[a-f0-9]{24}\.jpg$/', $name) ? avatar_dir() . '/' . $name : null;
    if (!$path || !is_file($path)) { http_response_code(404); exit; }
    header_remove('Cache-Control');
    header('Content-Type: image/jpeg');
    header('Cache-Control: private, max-age=86400');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// ---------- Messagerie ----------
function family_tutor(int $familyId): ?array
{
    return one('SELECT u.id, u.display_name, u.phone, u.email, u.avatar FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE fm.member_role="tutor" ORDER BY u.id LIMIT 1', [$familyId]);
}

/** Canaux : « family » = parent <-> tutrice ; « student » = élève <-> tutrice (lisible par le parent). */
const MSG_CHANNELS = ['family', 'student'];

function messages_have_channels(): bool
{
    static $ok = null;
    if ($ok === null) { try { one('SELECT channel FROM messages LIMIT 1'); $ok = true; } catch (PDOException) { $ok = false; } }
    return $ok;
}
function chan_sql(string $channel, string $alias = 'm'): string
{
    if (!messages_have_channels()) return $channel === 'family' ? '' : ' AND 0';
    return ' AND ' . $alias . '.channel=' . ($channel === 'student' ? '"student"' : '"family"');
}

function family_messages(int $familyId, string $channel = 'family'): array
{
    return all('SELECT m.*, u.display_name, u.role, u.avatar, u.id uid FROM messages m LEFT JOIN users u ON u.id=m.sender_id WHERE m.family_id=?' . chan_sql($channel) . ' ORDER BY m.created_at, m.id', [$familyId]);
}

function send_message(int $familyId, array $sender, string $body, string $channel = 'family', ?string $kind = null, bool $allowEmpty = false): bool
{
    $body = trim(mb_substr($body, 0, 4000));
    if ($body === '' && !$allowEmpty) return false;
    if ($kind !== null) {
        // notification automatique : enregistrée comme message système (repli sur un message classique si la colonne manque)
        try { q('INSERT INTO messages (family_id, channel, sender_id, body, kind) VALUES (?,?,?,?,?)', [$familyId, $channel === 'student' ? 'student' : 'family', $sender['id'], $body, $kind]); if (function_exists('push_new_message')) push_new_message($familyId, $sender, $body, $channel, $kind); return true; }
        catch (PDOException) {}
    }
    if (messages_have_channels()) q('INSERT INTO messages (family_id, channel, sender_id, body) VALUES (?,?,?,?)', [$familyId, $channel === 'student' ? 'student' : 'family', $sender['id'], $body]);
    elseif ($channel === 'family') q('INSERT INTO messages (family_id, sender_id, body) VALUES (?,?,?)', [$familyId, $sender['id'], $body]);
    else return false;
    if (function_exists('push_new_message')) push_new_message($familyId, $sender, $body, $channel, $kind);
    return true;
}

/** Liste des fichiers d'un champ <input type=file multiple>. */
function uploaded_list(string $field): array
{
    $f = $_FILES[$field] ?? null; $out = [];
    if (!$f || !is_array($f['name'] ?? null)) return $f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? [$f] : [];
    foreach ($f['name'] as $i => $n) if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    return $out;
}
/** Envoi d'un message saisi (texte et/ou pièces jointes). Renvoie null si envoyé, sinon l'erreur. */
function post_message(int $familyId, array $sender, string $channel = 'family'): ?string
{
    $body = (string)($_POST['body'] ?? ''); $files = uploaded_list('files');
    if (count($files) > MSG_FILES_MAX) return 'Pas plus de ' . MSG_FILES_MAX . ' pièces jointes par message.';
    foreach ($files as $f) if ($err = upload_check($f, MSG_FILE_TYPES, 'photo, PDF, document Word, Excel, PowerPoint, texte, audio ou vidéo')) return mb_strimwidth((string)$f['name'], 0, 40, '…') . ' : ' . $err;
    if (trim($body) === '' && !$files) return 'Écrivez un message ou joignez un fichier.';
    if (!send_message($familyId, $sender, $body, $channel, null, (bool)$files)) return 'Message non envoyé.';
    $mid = (int)(one('SELECT MAX(id) id FROM messages WHERE family_id=? AND sender_id=?', [$familyId, $sender['id']])['id'] ?? 0);
    foreach ($files as $f) {
        $r = store_upload($f, MSG_FILE_TYPES);
        if (is_array($r)) { try { q('INSERT INTO message_files (message_id, stored, original, mime, size) VALUES (?,?,?,?,?)', [$mid, ...$r]); } catch (PDOException $e) { error_log('message_files: ' . $e->getMessage()); } }
    }
    return null;
}
/** Pièces jointes d'une liste de messages, groupées par message. */
function message_files_for(array $ids): array
{
    $ids = array_filter(array_map('intval', $ids)); if (!$ids) return [];
    try { $rows = all('SELECT * FROM message_files WHERE message_id IN (' . implode(',', $ids) . ') ORDER BY id'); } catch (PDOException) { return []; }
    $out = []; foreach ($rows as $r) $out[(int)$r['message_id']][] = $r;
    return $out;
}
/** Pièce jointe accessible au demandeur (membre de la famille ; l'élève : son propre fil uniquement), sinon null. */
function message_file_for(array $viewer, int $id): ?array
{
    $f = null;
    try { $f = one('SELECT mf.*, m.family_id, m.channel, m.sender_id, m.created_at AS sent_at FROM message_files mf JOIN messages m ON m.id=mf.message_id WHERE mf.id=?', [$id]); } catch (PDOException) {}
    $ok = $f && one('SELECT 1 FROM family_members WHERE family_id=? AND user_id=?', [$f['family_id'], $viewer['id']]);
    if ($ok && $viewer['role'] === 'student') $ok = ($f['channel'] ?? 'family') === 'student';
    return $ok && preg_match('/^[a-f0-9]{24}\.[a-z0-9]{2,4}$/', $f['stored']) && is_file(files_dir() . '/' . $f['stored']) ? $f : null;
}
/** Sert une pièce jointe (brute). ?dl=1 : téléchargement ; ?embed=1 : affichable dans la visionneuse de l'app. */
function serve_message_file(array $viewer, int $id): never
{
    $f = message_file_for($viewer, $id);
    if (!$f) { http_response_code(404); exit('Fichier introuvable.'); }
    $path = files_dir() . '/' . $f['stored'];
    $inline = in_array($f['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'], true) && empty($_GET['dl']);
    header_remove('Cache-Control');
    header('Content-Type: ' . $f['mime']);
    header('X-Content-Type-Options: nosniff');
    if (!empty($_GET['embed']) && $f['mime'] === 'application/pdf') { header_remove('X-Frame-Options'); header("Content-Security-Policy: frame-ancestors 'self'"); }
    else header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($f['original']));
    header('Cache-Control: private, max-age=3600');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

/** Marque comme lus les messages reçus (envoyés par « l'autre côté » du canal). Le parent ne marque jamais le fil de l'élève. */
function mark_messages_read(int $familyId, array $viewer, string $channel = 'family'): void
{
    if ($viewer['role'] === 'parent' && $channel === 'student') return;
    $sideTutor = $viewer['role'] === 'tutor';
    q('UPDATE messages m JOIN users u ON u.id=m.sender_id SET m.read_at=NOW() WHERE m.family_id=? AND m.read_at IS NULL' . chan_sql($channel) . ' AND ' . ($sideTutor ? 'u.role<>"tutor"' : 'u.role="tutor"'), [$familyId]);
}

/** Messages non lus pour un utilisateur (toutes familles ou une seule ; tous canaux pour la tutrice, son canal sinon). */
function unread_count(array $user, ?int $familyId = null, ?string $channel = null): int
{
    $sideTutor = $user['role'] === 'tutor';
    $channel ??= match ($user['role']) { 'parent' => 'family', 'student' => 'student', default => null };
    $sql = 'SELECT COUNT(*) n FROM messages m JOIN users u ON u.id=m.sender_id JOIN family_members fm ON fm.family_id=m.family_id AND fm.user_id=?
            WHERE m.read_at IS NULL AND ' . ($sideTutor ? 'u.role<>"tutor"' : 'u.role="tutor"') . ($familyId ? ' AND m.family_id=' . (int)$familyId : '') . ($channel ? chan_sql($channel) : '');
    try { return (int)one($sql, [$user['id']])['n']; } catch (PDOException) { return 0; }
}
