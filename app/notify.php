<?php
declare(strict_types=1);
// Notifications e-mail des messages non lus, différées et regroupées (anti-spam) :
//  - un message n'est signalé que s'il est encore non lu après NOTIFY_DELAY_MIN minutes ;
//  - tous les messages non lus d'un fil sont regroupés dans un seul e-mail ;
//  - un même destinataire reçoit au plus un e-mail toutes les NOTIFY_COOLDOWN_MIN minutes ;
//  - chacun peut désactiver ces e-mails dans « Mon compte ».

const NOTIFY_DELAY_MIN = 30;
const NOTIFY_COOLDOWN_MIN = 120;

/** Envoi d'un e-mail texte. Hors production : écrit dans storage/outbox.log au lieu d'envoyer. */
function send_app_mail(string $to, string $subject, string|array $body, string $host): bool
{
    require_once APP_ROOT . '/app/mail_template.php';
    $tpl = is_array($body) ? $body : ['paragraphs' => [$body]];
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $host = preg_replace('/^www\./', '', preg_replace('/[^a-z0-9.-]/i', '', $host));
    $from = config('mail_from') ?: 'no-reply@' . $host;
    if (config('env') !== 'prod') {
        $dir = APP_ROOT . '/storage';
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        file_put_contents($dir . '/outbox.log', json_encode(['to' => $to, 'subject' => $subject, 'body' => mail_text($tpl), 'at' => date('c')], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        file_put_contents($dir . '/outbox_last.html', mail_html($tpl));
        return true;
    }
    return send_branded_mail($to, $subject, $tpl, $from);
}

/** Traite les notifications dues. $host : domaine public de l'application (pour les liens). */
function run_message_notifications(string $host, int $delayMin = NOTIFY_DELAY_MIN, int $cooldownMin = NOTIFY_COOLDOWN_MIN): array
{
    $stats = ['groups' => 0, 'sent' => 0, 'deferred' => 0, 'skipped' => 0];
    $base = 'https://' . preg_replace('/[^a-z0-9.:-]/i', '', $host) . rtrim((string)config('base_url'), '/') . '/index.php';
    if (config('env') !== 'prod') $base = 'http://' . preg_replace('/[^a-z0-9.:-]/i', '', $host) . '/index.php';

    // Messages non lus, non signalés, plus vieux que le délai ; « côté » = qui doit être prévenu
    $rows = all('SELECT m.id, m.family_id, ' . (messages_have_channels() ? 'm.channel' : '"family" channel') . ', m.body, m.created_at, u.role sender_role, u.display_name sender_name
                 FROM messages m JOIN users u ON u.id=m.sender_id
                 WHERE m.read_at IS NULL AND m.notified_at IS NULL AND m.created_at <= NOW() - INTERVAL ? MINUTE
                 ORDER BY m.family_id, m.created_at', [$delayMin]);
    $groups = [];
    foreach ($rows as $r) $groups[$r['family_id'] . ':' . $r['channel'] . ':' . ($r['sender_role'] === 'tutor' ? 'family' : 'tutor')][] = $r;

    foreach ($groups as $key => $msgs) {
        [$fid, $chan, $side] = explode(':', $key);
        $toStudent = $side === 'family' && $chan === 'student';
        $fid = (int)$fid;
        $stats['groups']++;
        $student = one('SELECT id, first_name, parent_email, student_email FROM students WHERE family_id=? ORDER BY id LIMIT 1', [$fid]);
        if (!$student) continue;
        $recipients = $toStudent
            ? all('SELECT u.id, u.display_name, u.email, s.perm_link_email notify_messages, u.last_notified_at FROM users u JOIN students s ON s.user_id=u.id AND s.family_id=? WHERE u.is_active=1', [$fid])
            : ($side === 'family'
            ? all('SELECT u.id, u.display_name, u.email, u.notify_messages, u.last_notified_at FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE u.role="parent" AND u.is_active=1', [$fid])
            : all('SELECT u.id, u.display_name, u.email, u.notify_messages, u.last_notified_at FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE fm.member_role="tutor" AND u.is_active=1', [$fid]));
        $allHandled = true;
        foreach ($recipients as $rc) {
            $email = $rc['email'] ?: ($toStudent ? $student['student_email'] : ($side === 'family' ? $student['parent_email'] : null));
            if (!(int)$rc['notify_messages'] || !$email) { $stats['skipped']++; continue; }
            if ($rc['last_notified_at'] && strtotime($rc['last_notified_at']) > time() - $cooldownMin * 60) { $stats['deferred']++; $allHandled = false; continue; }
            // Total des non-lus du fil (y compris ceux déjà signalés mais toujours pas lus)
            $n = (int)one('SELECT COUNT(*) n FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.family_id=? AND m.read_at IS NULL' . chan_sql($chan) . ' AND ' . ($side === 'family' ? 'u.role="tutor"' : 'u.role<>"tutor"'), [$fid])['n'];
            $last = end($msgs);
            $excerpt = mb_strimwidth(preg_replace('/\s+/', ' ', $last['body']), 0, 160, '…');
            $link = $base . '?' . http_build_query($toStudent ? ['r' => 's_messages'] : ['r' => $side === 'family' ? 'p_messages' : 't_messages', 'sid' => $student['id']] + ($chan === 'student' ? ['ch' => 'student'] : []));
            $subject = $n > 1 ? "$n nouveaux messages sur leevel.app" : 'Nouveau message sur leevel.app';
            $who = $side === 'family' ? ($toStudent ? 'de ta tutrice' : 'de votre tutrice') : ($chan === 'student' ? 'de ' . $student['first_name'] : 'au sujet de ' . $student['first_name']);
            $body = $toStudent
                ? ['title' => $n > 1 ? "Tu as $n messages non lus" : 'Tu as un nouveau message', 'greeting' => "Salut {$rc['display_name']},",
                   'paragraphs' => [($n > 1 ? "Tu as $n messages non lus" : 'Tu as un message non lu') . " $who sur leevel.app."],
                   'quote' => $excerpt, 'quote_by' => $last['sender_name'], 'cta_label' => 'Lire et répondre', 'cta_url' => $link,
                   'note' => "Tu reçois cet e-mail car ce message n'a pas été lu depuis plus de $delayMin minutes."]
                : ['title' => $n > 1 ? "$n messages non lus" : 'Nouveau message', 'greeting' => "Bonjour {$rc['display_name']},",
                   'paragraphs' => [($n > 1 ? "Vous avez $n messages non lus" : 'Vous avez un message non lu') . " $who sur leevel.app."],
                   'quote' => $excerpt, 'quote_by' => $last['sender_name'], 'cta_label' => 'Lire et répondre', 'cta_url' => $link,
                   'note' => "Vous recevez cet e-mail car ce message n'a pas été lu depuis plus de $delayMin minutes. Vous pouvez désactiver ces notifications dans « Mon compte »."];
            if (send_app_mail($email, $subject, $body, $host)) {
                q('UPDATE users SET last_notified_at=NOW() WHERE id=?', [$rc['id']]);
                $stats['sent']++;
            } else {
                $allHandled = false; // nouvel essai au prochain passage
            }
        }
        if ($allHandled) {
            $ids = array_map(fn($m) => (int)$m['id'], $msgs);
            q('UPDATE messages SET notified_at=NOW() WHERE id IN (' . implode(',', $ids) . ')');
        }
    }
    return $stats;
}
