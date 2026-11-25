<?php
// Gabarit des e-mails aux couleurs de leevel.app (HTML compatible messageries + version texte).

/**
 * $o : title, greeting, paragraphs[] (texte), quote (texte, facultatif), quote_by, cta_label, cta_url, note (petit texte de bas de mail)
 */
function mail_html(array $o): string
{
    $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif";
    $p = '';
    foreach ($o['paragraphs'] ?? [] as $x) $p .= '<p style="margin:0 0 14px;font:16px/1.6 ' . $font . ';color:#2f2a24">' . nl2br($h($x)) . '</p>';
    $quote = !empty($o['quote']) ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 20px"><tr><td style="background:#efeaff;border-left:4px solid #5b3df5;border-radius:10px;padding:14px 16px;font:15px/1.55 ' . $font . ';color:#2f2a24">'
        . (!empty($o['quote_by']) ? '<div style="font-size:13px;color:#5b3df5;font-weight:700;margin-bottom:4px">' . $h($o['quote_by']) . '</div>' : '') . '« ' . nl2br($h($o['quote'])) . ' »</td></tr></table>' : '';
    $cta = !empty($o['cta_url']) ? '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 22px"><tr><td style="border-radius:999px;background:#5b3df5"><a href="' . $h($o['cta_url']) . '" style="display:inline-block;padding:13px 26px;font:700 15px ' . $font . ';color:#ffffff;text-decoration:none;border-radius:999px">' . $h($o['cta_label'] ?? 'Ouvrir leevel.app') . '</a></td></tr></table>'
        . '<p style="margin:0 0 14px;font:12px/1.5 ' . $font . ';color:#7a6f62">Si le bouton ne fonctionne pas, copiez ce lien :<br><a href="' . $h($o['cta_url']) . '" style="color:#5b3df5;word-break:break-all">' . $h($o['cta_url']) . '</a></p>' : '';
    $note = !empty($o['note']) ? '<p style="margin:0;font:12px/1.5 ' . $font . ';color:#7a6f62">' . nl2br($h($o['note'])) . '</p>' : '';
    return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title>' . $h($o['title'] ?? 'leevel.app') . '</title></head>'
        . '<body style="margin:0;padding:0;background:#faf7f2">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf7f2"><tr><td align="center" style="padding:28px 14px">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px">'
        . '<tr><td style="padding:0 6px 16px;font:800 22px ' . $font . ';letter-spacing:-.5px;color:#2f2a24">leevel<span style="color:#5b3df5">.app</span></td></tr>'
        . '<tr><td style="background:#fffdf9;border:1px solid #efe6d8;border-radius:16px;overflow:hidden">'
        . '<div style="height:5px;background:#5b3df5"></div>'
        . '<div style="padding:28px 28px 22px">'
        . (!empty($o['title']) ? '<h1 style="margin:0 0 16px;font:800 22px/1.3 ' . $font . ';color:#2f2a24">' . $h($o['title']) . '</h1>' : '')
        . (!empty($o['greeting']) ? '<p style="margin:0 0 14px;font:16px/1.6 ' . $font . ';color:#2f2a24">' . $h($o['greeting']) . '</p>' : '')
        . $p . $quote . $cta . $note
        . '</div></td></tr>'
        . '<tr><td style="padding:16px 6px 0;font:12px/1.5 ' . $font . ';color:#8b7d6b;text-align:center">leevel.app · le suivi scolaire, simplement.<br>Aucune donnée transmise à des tiers.</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** Version texte (repli pour les messageries sans HTML). */
function mail_text(array $o): string
{
    $t = [];
    if (!empty($o['greeting'])) $t[] = $o['greeting'];
    foreach ($o['paragraphs'] ?? [] as $x) $t[] = $x;
    if (!empty($o['quote'])) $t[] = (!empty($o['quote_by']) ? $o['quote_by'] . " :\n" : '') . '« ' . $o['quote'] . ' »';
    if (!empty($o['cta_url'])) $t[] = ($o['cta_label'] ?? 'Ouvrir') . ' : ' . $o['cta_url'];
    if (!empty($o['note'])) $t[] = $o['note'];
    return implode("\n\n", $t) . "\n\n--\nleevel.app";
}

/** Envoie un e-mail HTML + texte (multipart/alternative).
 *  Si un compte SMTP est configuré (smtp_user / smtp_pass), l'envoi passe par la boîte mail authentifiée
 *  (signature DKIM du domaine : bien meilleure délivrabilité) ; sinon, ou en cas d'échec, par mail(). */
function send_branded_mail(string $to, string $subject, array $o, string $from): bool
{
    $b = 'lv' . bin2hex(random_bytes(12));
    $headers = ['From: leevel.app <' . $from . '>', 'Reply-To: ' . $from, 'MIME-Version: 1.0', 'Content-Type: multipart/alternative; boundary="' . $b . '"'];
    $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode(mail_text($o)))
          . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode(mail_html($o)))
          . "--$b--";
    $subj = mb_encode_mimeheader($subject, 'UTF-8');
    if ((string)(config('smtp_user') ?? '') !== '' && (string)(config('smtp_pass') ?? '') !== '') {
        if (smtp_send($to, $subj, $headers, $body, $from)) return true;
    }
    try { return @mail($to, $subj, $body, implode("\r\n", $headers), '-f' . $from); }
    catch (Throwable) { return false; }
}

/** Envoi SMTP minimal (SSL implicite, AUTH LOGIN), sans dépendance. */
function smtp_send(string $to, string $subject, array $headers, string $body, string $from): bool
{
    $host = (string)(config('smtp_host') ?: 'smtp.hostinger.com'); $port = (int)(config('smtp_port') ?: 465);
    $fp = @stream_socket_client(($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]));
    if (!$fp) { error_log("smtp: connexion impossible ($errstr)"); return false; }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): string { $r = ''; while (($l = fgets($fp, 1024)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $r; };
    $cmd = function (string $c, array $ok) use ($fp, $read): bool { if ($c !== '') fwrite($fp, $c . "\r\n"); $r = $read(); $code = (int)substr($r, 0, 3); if (!in_array($code, $ok, true)) { error_log('smtp: ' . trim(preg_replace('/AUTH.*/', 'AUTH', $c)) . ' -> ' . trim($r)); return false; } return true; };
    $ehlo = preg_replace('/[^a-z0-9.-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
    $ok = $cmd('', [220]) && $cmd('EHLO ' . $ehlo, [250])
        && $cmd('AUTH LOGIN', [334]) && $cmd(base64_encode((string)config('smtp_user')), [334]) && $cmd(base64_encode((string)config('smtp_pass')), [235])
        && $cmd('MAIL FROM:<' . $from . '>', [250]) && $cmd('RCPT TO:<' . $to . '>', [250, 251]) && $cmd('DATA', [354]);
    if ($ok) {
        $dom = substr(strrchr($from, '@') ?: '@localhost', 1);
        $msg = implode("\r\n", array_merge(['Date: ' . date('r'), 'To: <' . $to . '>', 'Subject: ' . $subject, 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $dom . '>'], $headers)) . "\r\n\r\n" . $body;
        $msg = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $msg));
        fwrite($fp, str_replace("\n", "\r\n", $msg) . "\r\n.\r\n");
        $ok = $cmd('', [250]);
    }
    @fwrite($fp, "QUIT\r\n"); @fclose($fp);
    return $ok;
}
