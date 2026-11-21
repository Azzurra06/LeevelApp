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

function family_messages(int $familyId): array
{
    return all('SELECT m.*, u.display_name, u.role, u.avatar, u.id uid FROM messages m LEFT JOIN users u ON u.id=m.sender_id WHERE m.family_id=? ORDER BY m.created_at, m.id', [$familyId]);
}

function send_message(int $familyId, array $sender, string $body): bool
{
    $body = trim(mb_substr($body, 0, 4000));
    if ($body === '') return false;
    q('INSERT INTO messages (family_id, sender_id, body) VALUES (?,?,?)', [$familyId, $sender['id'], $body]);
    return true;
}

/** Marque comme lus les messages reçus (envoyés par « l'autre côté »). */
function mark_messages_read(int $familyId, array $viewer): void
{
    $sideTutor = $viewer['role'] === 'tutor';
    q('UPDATE messages m JOIN users u ON u.id=m.sender_id SET m.read_at=NOW() WHERE m.family_id=? AND m.read_at IS NULL AND ' . ($sideTutor ? 'u.role<>"tutor"' : 'u.role="tutor"'), [$familyId]);
}

/** Messages non lus pour un utilisateur (toutes familles, ou une seule). */
function unread_count(array $user, ?int $familyId = null): int
{
    $sideTutor = $user['role'] === 'tutor';
    $sql = 'SELECT COUNT(*) n FROM messages m JOIN users u ON u.id=m.sender_id JOIN family_members fm ON fm.family_id=m.family_id AND fm.user_id=?
            WHERE m.read_at IS NULL AND ' . ($sideTutor ? 'u.role<>"tutor"' : 'u.role="tutor"') . ($familyId ? ' AND m.family_id=' . (int)$familyId : '');
    try { return (int)one($sql, [$user['id']])['n']; } catch (PDOException) { return 0; }
}
