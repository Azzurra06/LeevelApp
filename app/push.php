<?php
declare(strict_types=1);
// Notifications push (Web Push, RFC 8030 / 8291 / 8292) sans dépendance : chiffrement aes128gcm + VAPID (ES256).
// Fonctionne sur Android, ordinateurs (Chrome, Firefox, Edge, Safari macOS) et iPhone/iPad (iOS 16.4+) quand l'app est ajoutée à l'écran d'accueil.

const PUSH_TYPES = ['messages' => 'Nouveaux messages', 'sessions' => 'Rappel la veille d\'une séance', 'finance' => 'Échéances de paiement', 'reports' => 'Bilans de séance', 'session_end' => 'Fin de séance (bilan, retour)'];

function push_available(): bool
{
    static $ok = null;
    if ($ok === null) { try { db()->query('SELECT 1 FROM push_subscriptions LIMIT 1'); $ok = function_exists('openssl_pkey_derive') && function_exists('hash_hkdf'); } catch (PDOException) { $ok = false; } }
    return $ok;
}
function b64u(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64u_dec(string $s): string { return (string)base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4)); }

function vapid_file(): string { return APP_ROOT . '/storage/vapid.php'; }
/** Clés VAPID du serveur (créées une fois, stockées hors du web). */
function vapid_keys(): array
{
    $f = vapid_file();
    if (is_file($f)) { $k = require $f; if (is_array($k) && !empty($k['private'])) return $k; }
    $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    openssl_pkey_export($key, $pem);
    $d = openssl_pkey_get_details($key)['ec'];
    $pub = "\x04" . str_pad($d['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
    $k = ['private' => $pem, 'public' => b64u($pub)];
    if (!is_dir(dirname($f))) mkdir(dirname($f), 0750, true);
    file_put_contents($f, "<?php // clés VAPID des notifications push (ne pas partager)\nreturn " . var_export($k, true) . ";\n", LOCK_EX);
    @chmod($f, 0600);
    return $k;
}
/** Clé publique EC (point non compressé de 65 octets) au format PEM. */
function ec_public_pem(string $point): string
{
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $point;
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
}
/** Signature DER (ECDSA) → r||s brut de 64 octets (JWT ES256). */
function der_to_raw(string $der): string
{
    $o = 3; $rl = ord($der[$o]); $r = substr($der, $o + 1, $rl); $o += 1 + $rl + 1; $sl = ord($der[$o]); $s = substr($der, $o + 1, $sl);
    $fix = fn($x) => str_pad(ltrim($x, "\0"), 32, "\0", STR_PAD_LEFT);
    return $fix($r) . $fix($s);
}
function vapid_header(string $endpoint): string
{
    $k = vapid_keys(); $u = parse_url($endpoint);
    $aud = $u['scheme'] . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '');
    $sub = 'https://' . preg_replace('/[^a-z0-9.-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? config('host') ?? 'leevel.app'));
    $data = b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])) . '.' . b64u(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => $sub]));
    openssl_sign($data, $sig, $k['private'], OPENSSL_ALGO_SHA256);
    return 'vapid t=' . $data . '.' . b64u(der_to_raw($sig)) . ', k=' . $k['public'];
}
/** Chiffre la charge utile pour un abonnement (aes128gcm, RFC 8291). */
function push_encrypt(string $payload, string $uaPublicB64, string $authB64): string
{
    $uaPublic = b64u_dec($uaPublicB64); $auth = b64u_dec($authB64);
    $as = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $d = openssl_pkey_get_details($as)['ec'];
    $asPublic = "\x04" . str_pad($d['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
    $shared = openssl_pkey_derive(openssl_pkey_get_public(ec_public_pem($uaPublic)), $as, 32);
    $prkKey = hash_hmac('sha256', $shared, $auth, true);
    $ikm = hash_hmac('sha256', "WebPush: info\0" . $uaPublic . $asPublic . "\x01", $prkKey, true);
    $salt = random_bytes(16);
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $cek = substr(hash_hmac('sha256', "Content-Encoding: aes128gcm\0\x01", $prk, true), 0, 16);
    $nonce = substr(hash_hmac('sha256', "Content-Encoding: nonce\0\x01", $prk, true), 0, 12);
    $cipher = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    return $salt . pack('N', 4096) . chr(65) . $asPublic . $cipher . $tag;
}
/** Envoie à un abonnement. Retourne le code HTTP (201 = ok ; 404/410 = abonnement expiré). */
function push_send_one(array $sub, array $msg): int
{
    $body = push_encrypt(json_encode($msg, JSON_UNESCAPED_UNICODE), $sub['p256dh'], $sub['auth']);
    $ch = curl_init($sub['endpoint']);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Content-Type: application/octet-stream', 'Content-Encoding: aes128gcm', 'TTL: 86400', 'Urgency: normal', 'Authorization: ' . vapid_header($sub['endpoint'])]]);
    curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return $code;
}
function push_prefs(array $u): array
{
    $p = json_decode((string)(one('SELECT push_prefs FROM users WHERE id=?', [$u['id']])['push_prefs'] ?? ''), true);
    return array_merge(array_fill_keys(array_keys(PUSH_TYPES), true), is_array($p) ? array_map('boolval', $p) : []);
}
/** Notifie un utilisateur sur tous ses appareils (si ce type est activé). $msg : title, body, url, tag. */
function push_user(int $userId, string $type, array $msg): int
{
    if (!push_available()) return 0;
    try {
        $prefs = push_prefs(['id' => $userId]);
        if ($type !== '_test' && empty($prefs[$type])) return 0;
        $sent = 0;
        foreach (all('SELECT * FROM push_subscriptions WHERE user_id=?', [$userId]) as $sub) {
            $code = push_send_one($sub, $msg + ['icon' => asset('icon-192.png'), 'badge' => asset('icon-192.png')]);
            if ($code >= 200 && $code < 300) { $sent++; q('UPDATE push_subscriptions SET last_ok_at=NOW(), fail_count=0 WHERE id=?', [$sub['id']]); }
            elseif (in_array($code, [404, 410], true)) q('DELETE FROM push_subscriptions WHERE id=?', [$sub['id']]);
            else q('UPDATE push_subscriptions SET fail_count=fail_count+1 WHERE id=?', [$sub['id']]);
        }
        q('DELETE FROM push_subscriptions WHERE fail_count >= 10');
        return $sent;
    } catch (Throwable $e) { return 0; }
}
/** Après l'envoi d'un message : prévient les destinataires (parents ou tutrice ; élève sur son fil). */
function push_new_message(int $familyId, array $sender, string $body, string $channel, ?string $kind): void
{
    if (!push_available()) return;
    $st = one('SELECT id, first_name, user_id FROM students WHERE family_id=? ORDER BY id LIMIT 1', [$familyId]);
    $excerpt = mb_strimwidth(preg_replace('/\s+/', ' ', $body), 0, 140, '…');
    $type = $kind === 'system' ? (str_starts_with($body, 'Nouvelle échéance') ? 'finance' : 'messages') : (str_starts_with($body, 'Bilan de la séance') ? 'reports' : 'messages');
    if (($sender['role'] ?? '') === 'tutor') {
        $to = $channel === 'student' ? ($st && $st['user_id'] ? [(int)$st['user_id']] : [])
            : array_map('intval', array_column(all('SELECT u.id FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE u.role="parent" AND u.is_active=1', [$familyId]), 'id'));
        $title = $kind === 'system' ? ($type === 'finance' ? 'Nouvelle échéance' : 'leevel.app') : ($type === 'reports' ? 'Nouveau bilan de séance' : 'Message de ' . ($sender['display_name'] ?? 'la tutrice'));
        $url = $channel === 'student' ? url('s_messages') : ($type === 'finance' ? url('p_finance') : url('p_messages'));
    } else {
        $to = array_map('intval', array_column(all('SELECT u.id FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE fm.member_role="tutor" AND u.is_active=1', [$familyId]), 'id'));
        $title = 'Message de ' . ($sender['display_name'] ?? '') . ($st ? ' (' . $st['first_name'] . ')' : '');
        $url = url('t_messages', ['sid' => $st['id'] ?? 0] + ($channel === 'student' ? ['ch' => 'student'] : []));
    }
    foreach ($to as $uid) if ($uid !== (int)($sender['id'] ?? 0)) push_user($uid, $type, ['title' => $title, 'body' => $excerpt, 'url' => $url, 'tag' => 'msg-' . $familyId . '-' . $channel]);
}
/** Rappels de la veille (à partir de 17 h) pour les séances du lendemain, une seule fois par séance. */
function push_session_reminders(): void
{
    if (!push_available() || (int)date('G') < 17) return;
    try {
        $rows = all('SELECT se.id, se.starts_at, se.duration_min, se.mode, s.first_name, s.family_id, s.user_id FROM sessions se JOIN students s ON s.id=se.student_id
                     WHERE se.status="planned" AND se.reminded_at IS NULL AND DATE(se.starts_at)=DATE(NOW() + INTERVAL 1 DAY)');
    } catch (PDOException) { return; }
    foreach ($rows as $se) {
        q('UPDATE sessions SET reminded_at=NOW() WHERE id=?', [$se['id']]);
        $when = 'Demain à ' . date('H\hi', strtotime($se['starts_at'])) . ' · ' . fdur((int)$se['duration_min']) . ' · ' . (MODES[$se['mode']] ?? '');
        foreach (all('SELECT u.id FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE u.role="parent" AND u.is_active=1', [$se['family_id']]) as $p)
            push_user((int)$p['id'], 'sessions', ['title' => 'Séance de ' . $se['first_name'] . ' demain', 'body' => $when, 'url' => url('p_planning'), 'tag' => 'sess-' . $se['id']]);
        if ($se['user_id']) push_user((int)$se['user_id'], 'sessions', ['title' => 'Ta séance demain 📅', 'body' => $when, 'url' => url('s_planning'), 'tag' => 'sess-' . $se['id']]);
    }
}
/** Fin de séance : la tutrice est invitée à rédiger le bilan, l'élève à donner son retour (notification + e-mail), une seule fois. */
function push_session_ended(string $host = ''): void
{
    $host = preg_replace('/[^a-z0-9.:-]/i', '', $host ?: (string)($_SERVER['HTTP_HOST'] ?? ''));
    try {
        $rows = all('SELECT se.*, s.first_name, s.family_id, s.user_id AS s_uid, s.student_email FROM sessions se JOIN students s ON s.id=se.student_id
                     WHERE se.status IN ("planned","done") AND se.ended_notified_at IS NULL
                       AND se.starts_at + INTERVAL se.duration_min MINUTE <= NOW() AND se.starts_at + INTERVAL se.duration_min MINUTE > NOW() - INTERVAL 1 DAY');
    } catch (PDOException) { return; }
    if (!$rows) return;
    require_once APP_ROOT . '/app/notify.php';
    $abs = fn(string $u) => $host !== '' ? 'https://' . $host . $u : $u;
    foreach ($rows as $se) {
        q('UPDATE sessions SET ended_notified_at=NOW() WHERE id=? AND ended_notified_at IS NULL', [$se['id']]);
        $label = session_label($se); $when = fdatetime($se['starts_at']);
        // Tutrice(s) de la famille
        foreach (all('SELECT u.id, u.email, u.display_name FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE fm.member_role="tutor" AND u.is_active=1', [$se['family_id']]) as $t) {
            $link = url('t_student', ['sid' => $se['student_id'], 'tab' => 'seances']) . '#sess-' . (int)$se['id'];
            push_user((int)$t['id'], 'session_end', ['title' => 'Séance avec ' . $se['first_name'] . ' terminée', 'body' => 'Pensez à rédiger le bilan : ' . $label, 'url' => $link, 'tag' => 'end-' . $se['id']]);
            if ($t['email'] && $host !== '') send_app_mail($t['email'], 'Séance avec ' . $se['first_name'] . ' terminée : le bilan', [
                'title' => 'Séance terminée', 'greeting' => 'Bonjour ' . $t['display_name'] . ',',
                'paragraphs' => ['La séance avec ' . $se['first_name'] . ' (' . $when . ' · ' . $label . ') est terminée.', 'Vous pouvez rédiger le bilan maintenant, tant que tout est encore frais. Il sera envoyé au parent dès que vous le validez.'],
                'cta_label' => 'Rédiger le bilan', 'cta_url' => $abs($link)], $host);
        }
        // Élève
        if ($se['s_uid']) {
            $link = url('s_subjects') . '#recap';
            push_user((int)$se['s_uid'], 'session_end', ['title' => 'Séance terminée ✅', 'body' => 'Dis à ta tutrice comment ça s\'est passé !', 'url' => $link, 'tag' => 'end-' . $se['id']]);
            $mail = (string)(one('SELECT email FROM users WHERE id=?', [$se['s_uid']])['email'] ?? '') ?: (string)$se['student_email'];
            if ($mail && $host !== '') send_app_mail($mail, 'Ta séance est terminée : ton retour', [
                'title' => 'Comment ça s\'est passé ?', 'greeting' => 'Bonjour ' . $se['first_name'] . ',',
                'paragraphs' => ['Ta séance du ' . $when . ' est terminée.', 'Prends une minute pour dire à ta tutrice comment ça s\'est passé : ce que tu as compris, ce qui était difficile. Ça l\'aide à préparer la suite.'],
                'cta_label' => 'Donner mon retour', 'cta_url' => $abs($link)], $host);
        }
    }
}
/** Tâches périodiques légères, déclenchées au fil des visites (au plus toutes les 10 min), après l'envoi de la page. */
function push_tick(): void
{
    $f = APP_ROOT . '/storage/push_tick';
    if (is_file($f) && filemtime($f) > time() - 600) return;
    @touch($f);
    register_shutdown_function(function () { if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request(); push_session_reminders(); push_session_ended(); });
}
