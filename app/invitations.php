<?php
declare(strict_types=1);
// Invitations : lien à usage unique (7 jours), jeton aléatoire dont seul le hachage SHA-256 est stocké.

const INVITE_DAYS = 7;

function absolute_url(string $route, array $params = []): string
{
    $scheme = is_https() || config('env') === 'prod' ? 'https' : 'http';
    $host = preg_replace('/[^a-z0-9.:-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    return $scheme . '://' . $host . url($route, $params);
}

/** Crée une invitation et renvoie le jeton en clair (affiché/envoyé une seule fois). */
function create_invitation(int $familyId, int $studentId, string $role, string $email, string $name, int $by): array
{
    $token = bin2hex(random_bytes(32));
    q('INSERT INTO invitations (family_id, student_id, role, email, display_name, token_hash, expires_at, created_by) VALUES (?,?,?,?,?,?, NOW() + INTERVAL ' . INVITE_DAYS . ' DAY, ?)',
        [$familyId, $studentId, $role, $email, $name, hash('sha256', $token), $by]);
    $id = (int)db()->lastInsertId();
    $link = absolute_url('invite', ['token' => $token]);
    $sent = send_invite_mail($email, $name, $role, $link);
    q('UPDATE invitations SET mail_sent=? WHERE id=?', [(int)$sent, $id]);
    return ['id' => $id, 'link' => $link, 'sent' => $sent, 'email' => $email, 'role' => $role];
}

/** Invitation valide (non utilisée, non expirée) pour un jeton, ou null. */
function find_invitation(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    return one('SELECT i.*, s.first_name, f.guardian_consent_at FROM invitations i JOIN students s ON s.id=i.student_id JOIN families f ON f.id=i.family_id
                WHERE i.token_hash=? AND i.used_at IS NULL AND i.expires_at > NOW()', [hash('sha256', $token)]);
}

function send_invite_mail(string $to, string $name, string $role, string $link): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) return false;
    if (config('env') !== 'prod') return false; // en local : pas d'envoi réel, le lien est affiché
    $host = preg_replace('/^www\./', '', preg_replace('/[^a-z0-9.-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')));
    $from = config('mail_from') ?: 'no-reply@' . $host;
    $tutor = current_user()['display_name'] ?? 'Votre tutrice';
    $body = $role === 'parent'
        ? 'Bonjour' . ($name !== '' ? " $name" : '') . ",\n\n" . "$tutor vous invite à rejoindre leevel.app, l'espace de suivi scolaire de votre enfant.\n\nCréez votre compte (identifiant et mot de passe de votre choix) en cliquant sur ce lien :\n$link\n\nCe lien est personnel et valable " . INVITE_DAYS . " jours.\n\nÀ bientôt,\n$tutor"
        : "Salut $name,\n\n$tutor t'invite à rejoindre leevel.app pour suivre tes objectifs et tes séances.\n\nCrée ton compte (identifiant et mot de passe de ton choix) ici :\n$link\n\nLe lien est valable " . INVITE_DAYS . " jours.\n\nÀ bientôt !\n$tutor";
    $headers = implode("\r\n", ['From: leevel.app <' . $from . '>', 'Reply-To: ' . $from, 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: 8bit']);
    try {
        return @mail($to, mb_encode_mimeheader('Votre invitation sur leevel.app', 'UTF-8'), $body, $headers, '-f' . $from);
    } catch (Throwable) {
        return false;
    }
}

/** Accepte une invitation : crée le compte, l'associe au dossier, ouvre la session. */
function accept_invitation(array $inv, string $username, string $password, string $displayName, bool $consent): void
{
    $pdo = db();
    $pdo->beginTransaction();
    q('INSERT INTO users (username, display_name, role, password_hash) VALUES (?,?,?,?)', [$username, $displayName, $inv['role'], password_hash($password, PASSWORD_DEFAULT)]);
    $uid = (int)$pdo->lastInsertId();
    q('INSERT INTO family_members (family_id, user_id, member_role) VALUES (?,?,?)', [$inv['family_id'], $uid, $inv['role']]);
    if ($inv['role'] === 'student') q('UPDATE students SET user_id=? WHERE id=? AND user_id IS NULL', [$uid, $inv['student_id']]);
    if ($inv['role'] === 'parent' && $consent && !$inv['guardian_consent_at']) {
        q('UPDATE families SET guardian_consent_at=NOW(), guardian_consent_user_id=? WHERE id=?', [$uid, $inv['family_id']]);
    }
    q('UPDATE invitations SET used_at=NOW(), user_id=? WHERE id=? AND used_at IS NULL', [$uid, $inv['id']]);
    // Les autres invitations du même rôle pour ce dossier deviennent caduques
    q('UPDATE invitations SET expires_at=NOW() WHERE family_id=? AND role=? AND used_at IS NULL', [$inv['family_id'], $inv['role']]);
    $pdo->commit();
    session_regenerate_id(true);
    $_SESSION = ['uid' => $uid, 'last' => time()];
    q('INSERT INTO audit_log (user_id, family_id, action, detail, ip) VALUES (?,?,?,?,?)', [$uid, $inv['family_id'], 'invitation_accepted', $inv['role'], client_ip()]);
}
