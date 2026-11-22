<?php
declare(strict_types=1);
// Espace tutrice : toutes les actions vérifient l'appartenance au dossier (student_for).

function tutor_route(string $r, bool $isPost): never
{
    $u = require_login('tutor');

    // Création de famille (pas de dossier requis)
    if ($r === 't_users') {
        if ($isPost) create_family($u);
        $families = all('SELECT f.* FROM families f JOIN family_members fm ON fm.family_id=f.id AND fm.user_id=? ORDER BY f.is_demo, f.id', [$u['id']]);
        foreach ($families as &$f) { $f['users'] = family_users((int)$f['id']); $f['students'] = all('SELECT * FROM students WHERE family_id=?', [$f['id']]);
            $f['invites'] = all('SELECT * FROM invitations WHERE family_id=? AND used_at IS NULL ORDER BY id', [$f['id']]); }
        render('t_users', ['families' => $families]);
    }
    if ($r === 't_finance') {
        if (!finance_available()) render('error', ['msg' => 'La base de données doit être mise à jour (migration 018).']);
        $mine = fn(int $sid) => one('SELECT s.* FROM students s JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=? AND fm.member_role="tutor" WHERE s.id=?', [$u['id'], $sid]);
        $month = preg_match('/^\d{4}-\d{2}$/', (string)($_REQUEST['m'] ?? '')) ? $_REQUEST['m'] : date('Y-m');
        if ($isPost) {
            $act = (string)($_POST['action'] ?? '');
            $num = fn(string $k) => ($v = str_replace([',', ' ', '€'], ['.', '', ''], trim((string)($_POST[$k] ?? '')))) === '' ? null : max(0, min(9999, round((float)$v, 2)));
            if ($act === 'settings') {
                setting_set('tax_credit', !empty($_POST['tax_credit']) ? '1' : '0');
                setting_set('pay_instructions', post_str('pay_instructions', 1000) ?: null);
                flash('Réglages enregistrés.');
            } elseif ($act === 'rate' && ($st = $mine(post_int('sid')))) {
                q('UPDATE students SET hourly_rate=?, hourly_rate_visio=? WHERE id=?', [$num('hourly_rate'), $num('hourly_rate_visio'), $st['id']]);
                $pct = $num('discount_pct'); $pct = $pct === null ? null : min(100, $pct);
                try { q('UPDATE students SET discount_pct=?, discount_label=? WHERE id=?', [$pct ?: null, $pct ? (post_str('discount_label', 80) ?: null) : null, $st['id']]); } catch (PDOException) {}
                flash('Tarif de ' . $st['first_name'] . ' enregistré.');
            } elseif ($act === 'create' && ($st = $mine(post_int('sid')))) {
                $b = month_billing($st, $month);
                $manual = !empty($_POST['manual']);
                if ($manual) {
                    $gross = $num('amount') ?? 0.0;
                    $dRaw = trim((string)($_POST['discount'] ?? ''));
                    $isPct = str_contains($dRaw, '%');
                    $dVal = $num('discount') ?? 0.0;
                    if ($isPct) { $dVal = min(100, (float)str_replace([',', '%', ' '], ['.', '', ''], $dRaw)); }
                    $disc = $isPct ? round($gross * $dVal / 100, 2) : min($gross, $dVal);
                    $dLabel = post_str('discount_label', 80) ?: null;
                } else { $gross = $b['gross']; $disc = $b['discount']; $dLabel = $b['discount'] > 0 ? discount_text($b['discount_label'] ?: null, $b['discount_pct']) : null; }
                $amount = round($gross - $disc, 2);
                if ($amount <= 0) flash('Montant nul : renseignez le tarif ou le montant.', 'err');
                else {
                    $label = post_str('label', 160) ?: 'Séances de ' . mb_strtolower(month_label($month));
                    $due = preg_match('/^\d{4}-\d{2}-\d{2}$/', post_str('due_date', 10)) ? post_str('due_date', 10) : date('Y-m-d', strtotime($month . '-01 +1 month +9 days'));
                    try { q('INSERT INTO payments (student_id, period, label, minutes, gross, discount, discount_label, amount, due_date) VALUES (?,?,?,?,?,?,?,?,?)', [$st['id'], $month, $label, $manual ? 0 : $b['minutes'], $gross, $disc ?: null, $disc ? ($dLabel ?: 'Remise') : null, $amount, $due]); }
                    catch (PDOException) { q('INSERT INTO payments (student_id, period, label, minutes, amount, due_date) VALUES (?,?,?,?,?,?)', [$st['id'], $month, $label, $manual ? 0 : $b['minutes'], $amount, $due]); }
                    if (!empty($_POST['notify'])) send_message((int)$st['family_id'], $u, "Nouvelle échéance pour " . $st['first_name'] . " : " . $label . " · " . money($amount) . ($disc > 0 ? " (après " . mb_strtolower($dLabel ?: 'remise') . " de " . money($disc) . ")" : '') . " à régler avant le " . fdate($due) . ".\nLe détail est dans « Finances » (menu Mon compte).");
                    audit('payment_created', money($amount), (int)$st['family_id']);
                    flash('Échéance créée' . (!empty($_POST['notify']) ? ' et envoyée au parent.' : '.'));
                }
            } elseif (in_array($act, ['pay', 'unpay', 'delete'], true)) {
                $p = one('SELECT p.* FROM payments p WHERE p.id=?', [post_int('id')]);
                $st = $p ? $mine((int)$p['student_id']) : null;
                if (!$st) { http_response_code(403); render('error', ['msg' => 'Paiement introuvable.']); }
                if ($act === 'pay') {
                    $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', post_str('paid_at', 10)) ? post_str('paid_at', 10) : date('Y-m-d');
                    q('UPDATE payments SET status="paid", paid_at=?, method=? WHERE id=?', [$d, post_enum('method', PAY_METHODS, 'virement'), $p['id']]);
                    flash('Paiement enregistré.');
                } elseif ($act === 'unpay') { q('UPDATE payments SET status="pending", paid_at=NULL, method=NULL WHERE id=?', [$p['id']]); flash('Paiement remis en attente.'); }
                else { q('DELETE FROM payments WHERE id=?', [$p['id']]); flash('Échéance supprimée.'); }
            }
            redirect('t_finance', ['m' => $month]);
        }
        $students = all('SELECT s.*, f.name family_name FROM students s JOIN families f ON f.id=s.family_id JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=? AND fm.member_role="tutor" ORDER BY f.is_demo, s.first_name', [$u['id']]);
        $rows = []; $sids = [0];
        foreach ($students as $st) {
            $sids[] = (int)$st['id'];
            $rows[] = ['s' => $st, 'b' => month_billing($st, $month), 'pay' => one('SELECT * FROM payments WHERE student_id=? AND period=? ORDER BY id DESC LIMIT 1', [$st['id'], $month])];
        }
        $in = implode(',', $sids);
        render('t_finance', ['month' => $month, 'rows' => $rows,
            'pending' => all("SELECT p.*, s.first_name FROM payments p JOIN students s ON s.id=p.student_id WHERE p.student_id IN ($in) AND p.status='pending' ORDER BY p.due_date"),
            'paid' => all("SELECT p.*, s.first_name FROM payments p JOIN students s ON s.id=p.student_id WHERE p.student_id IN ($in) AND p.status='paid' ORDER BY p.paid_at DESC, p.id DESC LIMIT 15"),
            'cashed' => (float)(one("SELECT SUM(amount) t FROM payments WHERE student_id IN ($in) AND status='paid' AND paid_at >= ? AND paid_at < ?", [$month . '-01', date('Y-m-d', strtotime($month . '-01 +1 month'))])['t'] ?? 0)]);
    }
    if ($r === 't_demo' && $isPost) {
        if (one('SELECT 1 FROM users WHERE username IN ("demo.parent","demo.eleve")')) {
            flash('Un dossier démo existe déjà. Redéfinissez ses mots de passe ci-dessous si besoin.', 'err');
            redirect('t_users');
        }
        require_once APP_ROOT . '/app/demo.php';
        $pw = create_demo((int)$u['id']);
        audit('demo_created');
        flash('Dossier démo créé. Notez ces mots de passe, ils ne seront plus affichés : demo.parent → ' . $pw['demo.parent'] . ' · demo.eleve → ' . $pw['demo.eleve'], 'warn');
        redirect('t_users');
    }
    if ($r === 't_invite' && $isPost) manage_invitation($u);
    if ($r === 't_invite_new' && $isPost) {
        require_once APP_ROOT . '/app/invitations.php';
        $st = one('SELECT s.* FROM students s JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=? AND fm.member_role="tutor" WHERE s.id=?', [$u['id'], post_int('sid')]);
        if (!$st) { http_response_code(403); render('error', ['msg' => 'Dossier non accessible.']); }
        $role = post_enum('role', ['parent' => 1, 'student' => 1], 'parent');
        $email = mb_strtolower(post_str('email', 120));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Adresse e-mail invalide.', 'err');
        elseif ($role === 'student' && $st['user_id']) flash('Cet élève a déjà un compte.', 'err');
        else {
            q('UPDATE invitations SET expires_at=NOW() WHERE family_id=? AND role=? AND used_at IS NULL', [$st['family_id'], $role]);
            $_SESSION['invite_result'] = [create_invitation((int)$st['family_id'], (int)$st['id'], $role, $email, $role === 'student' ? $st['first_name'] : '', (int)$u['id'])];
            audit('invitation_created', $role, (int)$st['family_id']);
            flash('Invitation créée.');
        }
        redirect('t_users', ['open' => $st['family_id']]);
    }
    if ($r === 't_user_update' && $isPost) {
        $target = one('SELECT u.*, a.family_id FROM users u JOIN family_members a ON a.user_id=u.id JOIN family_members b ON b.family_id=a.family_id AND b.user_id=? WHERE u.id=? AND u.role<>"tutor"', [$u['id'], post_int('user_id')]);
        if (!$target) { http_response_code(403); render('error', ['msg' => 'Compte non accessible.']); }
        if (($pw = (string)($_POST['password'] ?? '')) !== '') {
            if ($m = validate_password($pw)) { flash($m, 'err'); redirect('t_users', ['open' => $target['family_id']]); }
            q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $target['id']]);
            audit('password_reset', $target['username']);
            flash('Mot de passe redéfini pour ' . $target['username'] . '.');
        }
        if (isset($_POST['toggle_active'])) {
            q('UPDATE users SET is_active=1-is_active WHERE id=?', [$target['id']]);
            audit('user_toggle', $target['username']);
            flash('Statut du compte modifié.');
        }
        redirect('t_users', ['open' => $target['family_id']]);
    }

    $s = pick_student($u);
    if ($r === 't' && !$s) render('t_dashboard', ['s' => null]);
    if (!$s) redirect('t_users');
    $sid = (int)$s['id'];
    $back = fn(string $tab = '') => redirect($_POST['back'] ?? 't_student', ['sid' => $sid] + ($tab ? ['tab' => $tab] : []));

    switch ($r) {
        case 't':
            $an = latest_analysis($sid);
            $prio = effective_priorities($sid, $an);
            render('t_dashboard', ['s' => $s, 'an' => $an, 'prio' => $prio, 'reco' => recommendations($prio['axes'], $an),
                'students' => students_of($u), 'subs' => ['parent' => qn_latest('parent', $sid), 'student' => qn_latest('student', $sid)],
                'sessions' => all('SELECT * FROM sessions WHERE student_id=? AND starts_at>=CURDATE() AND status="planned" ORDER BY starts_at LIMIT 4', [$sid]),
                'goals' => all('SELECT * FROM goals WHERE student_id=? AND status<>"acquired" ORDER BY target_date IS NULL, target_date LIMIT 6', [$sid]),
                'goalStats' => one('SELECT COUNT(*) n, SUM(status="acquired") acq, ROUND(AVG(progress)) avgp FROM goals WHERE student_id=?', [$sid]),
                'help' => all('SELECT p.*, g.title goal FROM progress_entries p LEFT JOIN goals g ON g.id=p.goal_id WHERE p.student_id=? ORDER BY p.id DESC LIMIT 6', [$sid]),
                'recap' => last_session_recap($sid), 'blockersUnseen' => blockers_unseen($sid),
            ]);

        case 't_student':
            $tab = $_GET['tab'] ?? 'profil';
            $an = latest_analysis($sid);
            $prio = effective_priorities($sid, $an);
            $data = load_answers($sid);
            render('t_student', ['s' => $s, 'tab' => $tab, 'an' => $an, 'prio' => $prio, 'reco' => recommendations($prio['axes'], $an), 'data' => $data,
                'users' => family_users((int)$s['family_id']),
                'obs' => all('SELECT o.*, u.display_name author FROM tutor_observations o LEFT JOIN users u ON u.id=o.author_id WHERE o.student_id=? ORDER BY o.id DESC', [$sid]),
                'sessions' => all('SELECT * FROM sessions WHERE student_id=? ORDER BY starts_at', [$sid]),
                'goals' => all('SELECT * FROM goals WHERE student_id=? ORDER BY FIELD(status,"in_progress","rework","todo","acquired"), target_date', [$sid]),
                'entries' => all('SELECT p.*, g.title goal FROM progress_entries p LEFT JOIN goals g ON g.id=p.goal_id WHERE p.student_id=? ORDER BY p.id DESC LIMIT 30', [$sid]),
                'subjects' => $tab === 'matieres' ? subjects_of($sid) : [],
                'suggestions' => $tab === 'matieres' && subjects_available() ? subject_suggestions($sid) : [],
                'files' => $tab === 'matieres' ? student_files($sid, null, 40) : [],
                'blockers' => $tab === 'matieres' ? student_blockers($sid, null, 30) : [],
            ]);

        case 't_rdv':
            $an = latest_analysis($sid);
            $prio = effective_priorities($sid, $an);
            render('t_rdv', ['s' => $s, 'an' => $an, 'prio' => $prio, 'reco' => recommendations($prio['axes'], $an),
                'users' => family_users((int)$s['family_id']), 'subs' => ['parent' => qn_latest('parent', $sid), 'student' => qn_latest('student', $sid)],
                'obs' => all('SELECT * FROM tutor_observations WHERE student_id=? ORDER BY id DESC', [$sid]),
                'nSessions' => (int)one('SELECT COUNT(*) n FROM sessions WHERE student_id=?', [$sid])['n']]);

        case 't_messages':
            $ch = (($_POST['ch'] ?? $_GET['ch'] ?? '') === 'student' && messages_have_channels()) ? 'student' : 'family';
            if ($isPost) {
                if (send_message((int)$s['family_id'], $u, (string)($_POST['body'] ?? ''), $ch)) { audit('message_sent', $ch, (int)$s['family_id']); flash('Message envoyé.'); }
                redirect('t_messages', ['sid' => $sid] + ($ch === 'student' ? ['ch' => 'student'] : []));
            }
            mark_messages_read((int)$s['family_id'], $u, $ch);
            $threads = [];
            foreach (students_of($u) as $x) {
                $last = one('SELECT body, created_at FROM messages WHERE family_id=? ORDER BY id DESC LIMIT 1', [$x['family_id']]);
                $threads[] = ['s' => $x, 'unread' => unread_count($u, (int)$x['family_id']), 'last' => $last];
            }
            usort($threads, fn($a, $b) => ($b['unread'] <=> $a['unread']) ?: strcmp($b['last']['created_at'] ?? '', $a['last']['created_at'] ?? ''));
            $parent = one('SELECT u.id, u.display_name, u.phone, u.email, u.avatar FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE u.role="parent" ORDER BY u.id LIMIT 1', [$s['family_id']]);
            $studentU = $s['user_id'] ? one('SELECT id, display_name, avatar FROM users WHERE id=?', [$s['user_id']]) : null;
            render('t_messages', ['s' => $s, 'threads' => $threads, 'messages' => family_messages((int)$s['family_id'], $ch), 'parent' => $parent, 'ch' => $ch, 'studentU' => $studentU,
                'unreadCh' => ['family' => unread_count($u, (int)$s['family_id'], 'family'), 'student' => messages_have_channels() ? unread_count($u, (int)$s['family_id'], 'student') : 0]]);

        case 't_subjects':
            if (!$isPost || !subjects_available()) redirect('t_student', ['sid' => $sid, 'tab' => 'matieres']);
            $act = (string)($_POST['action'] ?? '');
            $subject = post_int('subject_id') ? subject_of_student(post_int('subject_id'), $sid) : null;
            $chapter = post_int('chapter_id') ? chapter_of_student(post_int('chapter_id'), $sid) : null;
            $anchor = $subject ? '#subj-' . $subject['id'] : ($chapter ? '#subj-' . $chapter['subject_id'] : '');
            switch ($act) {
                case 'subject_add':
                    $name = post_str('name', 80) ?: post_str('custom', 80);
                    if ($name !== '') { q('INSERT IGNORE INTO student_subjects (student_id, name, position) VALUES (?,?,?)', [$sid, $name, (int)(one('SELECT COUNT(*) n FROM student_subjects WHERE student_id=?', [$sid])['n'] ?? 0)]); flash("Matière « $name » ajoutée."); }
                    break;
                case 'subject_del':
                    if ($subject) { q('DELETE FROM student_subjects WHERE id=?', [$subject['id']]); flash('Matière retirée.'); $anchor = ''; }
                    break;
                case 'grade_add':
                    if ($subject) { $err = grade_add_from_post($subject, $u); flash($err ?? 'Note ajoutée.', $err ? 'err' : 'ok'); $anchor = '#notes-' . $subject['id']; }
                    break;
                case 'grade_del':
                    q('DELETE g FROM subject_grades g JOIN student_subjects ss ON ss.id=g.subject_id WHERE g.id=? AND ss.student_id=?', [post_int('grade_id'), $sid]);
                    flash('Note supprimée.'); if ($subject) $anchor = '#notes-' . $subject['id'];
                    break;
                case 'chapter_add':
                    if ($subject && post_str('title', 160) !== '') {
                        $st = post_enum('status', CHAPTER_STATUS, 'in_progress');
                        q('INSERT INTO subject_chapters (subject_id, title, status, is_current) VALUES (?,?,?,?)', [$subject['id'], post_str('title', 160), $st, (int)($st === 'in_progress')]);
                        flash('Chapitre ajouté.');
                    }
                    break;
                case 'chapter_save':
                    if ($chapter) {
                        // le chapitre « en cours » est celui dont le niveau est « En cours »
                        $st = post_enum('status', CHAPTER_STATUS, $chapter['status']);
                        q('UPDATE subject_chapters SET title=?, status=?, is_current=? WHERE id=?', [post_str('title', 160) ?: $chapter['title'], $st, (int)($st === 'in_progress'), $chapter['id']]);
                        flash('Chapitre mis à jour.');
                    }
                    break;
                case 'chapter_del':
                    if ($chapter) { q('DELETE FROM subject_chapters WHERE id=?', [$chapter['id']]); flash('Chapitre supprimé.'); }
                    break;
                case 'question_add':
                    if (!$chapter) break;
                    $parsed = quiz_options_from_post();
                    if (is_string($parsed)) flash($parsed, 'err');
                    else {
                        q('INSERT INTO chapter_questions (chapter_id, question, options_json, answer, explanation) VALUES (?,?,?,?,?)', [$chapter['id'], post_str('question', 500), json_encode($parsed[0], JSON_UNESCAPED_UNICODE), $parsed[1], post_str('explanation', 500) ?: null]);
                        flash('Question ajoutée au quiz.');
                    }
                    $anchor = '#quiz-' . $chapter['id'];
                    break;
                case 'question_update':
                    $qq = one('SELECT cq.* FROM chapter_questions cq JOIN subject_chapters c ON c.id=cq.chapter_id JOIN student_subjects ss ON ss.id=c.subject_id WHERE cq.id=? AND ss.student_id=?', [post_int('question_id'), $sid]);
                    if (!$qq) break;
                    $parsed = quiz_options_from_post();
                    if (is_string($parsed)) flash($parsed, 'err');
                    else {
                        q('UPDATE chapter_questions SET question=?, options_json=?, answer=?, explanation=? WHERE id=?', [post_str('question', 500), json_encode($parsed[0], JSON_UNESCAPED_UNICODE), $parsed[1], post_str('explanation', 500) ?: null, $qq['id']]);
                        flash('Question modifiée.');
                    }
                    $anchor = '#quiz-' . $qq['chapter_id'];
                    break;
                case 'question_ai':
                    if (!$chapter) break;
                    $existing = array_column(chapter_questions((int)$chapter['id']), 'question');
                    $res = ai_generate_quiz($chapter['subject'], $chapter['title'], $s['grade'] ?? null, max(1, min(10, post_int('count') ?: 5)), post_str('focus', 300), $existing);
                    if (isset($res['error'])) flash($res['error'], 'err');
                    else {
                        foreach ($res['questions'] as $g) q('INSERT INTO chapter_questions (chapter_id, question, options_json, answer, explanation, source) VALUES (?,?,?,?,?,"ai")', [$chapter['id'], $g['question'], json_encode($g['options'], JSON_UNESCAPED_UNICODE), $g['answer'], $g['explanation'] ?: null]);
                        audit('quiz_ai_generated', count($res['questions']) . ' question(s)', (int)$s['family_id']);
                        flash(count($res['questions']) . ' question(s) générée(s) par l\'IA. Relisez-les : vous pouvez les modifier ou les supprimer.');
                    }
                    $anchor = '#quiz-' . $chapter['id'];
                    break;
                case 'question_del':
                    q('DELETE cq FROM chapter_questions cq JOIN subject_chapters c ON c.id=cq.chapter_id JOIN student_subjects ss ON ss.id=c.subject_id WHERE cq.id=? AND ss.student_id=?', [post_int('question_id'), $sid]);
                    flash('Question supprimée.'); if ($chapter) $anchor = '#quiz-' . $chapter['id'];
                    break;
                case 'blocker_seen':
                    q('UPDATE subject_blockers SET seen_at=NOW() WHERE id=? AND student_id=?', [post_int('blocker_id'), $sid]);
                    $anchor = '#blocages';
                    break;
                case 'file_upload':
                    $err = save_student_file($sid, $subject['id'] ?? null, $u, $_FILES['file'] ?? [], post_str('note', 300));
                    flash($err ?? 'Fichier ajouté.', $err ? 'err' : 'ok'); $anchor = '#fichiers';
                    break;
                case 'file_del':
                    $f = one('SELECT * FROM student_files WHERE id=? AND student_id=?', [post_int('file_id'), $sid]);
                    if ($f) { delete_student_file($f); flash('Fichier supprimé.'); }
                    $anchor = '#fichiers';
                    break;
            }
            header('Location: ' . url('t_student', ['sid' => $sid, 'tab' => 'matieres']) . $anchor, true, 303);
            exit;

        case 't_report':
            if (!$isPost) $back();
            $se = one('SELECT * FROM sessions WHERE id=? AND student_id=?', [post_int('session_id'), $sid]);
            if (!$se) { http_response_code(403); render('error', ['msg' => 'Séance introuvable.']); }
            $send = !empty($_POST['send']);
            $summary = post_str('parent_summary', 3000);
            if ($send && $summary === '') { flash('Écrivez le bilan pour le parent avant de l\'envoyer.', 'err'); redirect('t_student', ['sid' => $sid, 'tab' => 'seances']); }
            $subj = array_map(fn($x) => mb_substr(trim((string)$x), 0, 60), (array)($_POST['subjects'] ?? []));
            foreach (preg_split('/[,;]/', post_str('subjects_other', 200)) as $x) $subj[] = mb_substr(trim($x), 0, 60);
            $subj = array_values(array_unique(array_filter($subj, fn($x) => $x !== '')));
            $subjJson = $subj ? json_encode($subj, JSON_UNESCAPED_UNICODE) : null;
            try {
                q('INSERT INTO session_reports (session_id, subjects_json, worked_on, tutor_note, parent_summary, next_focus) VALUES (?,?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE subjects_json=VALUES(subjects_json), worked_on=VALUES(worked_on), tutor_note=VALUES(tutor_note), parent_summary=VALUES(parent_summary), next_focus=VALUES(next_focus)',
                    [$se['id'], $subjJson, post_str('worked_on', 3000) ?: null, post_str('tutor_note', 3000) ?: null, $summary ?: null, post_str('next_focus', 500) ?: null]);
            } catch (PDOException) {
                q('INSERT INTO session_reports (session_id, worked_on, tutor_note, parent_summary, next_focus) VALUES (?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE worked_on=VALUES(worked_on), tutor_note=VALUES(tutor_note), parent_summary=VALUES(parent_summary), next_focus=VALUES(next_focus)',
                    [$se['id'], post_str('worked_on', 3000) ?: null, post_str('tutor_note', 3000) ?: null, $summary ?: null, post_str('next_focus', 500) ?: null]);
            }
            if ($se['status'] === 'planned' && strtotime($se['starts_at']) < time()) q('UPDATE sessions SET status="done" WHERE id=?', [$se['id']]);
            if ($send) {
                $next = post_str('next_focus', 500);
                send_message((int)$s['family_id'], $u, 'Bilan de la séance du ' . fdatetime($se['starts_at']) . " :\n\n" . ($subj ? 'Matières abordées : ' . implode(', ', $subj) . "\n\n" : '') . $summary . ($next !== '' ? "\n\nPour la prochaine séance : " . $next : ''));
                q('UPDATE session_reports SET sent_at=NOW() WHERE session_id=?', [$se['id']]);
                audit('session_report_sent', null, (int)$s['family_id']);
                flash('Bilan enregistré et envoyé au parent (visible dans « Échanges » et son suivi).');
            } else flash('Bilan enregistré.');
            redirect('t_student', ['sid' => $sid, 'tab' => 'seances']);

        case 't_monthly':
            if (!$isPost) $back();
            $month = preg_match('/^\d{4}-\d{2}$/', $_POST['month'] ?? '') ? $_POST['month'] : date('Y-m');
            $summary = post_str('summary', 6000);
            $publish = !empty($_POST['publish']);
            q('INSERT INTO monthly_reports (student_id, month, summary) VALUES (?,?,?) ON DUPLICATE KEY UPDATE summary=VALUES(summary)', [$sid, $month, $summary ?: null]);
            if ($publish && $summary !== '') {
                q('UPDATE monthly_reports SET published_at=NOW() WHERE student_id=? AND month=?', [$sid, $month]);
                send_message((int)$s['family_id'], $u, 'Bilan du mois — ' . month_label($month) . " :\n\n" . $summary);
                audit('monthly_report_published', $month, (int)$s['family_id']);
                flash('Bilan mensuel publié pour la famille.');
            } else flash('Brouillon du bilan mensuel enregistré.');
            redirect('t_student', ['sid' => $sid, 'tab' => 'bilans', 'month' => $month]);

        case 't_synthesis':
            render('t_synthesis', ['s' => $s] + family_view_data($s) + ['an' => latest_analysis($sid)], 'layout_min');

        case 't_passation':
            if (!$isPost) $back();
            $code = post_enum('code', ['parent' => 1, 'student' => 1], 'parent');
            $target = one('SELECT u.* FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE u.role=? AND u.is_active=1'
                . ($code === 'student' ? ' AND u.id=?' : ' AND ?=?'), $code === 'student' ? [$s['family_id'], 'student', $s['user_id']] : [$s['family_id'], 'parent', 1, 1]);
            if (!$target) { flash('Aucun compte ' . ($code === 'parent' ? 'parent' : 'élève') . ' actif pour ce dossier.', 'err'); redirect('t_rdv', ['sid' => $sid]); }
            if ($code === 'student' && !$s['guardian_consent_at']) { flash("L'autorisation du représentant légal doit d'abord être validée (questionnaire parent).", 'err'); redirect('t_rdv', ['sid' => $sid]); }
            audit('passation_start', "$code user={$target['id']}", (int)$s['family_id']);
            $tutorUsername = $u['username'];
            // Ferme la session tutrice : l'appareil n'a plus accès à l'espace administrateur.
            logout();
            $_SESSION = ['passation' => ['user_id' => (int)$target['id'], 'student_id' => $sid, 'code' => $code, 'expires' => time() + 5400, 'tutor_username' => $tutorUsername], 'last' => time()];
            redirect('q');

        case 't_analyze':
            if ($isPost) { run_analysis($sid, (int)$u['id']); audit('analysis_recomputed', null, (int)$s['family_id']); flash('Analyse recalculée.'); }
            $back('analyse');

        case 't_priorities':
            if (!$isPost) $back();
            q('DELETE FROM assessment_priorities WHERE student_id=?', [$sid]);
            if (empty($_POST['reset'])) {
                $rank = 1;
                $seen = [];
                foreach ((array)($_POST['axes'] ?? []) as $i => $a) {
                    if (!is_string($a) || !isset(AXES[$a]) || isset($seen[$a]) || $rank > 3) continue;
                    $seen[$a] = true;
                    q('INSERT INTO assessment_priorities (student_id, axis, rank_no, note, updated_by) VALUES (?,?,?,?,?)', [$sid, $a, $rank++, mb_substr((string)($_POST['note'][$i] ?? ''), 0, 500) ?: null, $u['id']]);
                }
                flash('Priorités enregistrées.');
            } else flash('Priorités automatiques rétablies.');
            audit('priorities_changed', null, (int)$s['family_id']);
            $back('analyse');

        case 't_observation':
            if (!$isPost) $back();
            if (!empty($_POST['delete_id'])) {
                q('DELETE FROM tutor_observations WHERE id=? AND student_id=?', [post_int('delete_id'), $sid]);
                run_analysis($sid, (int)$u['id']);
                flash('Observation supprimée.');
            } elseif (($content = post_str('content', 3000)) !== '') {
                $axis = isset(AXES[$_POST['axis'] ?? '']) ? $_POST['axis'] : null;
                $adj = $axis ? max(-30, min(30, post_int('adjustment'))) : 0;
                q('INSERT INTO tutor_observations (student_id, author_id, axis, adjustment, content, visibility) VALUES (?,?,?,?,?,?)',
                    [$sid, $u['id'], $axis, $adj, $content, post_enum('visibility', ['private' => 1, 'shared' => 1], 'private')]);
                if ($adj) run_analysis($sid, (int)$u['id']);
                flash('Observation ajoutée' . ($adj ? ' - analyse recalculée.' : '.'));
            }
            $back('analyse');

        case 't_schedule':
            if (!$isPost || !schedule_available()) redirect('t_student', ['sid' => $sid, 'tab' => 'seances']);
            [$sched, $err] = schedule_from_post();
            if ($err) { flash($err, 'err'); redirect('t_student', ['sid' => $sid, 'tab' => 'seances']); }
            q('UPDATE students SET schedule_json=? WHERE id=?', [json_encode($sched, JSON_UNESCAPED_UNICODE), $sid]);
            $msg = 'Rythme enregistré : ' . fdur(schedule_minutes_per_week($sched)) . ' par semaine.';
            if (!empty($_POST['generate'])) {
                $n = schedule_generate($s, $u, $sched, !empty($_POST['replace']), !empty($_POST['with_goals']));
                $msg .= " $n séance" . ($n > 1 ? 's' : '') . ' ajoutée' . ($n > 1 ? 's' : '') . ' au planning.';
            }
            if (!empty($_POST['notify'])) {
                send_message((int)$s['family_id'], $u, "Voici le rythme des séances de {$s['first_name']}, comme convenu :\n\n• " . implode("\n• ", schedule_lines($sched))
                    . "\n\nSoit " . fdur(schedule_minutes_per_week($sched)) . ' par semaine, pendant ' . (int)$sched['weeks'] . ' semaine' . ((int)$sched['weeks'] > 1 ? 's' : '') . ', à partir du ' . fdate($sched['start']) . '.'
                    . ($sched['note'] !== '' ? "\n\nÀ noter : " . $sched['note'] : '') . "\n\nToutes les séances sont visibles dans « Planning ».");
                $msg .= ' Le parent a reçu le récapitulatif.';
            }
            audit('schedule_saved', null, (int)$s['family_id']);
            flash($msg);
            header('Location: ' . url('t_student', ['sid' => $sid, 'tab' => 'seances']) . '#rythme', true, 303);
            exit;

        case 't_plan':
            if (!$isPost) $back();
            generate_plan($s, $u, (string)($_POST['start'] ?? ''), !empty($_POST['replace']), !empty($_POST['with_goals']));
            $back('seances');

        case 't_session':
            if (!$isPost) $back();
            save_session($sid);
            $back('seances');

        case 't_goal':
            if (!$isPost) $back();
            save_goal($sid, (int)$u['id']);
            $back('objectifs');

        case 't_shared':
            if (!$isPost) $back();
            q('UPDATE students SET shared_message=?, shared_strengths=?, shared_to_work=?, shared_updated_at=NOW() WHERE id=?',
                [post_str('shared_message', 3000) ?: null, post_str('shared_strengths', 3000) ?: null, post_str('shared_to_work', 3000) ?: null, $sid]);
            flash('Bilan partagé mis à jour (visible par le parent et l\'élève).');
            $back('profil');

        case 't_profile':
            if (!$isPost) $back();
            q('UPDATE students SET first_name=?, grade=?, school=? WHERE id=?', [post_str('first_name', 80) ?: $s['first_name'], post_str('grade', 30) ?: '3e', post_str('school', 120) ?: null, $sid]);
            if (isset($_POST['birth_date']) || isset($_POST['last_name'])) {
                $bd = post_str('birth_date', 10); $bd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd) && strtotime($bd) < time() ? $bd : null;
                try { q('UPDATE students SET last_name=?, birth_date=? WHERE id=?', [post_str('last_name', 80) ?: null, $bd, $sid]); } catch (PDOException) {}
            }
            flash('Profil mis à jour.');
            $back('profil');

        case 't_contacts':
            if (!$isPost) $back();
            $email = function (string $k): ?string {
                $v = post_str($k, 120);
                if ($v === '') return null;
                if (!filter_var($v, FILTER_VALIDATE_EMAIL)) { flash('Adresse e-mail invalide : ' . $v, 'err'); return '__invalid'; }
                return $v;
            };
            $phone = fn(string $k) => ($v = preg_replace('/[^0-9+ ().-]/', '', post_str($k, 30))) !== '' ? $v : null;
            if (($_POST['who'] ?? '') === 'parent') {
                $e = $email('parent_email');
                if ($e !== '__invalid') {
                    q('UPDATE students SET parent_phone=?, parent_email=?, parent_address=?, contact_notes=? WHERE id=?',
                        [$phone('parent_phone'), $e, post_str('parent_address', 255) ?: null, post_str('contact_notes', 500) ?: null, $sid]);
                    flash('Coordonnées du parent enregistrées.');
                }
            } else {
                $e = $email('student_email');
                if ($e !== '__invalid') {
                    q('UPDATE students SET student_phone=?, student_email=? WHERE id=?', [$phone('student_phone'), $e, $sid]);
                    flash('Coordonnées de l\'élève enregistrées.');
                }
            }
            audit('contacts_updated', null, (int)$s['family_id']);
            redirect('t_student', ['sid' => $sid, 'tab' => 'profil'] + (($_POST['who'] ?? '') === 'parent' ? ['pane' => 'parent'] : []));

        case 't_export':
            audit('export', null, (int)$s['family_id']);
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="export-' . preg_replace('/[^a-z0-9]/i', '', $s['first_name']) . '-' . date('Ymd') . '.json"');
            echo json_encode(export_student($s), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        case 't_delete_family':
            if (!$isPost) $back();
            if (post_str('confirm') !== 'SUPPRIMER') { flash('Tapez SUPPRIMER pour confirmer.', 'err'); redirect('t_users'); }
            $fid = (int)$s['family_id'];
            $uids = array_column(all('SELECT user_id FROM family_members WHERE family_id=? AND member_role<>"tutor"', [$fid]), 'user_id');
            db()->beginTransaction();
            q('DELETE FROM families WHERE id=?', [$fid]); // cascade : élèves, réponses, objectifs, séances…
            foreach ($uids as $id) {
                if (!one('SELECT 1 FROM family_members WHERE user_id=?', [$id])) q('DELETE FROM users WHERE id=?', [$id]);
            }
            db()->commit();
            audit('family_deleted', "family=$fid");
            flash('Dossier et comptes associés supprimés définitivement.');
            redirect('t_users');
    }
    http_response_code(404);
    render('error', ['msg' => 'Page introuvable.']);
}

function create_family(array $tutor): void
{
    require_once APP_ROOT . '/app/invitations.php';
    $modes = ['parent' => post_enum('parent_mode', ['create' => 1, 'invite' => 1], 'create'), 'student' => post_enum('student_mode', ['create' => 1, 'invite' => 1], 'create')];
    $v = [];
    foreach (['family_name', 'student_first_name', 'parent_name', 'parent_username', 'student_username', 'parent_email', 'student_email'] as $f) $v[$f] = post_str($f, 120);
    foreach (['parent_username', 'student_username', 'parent_email', 'student_email'] as $f) $v[$f] = mb_strtolower($v[$f]);
    $v['parent_mode'] = $modes['parent']; $v['student_mode'] = $modes['student'];
    $pw = ['parent' => (string)($_POST['parent_password'] ?? ''), 'student' => (string)($_POST['student_password'] ?? '')];
    $quick = post_enum('quick_invite', ['parent' => 1, 'student' => 1], '');
    if ($quick) {
        // Bouton « Envoyer » : seul l'e-mail de cette personne est obligatoire, le reste peut être complété plus tard
        $modes[$quick] = 'invite';
        $other = $quick === 'parent' ? 'student' : 'parent';
        $filled = $modes[$other] === 'invite' ? $v["{$other}_email"] !== '' : ($v["{$other}_username"] !== '' && $pw[$other] !== '');
        if (!$filled) $modes[$other] = 'none';
        if ($v['student_first_name'] === '') $v['student_first_name'] = 'Élève (à compléter)';
        if ($v['family_name'] === '') $v['family_name'] = 'Dossier ' . (strstr($v["{$quick}_email"], '@', true) ?: 'sans nom');
        $v['parent_mode'] = $modes['parent']; $v['student_mode'] = $modes['student'];
    }

    $err = null;
    foreach (['family_name' => 'le nom du dossier', 'student_first_name' => 'le prénom de l\'élève'] as $f => $l) if ($v[$f] === '') $err ??= "Renseignez $l.";
    if ($modes['parent'] === 'create' && $v['parent_name'] === '') $err ??= 'Renseignez le nom du parent.';
    $usernames = [];
    foreach (['parent', 'student'] as $who) {
        $label = $who === 'parent' ? 'parent' : 'élève';
        if ($modes[$who] === 'none') continue;
        if ($modes[$who] === 'create') {
            $err ??= validate_username($v["{$who}_username"]) ? "Identifiant $label : 3 à 60 caractères (minuscules, chiffres, . _ -)." : null;
            $err ??= validate_password($pw[$who]) ? "Mot de passe $label : " . lcfirst(validate_password($pw[$who])) : null;
            $usernames[] = $v["{$who}_username"];
        } else {
            $err ??= filter_var($v["{$who}_email"], FILTER_VALIDATE_EMAIL) ? null : "Adresse e-mail $label invalide.";
        }
    }
    if (!$err && count($usernames) === 2 && $usernames[0] === $usernames[1]) $err = 'Les deux identifiants doivent être différents.';
    if (!$err && $usernames && one('SELECT 1 FROM users WHERE username IN (' . implode(',', array_fill(0, count($usernames), '?')) . ')', $usernames)) $err = 'Un des identifiants est déjà utilisé.';
    if ($err) { flash($err, 'err'); $_SESSION['old'] = $v; redirect('t_users'); }

    $pdo = db();
    $pdo->beginTransaction();
    q('INSERT INTO families (name, is_demo) VALUES (?, 0)', [$v['family_name']]);
    $fid = (int)$pdo->lastInsertId();
    q('INSERT INTO family_members (family_id, user_id, member_role) VALUES (?,?,?)', [$fid, $tutor['id'], 'tutor']);
    $ids = [];
    foreach (['parent', 'student'] as $who) {
        if ($modes[$who] !== 'create') continue;
        q('INSERT INTO users (username, display_name, role, password_hash) VALUES (?,?,?,?)', [$v["{$who}_username"], $who === 'parent' ? $v['parent_name'] : $v['student_first_name'], $who, password_hash($pw[$who], PASSWORD_DEFAULT)]);
        $ids[$who] = (int)$pdo->lastInsertId();
        q('INSERT INTO family_members (family_id, user_id, member_role) VALUES (?,?,?)', [$fid, $ids[$who], $who]);
    }
    q('INSERT INTO students (family_id, user_id, first_name, grade, school) VALUES (?,?,?,?,?)', [$fid, $ids['student'] ?? null, $v['student_first_name'], post_str('grade', 30) ?: '3e', post_str('school', 120) ?: null]);
    $sid = (int)$pdo->lastInsertId();
    $pdo->commit();

    $msgs = []; $invites = [];
    foreach (['parent', 'student'] as $who) {
        if ($modes[$who] === 'none') { $msgs[] = 'compte ' . ($who === 'parent' ? 'parent' : 'élève') . ' à ajouter plus tard'; continue; }
        if ($modes[$who] === 'create') { $msgs[] = 'compte « ' . $v["{$who}_username"] . ' » créé'; continue; }
        $invites[] = create_invitation($fid, $sid, $who, $v["{$who}_email"], $who === 'parent' ? '' : $v['student_first_name'], (int)$tutor['id']); // le parent saisit son nom
        $msgs[] = 'invitation ' . ($who === 'parent' ? 'parent' : 'élève') . ' préparée';
    }
    if ($invites) $_SESSION['invite_result'] = $invites;
    unset($_SESSION['old']);
    audit('family_created', $v['family_name'] . ' (' . $modes['parent'] . '/' . $modes['student'] . ')', $fid);
    flash('Dossier créé : ' . implode(', ', $msgs) . '.');
    redirect('t_rdv', ['sid' => $sid]);
}

/** Renvoyer (nouveau lien) ou annuler une invitation d'un dossier de la tutrice. */
function manage_invitation(array $tutor): never
{
    require_once APP_ROOT . '/app/invitations.php';
    $inv = one('SELECT i.* FROM invitations i JOIN family_members fm ON fm.family_id=i.family_id AND fm.user_id=? AND fm.member_role="tutor" WHERE i.id=? AND i.used_at IS NULL', [$tutor['id'], post_int('invite_id')]);
    if (!$inv) { http_response_code(403); render('error', ['msg' => 'Invitation introuvable.']); }
    q('UPDATE invitations SET expires_at=NOW() WHERE id=?', [$inv['id']]); // l'ancien lien ne fonctionne plus
    if (($_POST['do'] ?? '') === 'resend') {
        $email = post_str('email', 120) ?: $inv['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Adresse e-mail invalide.', 'err'); redirect('t_users', ['open' => $inv['family_id']]); }
        $_SESSION['invite_result'] = [create_invitation((int)$inv['family_id'], (int)$inv['student_id'], $inv['role'], $email, $inv['display_name'], (int)$tutor['id'])];
        q('DELETE FROM invitations WHERE id=?', [$inv['id']]);
        audit('invitation_resent', $inv['role'], (int)$inv['family_id']);
        flash('Nouveau lien d\'invitation créé.');
    } else {
        q('DELETE FROM invitations WHERE id=?', [$inv['id']]);
        audit('invitation_cancelled', $inv['role'], (int)$inv['family_id']);
        flash('Invitation annulée.');
    }
    redirect('t_users', ['open' => $inv['family_id']]);
}

function generate_plan(array $s, array $u, string $start, bool $replace, bool $withGoals): void
{
    $sid = (int)$s['id'];
    $d = DateTime::createFromFormat('Y-m-d', $start) ?: new DateTime('next monday');
    $d->setTime(0, 0);
    if ($replace) q('DELETE FROM sessions WHERE student_id=? AND week_no IS NOT NULL AND status="planned"', [$sid]);
    $an = latest_analysis($sid);
    $prio = effective_priorities($sid, $an);
    $reco = recommendations($prio['axes'], $an);
    $slots = [[0, '17:00'], [2, '14:00'], [5, '10:00']]; // lun., mer., sam.
    $n = 0;
    foreach (plan_weeks() as $w => $pw) {
        foreach ($pw['slots'] as $i => [$mode, $dur]) {
            $dt = (clone $d)->modify('+' . (($w - 1) * 7 + $slots[$i][0]) . ' days');
            [$h, $m] = explode(':', $slots[$i][1]);
            $dt->setTime((int)$h, (int)$m);
            $obj = '• ' . $pw['title'] . ($reco['plan'][$w]['items'] ?? [] ? "\n• " . implode("\n• ", $reco['plan'][$w]['items']) : '');
            q('INSERT INTO sessions (student_id, week_no, title, starts_at, duration_min, mode, objectives) VALUES (?,?,?,?,?,?,?)',
                [$sid, $w, $pw['title'], $dt->format('Y-m-d H:i:s'), $dur, $mode, $obj]);
            $n++;
        }
    }
    $g = 0;
    if ($withGoals) {
        $target = (clone $d)->modify('+27 days')->format('Y-m-d');
        foreach ($reco['goals'] as $i => $goal) {
            if (one('SELECT 1 FROM goals WHERE student_id=? AND title=?', [$sid, $goal['title']])) continue;
            q('INSERT INTO goals (student_id, title, description, category, target_date, is_mission, created_by) VALUES (?,?,?,?,?,?,?)',
                [$sid, $goal['title'], $goal['description'], $goal['category'], $target, $i % 2 === 0 ? 1 : 0, $u['id']]);
            $g++;
        }
    }
    audit('plan_generated', "$n séances, $g objectifs", (int)$s['family_id']);
    flash("Plan initial généré : $n séances" . ($withGoals ? ", $g objectifs" : '') . '. Tout reste modifiable.');
}

function save_session(int $sid): void
{
    $id = post_int('id');
    if ($id && !one('SELECT 1 FROM sessions WHERE id=? AND student_id=?', [$id, $sid])) { http_response_code(403); exit('Accès refusé'); }
    if (!empty($_POST['delete'])) { q('DELETE FROM sessions WHERE id=? AND student_id=?', [$id, $sid]); flash('Séance supprimée.'); return; }
    $dt = DateTime::createFromFormat('Y-m-d H:i', post_str('date') . ' ' . post_str('time'));
    if (!$dt) { flash('Date ou heure invalide.', 'err'); return; }
    $vals = [post_str('title', 150) ?: 'Séance', $dt->format('Y-m-d H:i:00'), max(15, min(600, post_int('duration_min') ?: 120)),
        post_enum('mode', MODES, 'presentiel'), post_enum('status', SESSION_STATUS, 'planned'), post_str('objectives', 3000) ?: null];
    if ($id) q('UPDATE sessions SET title=?, starts_at=?, duration_min=?, mode=?, status=?, objectives=? WHERE id=? AND student_id=?', [...$vals, $id, $sid]);
    else q('INSERT INTO sessions (title, starts_at, duration_min, mode, status, objectives, student_id) VALUES (?,?,?,?,?,?,?)', [...$vals, $sid]);
    $status = $vals[4]; $extra = '';
    try {
        $sessId = $id ?: (int)db()->lastInsertId();
        q('UPDATE sessions SET status_reason=? WHERE id=?', [in_array($status, ['postponed', 'cancelled'], true) ? (post_str('status_reason', 500) ?: null) : null, $sessId]);
        if ($id && $status === 'postponed') { // nouvelles dates : une séance par créneau (report découpable)
            $orig = one('SELECT * FROM sessions WHERE id=?', [$id]);
            $dates = (array)($_POST['rs_date'] ?? []); $times = (array)($_POST['rs_time'] ?? []); $durs = (array)($_POST['rs_dur'] ?? []); $modes = (array)($_POST['rs_mode'] ?? []);
            $n = 0;
            foreach ($dates as $i => $d) {
                $ndt = DateTime::createFromFormat('Y-m-d H:i', (string)$d . ' ' . (string)($times[$i] ?? ''));
                if (!$ndt) continue;
                $rsDur = max(15, min(600, (int)($durs[$i] ?? 0) ?: (int)$orig['duration_min']));
                if (merge_report_into_day($sid, $ndt, $rsDur, $id)) { $merged = ($merged ?? 0) + 1; continue; }
                q('INSERT INTO sessions (student_id, week_no, title, starts_at, duration_min, mode, status, objectives, rescheduled_from) VALUES (?,?,?,?,?,?,"planned",?,?)',
                    [$sid, $orig['week_no'], session_base_title($orig['title']), $ndt->format('Y-m-d H:i:00'), max(15, min(600, (int)($durs[$i] ?? 0) ?: (int)$orig['duration_min'])),
                     isset(MODES[$modes[$i] ?? '']) ? $modes[$i] : $orig['mode'], $orig['objectives'], $id]);
                $n++;
            }
            if ($n) $extra = " $n nouveau" . ($n > 1 ? 'x' : '') . ' créneau' . ($n > 1 ? 'x' : '') . ' ajouté' . ($n > 1 ? 's' : '') . ' au planning.';
            if (!empty($merged)) $extra .= ' ' . $merged . ' créneau' . ($merged > 1 ? 'x' : '') . ' ajouté' . ($merged > 1 ? 's' : '') . ' à une séance déjà prévue le même jour (séance prolongée).';
        }
    } catch (PDOException) { /* base pas encore migrée */ }
    if (($note = post_str('note', 3000)) !== '' && $id) q('INSERT INTO session_notes (session_id, author_id, content, visibility) VALUES (?,?,?,?)', [$id, current_user()['id'], $note, post_enum('note_visibility', ['private' => 1, 'shared' => 1], 'private')]);
    flash(($id ? 'Séance mise à jour.' : 'Séance ajoutée.') . $extra);
}

function save_goal(int $sid, int $uid): void
{
    $id = post_int('id');
    if ($id && !one('SELECT 1 FROM goals WHERE id=? AND student_id=?', [$id, $sid])) { http_response_code(403); exit('Accès refusé'); }
    if (!empty($_POST['delete'])) { q('DELETE FROM goals WHERE id=? AND student_id=?', [$id, $sid]); flash('Objectif supprimé.'); return; }
    $title = post_str('title', 200);
    if ($title === '') { flash('Le titre est obligatoire.', 'err'); return; }
    $td = post_str('target_date');
    $status = post_enum('status', GOAL_STATUS, 'todo');
    $vals = [$title, post_str('description', 3000) ?: null, post_enum('category', AXES, 'methodo'), preg_match('/^\d{4}-\d{2}-\d{2}$/', $td) ? $td : null,
        $status, $status === 'acquired' ? 100 : max(0, min(100, post_int('progress'))), post_str('tutor_comment', 3000) ?: null, (int)!empty($_POST['is_mission'])];
    if ($id) q('UPDATE goals SET title=?, description=?, category=?, target_date=?, status=?, progress=?, tutor_comment=?, is_mission=? WHERE id=? AND student_id=?', [...$vals, $id, $sid]);
    else { q('INSERT INTO goals (title, description, category, target_date, status, progress, tutor_comment, is_mission, student_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)', [...$vals, $sid, $uid]); $id = (int)db()->lastInsertId(); }
    try { q('UPDATE goals SET horizon=? WHERE id=? AND student_id=?', [!empty($_POST['is_year']) ? 'year' : 'month', $id, $sid]); } catch (PDOException) { /* base pas encore migrée */ }
    flash($id ? 'Objectif mis à jour.' : 'Objectif ajouté.');
}

function export_student(array $s): array
{
    $sid = $s['id'];
    $subs = all('SELECT qs.id, qn.code, qs.status, qs.started_at, qs.completed_at, u.username FROM questionnaire_submissions qs JOIN questionnaires qn ON qn.id=qs.questionnaire_id JOIN users u ON u.id=qs.user_id WHERE qs.student_id=?', [$sid]);
    foreach ($subs as &$sb) $sb['answers'] = qn_answers((int)$sb['id']);
    return [
        'exported_at' => date('c'), 'student' => array_diff_key($s, ['is_demo' => 1]),
        'accounts' => family_users((int)$s['family_id']),
        'questionnaires' => $subs,
        'assessments' => all('SELECT computed_at, scores_json FROM pedagogical_assessments WHERE student_id=?', [$sid]),
        'priorities' => all('SELECT axis, rank_no, note FROM assessment_priorities WHERE student_id=?', [$sid]),
        'observations' => all('SELECT axis, adjustment, content, visibility, created_at FROM tutor_observations WHERE student_id=?', [$sid]),
        'goals' => all('SELECT * FROM goals WHERE student_id=?', [$sid]),
        'sessions' => all('SELECT * FROM sessions WHERE student_id=?', [$sid]),
        'session_notes' => all('SELECT n.* FROM session_notes n JOIN sessions s ON s.id=n.session_id WHERE s.student_id=?', [$sid]),
        'progress_entries' => all('SELECT * FROM progress_entries WHERE student_id=?', [$sid]),
    ];
}
