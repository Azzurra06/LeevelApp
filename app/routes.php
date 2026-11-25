<?php
declare(strict_types=1);

function dispatch(string $r, string $method): void
{
    $isPost = $method === 'POST';
    if ($isPost) check_csrf();

    // En passation, seules les routes du questionnaire sont autorisées.
    if (in_passation() && !in_array($r, ['q', 'q_save', 'q_submit', 'q_recap', 'consent', 'passation_end'], true)) {
        http_response_code(403);
        render('error', ['msg' => 'Mode passation : seul le questionnaire est accessible.'], 'layout_min');
    }

    switch ($r) {
        case 'home':
            $u = current_user();
            if (in_passation()) redirect('q');
            if (!$u) redirect('login');
            if (needs_onboarding($u)) redirect('welcome');
            redirect(['tutor' => 't', 'parent' => 'p', 'student' => 's'][$u['role']]);

        case 'welcome':
            welcome_route($isPost);

        case 'push_subscribe': case 'push_unsubscribe': case 'push_test': case 'push_prefs':
            $u = require_login();
            if (!$isPost || !push_available()) { http_response_code(400); exit; }
            if ($r === 'push_subscribe') {
                $ep = (string)($_POST['endpoint'] ?? ''); $p = (string)($_POST['p256dh'] ?? ''); $a = (string)($_POST['auth'] ?? '');
                if (!preg_match('#^https://[^\s]{10,690}$#', $ep) || !preg_match('/^[A-Za-z0-9_-]{40,200}$/', $p) || !preg_match('/^[A-Za-z0-9_-]{10,100}$/', $a)) { http_response_code(422); exit('invalide'); }
                q('INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, ua) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), p256dh=VALUES(p256dh), auth=VALUES(auth), ua=VALUES(ua), fail_count=0',
                    [$u['id'], $ep, $p, $a, mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 160)]);
                audit('push_subscribed'); header('Content-Type: application/json'); exit('{"ok":true}');
            }
            if ($r === 'push_unsubscribe') { q('DELETE FROM push_subscriptions WHERE user_id=? AND endpoint=?', [$u['id'], (string)($_POST['endpoint'] ?? '')]); header('Content-Type: application/json'); exit('{"ok":true}'); }
            if ($r === 'push_test') {
                $n = push_user((int)$u['id'], '_test', ['title' => 'leevel.app', 'body' => 'Les notifications fonctionnent sur cet appareil 🎉', 'url' => url('account'), 'tag' => 'test']);
                header('Content-Type: application/json'); exit(json_encode(['sent' => $n]));
            }
            $prefs = []; foreach (array_keys(PUSH_TYPES) as $k) $prefs[$k] = !empty($_POST['push_' . $k]);
            q('UPDATE users SET push_prefs=? WHERE id=?', [json_encode($prefs), $u['id']]);
            flash('Préférences de notification enregistrées.'); redirect('account');

        case 'switch_account':
            if (!$isPost || !current_user()) redirect('home');
            if (switch_account(post_int('uid'))) { audit('account_switched'); flash('Vous êtes maintenant connecté·e en tant que ' . (current_user()['display_name'] ?? '') . '.'); }
            else flash('Ce compte n\'est plus connecté : identifiez-vous à nouveau.', 'err');
            redirect('home');

        case 'login':
            $adding = !empty($_GET['add']) || !empty($_POST['add']);
            if (current_user() && !in_passation() && !$adding) redirect('home');
            $err = null;
            if ($isPost) {
                $res = attempt_login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''));
                if ($res === true) redirect('home');
                $err = $res;
            }
            render('login', ['err' => $err, 'prefill' => $_GET['u'] ?? '', 'adding' => $adding && current_user()], 'layout_min');

        case 'logout':
            if (!$isPost) redirect('home');
            audit('logout');
            // déconnexion du seul compte courant : on bascule sur un autre compte encore connecté
            $others = empty($_POST['all']) ? array_column(linked_accounts(), 'id') : [];
            $name = current_user()['display_name'] ?? '';
            if ($others) {
                $_SESSION['accounts'] = array_values(array_filter(array_map('intval', $_SESSION['accounts'] ?? []), fn($i) => $i !== (int)$_SESSION['uid']));
                switch_account((int)$others[0]);
                flash($name . ' est déconnecté·e. Vous êtes sur le compte de ' . (current_user()['display_name'] ?? '') . '.');
                redirect('home');
            }
            logout();
            flash('Vous êtes déconnecté·e.');
            redirect('login');

        case 'invite':
            require APP_ROOT . '/app/invitations.php';
            $token = (string)($_GET['token'] ?? '');
            $inv = find_invitation($token);
            if (!$inv) render('error', ['msg' => "Ce lien d'invitation n'est plus valable (déjà utilisé ou expiré). Demandez un nouveau lien à votre tutrice."], 'layout_min');
            $err = null; $old = [];
            if ($isPost) {
                $old = ['username' => mb_strtolower(post_str('username', 60)), 'display_name' => post_str('display_name', 100)];
                $pw = (string)($_POST['password'] ?? '');
                $err = validate_username($old['username']) ?? validate_password($pw);
                if (!$err && $pw !== ($_POST['password2'] ?? '')) $err = 'Les deux mots de passe ne correspondent pas.';
                if (!$err && $old['display_name'] === '') $err = 'Indiquez votre nom.';
                if (!$err && one('SELECT 1 FROM users WHERE username=?', [$old['username']])) $err = 'Cet identifiant est déjà pris, choisissez-en un autre.';
                if (!$err && $inv['role'] === 'parent' && !$inv['guardian_consent_at'] && empty($_POST['agree'])) $err = "Merci de valider l'autorisation pour continuer.";
                if (!$err) {
                    accept_invitation($inv, $old['username'], $pw, $old['display_name'], !empty($_POST['agree']));
                    flash('Bienvenue sur leevel.app ! Votre compte est créé.');
                    redirect('home');
                }
            }
            render('invite', ['inv' => $inv, 'err' => $err, 'old' => $old, 'token' => $token], 'layout_min');

        case 'test':
            if (!$isPost) redirect('home');
            handle_test_action(require_login());

        case 'avatar':
            serve_avatar(require_login(), (int)($_GET['u'] ?? 0));

        case 'file':
            serve_student_file(require_login(), (int)($_GET['id'] ?? 0));

        case 'goal_bank':
            $u = require_login();
            $st = $isPost ? study_student_for($u, post_int('sid')) : null;
            if (!$st) { http_response_code(403); render('error', ['msg' => 'Action non autorisée.']); }
            if (($_POST['action'] ?? '') === 'delete' && $u['role'] === 'student') {
                q('DELETE FROM goals WHERE id=? AND student_id=? AND created_by=?', [post_int('goal_id'), $st['id'], $u['id']]); $msg = 'Objectif retiré.';
            } else $msg = goal_bank_add((int)$st['id'], (int)$u['id'], post_str('key', 40));
            flash(ltrim($msg, '!'), str_starts_with($msg, '!') ? 'err' : 'ok');
            header('Location: ' . ($u['role'] === 'tutor' ? url('t_student', ['sid' => $st['id'], 'tab' => 'objectifs']) : url('s_goals')) . (post_str('cat', 30) ? '#bank-' . preg_replace('/[^a-z]/', '', post_str('cat', 30)) : ''), true, 303);
            exit;

        case 'study':
            $u = require_login();
            $st = $isPost ? study_student_for($u, post_int('sid')) : null;
            if (!$st) { http_response_code(403); render('error', ['msg' => 'Action non autorisée.']); }
            $msg = study_post($u, $st);
            if ($msg !== '') flash(ltrim($msg, '!'), str_starts_with($msg, '!') ? 'err' : 'ok');
            $keep = array_filter(['av' => post_enum('av', ['week' => 1, 'month' => 1, 'todo' => 1, 'sessions' => 1], ''), 'wk' => preg_match('/^\d{4}-\d{2}-\d{2}$/', post_str('wk', 10)) ? post_str('wk', 10) : '', 'sm' => preg_match('/^\d{4}-\d{2}$/', post_str('sm', 7)) ? post_str('sm', 7) : '']);
            $back = $u['role'] === 'tutor' ? url('t_student', ['sid' => $st['id'], 'tab' => 'agenda'] + $keep) : url('s_planning', $keep);
            header('Location: ' . $back . '#devoirs', true, 303);
            exit;

        case 'msg_file':
            serve_message_file(require_login(), (int)($_GET['id'] ?? 0));

        case 'help':
            render('help', ['u' => require_login()]);

        case 'privacy':
            render('privacy', [], current_user() && !in_passation() ? 'layout' : 'layout_min');

        case 'account':
            $u = require_login();
            $act = $_POST['action'] ?? 'password';
            if ($isPost && $u['role'] === 'student') {
                $perm = one('SELECT perm_edit_contacts, perm_edit_photo FROM students WHERE user_id=?', [$u['id']]) ?? [];
                if (($act === 'profile' && !(int)($perm['perm_edit_contacts'] ?? 1)) || (in_array($act, ['avatar', 'avatar_remove'], true) && !(int)($perm['perm_edit_photo'] ?? 1))) {
                    http_response_code(403); flash('Cette modification est gérée par ton parent.', 'err'); redirect('account');
                }
            }
            if ($isPost && in_array($act, ['child_contacts', 'child_perms'], true)) {
                if ($u['role'] !== 'parent') { http_response_code(403); render('error', ['msg' => 'Action réservée au parent.']); }
                $ch = student_for($u, post_int('sid'));
                if ($act === 'child_contacts') {
                    $email = post_str('email', 120); $phone = preg_replace('/[^0-9+ ().-]/', '', post_str('phone', 30));
                    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Adresse e-mail invalide.', 'err'); redirect('account'); }
                    q('UPDATE students SET student_phone=?, student_email=? WHERE id=?', [$phone ?: null, $email ?: null, $ch['id']]);
                    if ($ch['user_id']) q('UPDATE users SET phone=?, email=? WHERE id=?', [$phone ?: null, $email ?: null, $ch['user_id']]);
                    flash('Coordonnées de ' . $ch['first_name'] . ' enregistrées.');
                } else {
                    save_child_perms((int)$ch['id']);
                    audit('student_permissions', null, (int)$ch['family_id']);
                    flash('Autorisations de ' . $ch['first_name'] . ' enregistrées.');
                }
                redirect('account');
            }
            if ($isPost && $act === 'profile') {
                $name = post_str('display_name', 100); $email = post_str('email', 120);
                if ($u['role'] === 'student' && !(int)(one('SELECT perm_link_email FROM students WHERE user_id=?', [$u['id']])['perm_link_email'] ?? 1)) $email = (string)(one('SELECT email FROM users WHERE id=?', [$u['id']])['email'] ?? '');
                $phone = preg_replace('/[^0-9+ ().-]/', '', post_str('phone', 30));
                if ($name === '') flash('Le nom ne peut pas être vide.', 'err');
                elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Adresse e-mail invalide.', 'err');
                else {
                    q('UPDATE users SET display_name=?, phone=?, email=? WHERE id=?', [$name, $phone ?: null, $email ?: null, $u['id']]);
                    if ($u['role'] !== 'student') {
                        try { q('UPDATE users SET notify_messages=? WHERE id=?', [(int)!empty($_POST['notify_messages']), $u['id']]); } catch (PDOException) { /* base pas encore migrée */ }
                    }
                    $address = post_str('address', 255);
                    try { q('UPDATE users SET address=? WHERE id=?', [$address ?: null, $u['id']]); } catch (PDOException) { /* base pas encore migrée */ }
                    if ($u['role'] === 'parent' && $address !== '') {
                        q('UPDATE students s JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=? SET s.parent_address=?', [$u['id'], $address]);
                    }
                    if ($u['role'] === 'parent') { // les coordonnées du parent alimentent la fiche élève de la tutrice
                        q('UPDATE students s JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=? SET s.parent_phone=?, s.parent_email=?', [$u['id'], $phone ?: null, $email ?: null]);
                    } elseif ($u['role'] === 'student') {
                        q('UPDATE students SET student_phone=?, student_email=? WHERE user_id=?', [$phone ?: null, $email ?: null, $u['id']]);
                    }
                    audit('profile_updated');
                    flash('Informations enregistrées.');
                }
                redirect('account');
            }
            if ($isPost && $act === 'student_profile' && $u['role'] === 'student') {
                $st = one('SELECT s.*, f.guardian_consent_at FROM students s JOIN families f ON f.id=s.family_id WHERE s.user_id=?', [$u['id']]);
                if (!$st || !$st['guardian_consent_at']) { flash("Ton profil sera modifiable dès que ton parent aura validé l'autorisation d'utilisation.", 'err'); redirect('account'); }
                $in = ['s_first_name' => post_str('s_first_name', 80), 's_last_name' => post_str('s_last_name', 80), 's_age' => post_str('s_age', 3),
                       's_class' => post_str('s_class', 10), 's_school' => post_str('s_school', 120),
                       's_interests' => array_values(array_filter((array)($_POST['s_interests'] ?? []), 'is_string')), 's_memory' => array_values(array_filter((array)($_POST['s_memory'] ?? []), 'is_string'))];
                qn_save(qn_submission('student', (int)$st['id'], (int)$u['id']), 'student', $in);
                student_profile_sync((int)$st['id'], $in);
                audit('student_profile_updated', null, (int)$st['family_id']);
                flash('Ton profil est enregistré.');
                redirect('account');
            }
            if ($isPost && $act === 'ai_key' && $u['role'] === 'tutor') {
                if (!empty($_POST['remove'])) { ai_save_key(null); ai_save_workspace(null); audit('ai_key_removed'); flash('Clé API supprimée.'); }
                elseif (($_POST['only'] ?? '') === 'workspace') {
                    $ws = trim((string)($_POST['workspace_id'] ?? ''));
                    if ($ws !== '' && !preg_match('/^wrkspc_[A-Za-z0-9]{8,}$/', $ws)) flash("L'identifiant d'espace de travail commence par « wrkspc_ ».", 'err');
                    else { ai_save_workspace($ws); audit('ai_workspace_saved'); flash($ws ? 'Espace de travail enregistré.' : 'Espace de travail retiré.'); }
                }
                else {
                    $key = trim((string)($_POST['ai_key'] ?? ''));
                    if (!preg_match('/^sk-ant-[A-Za-z0-9_\-]{20,}$/', $key)) flash('Cette clé ne ressemble pas à une clé API Anthropic (elle commence par « sk-ant- »).', 'err');
                    else { ai_save_key($key); audit('ai_key_saved'); flash('Clé API enregistrée : la génération de quiz par IA est activée.'); }
                }
                redirect('account');
            }
            if ($isPost && $act === 'rates' && $u['role'] === 'tutor') {
                $money = function (string $k): ?string {
                    $v = str_replace([',', '€', ' '], ['.', '', ''], post_str($k, 20));
                    return $v !== '' && is_numeric($v) && (float)$v >= 0 && (float)$v < 100000 ? number_format((float)$v, 2, '.', '') : null;
                };
                $rows = [];
                $types = (array)($_POST['d_type'] ?? []); $conds = (array)($_POST['d_cond'] ?? []); $vals = (array)($_POST['d_value'] ?? []);
                foreach ($types as $i => $type) {
                    $cond = mb_substr(trim((string)($conds[$i] ?? '')), 0, 100); $val = mb_substr(trim((string)($vals[$i] ?? '')), 0, 60);
                    if ($cond === '' && $val === '') continue;
                    $rows[] = ['type' => in_array($type, ['children', 'pack', 'other'], true) ? $type : 'other', 'condition' => $cond, 'value' => $val];
                    if (count($rows) >= 10) break;
                }
                q('UPDATE users SET rate_presentiel=?, rate_visio=?, discounts_json=? WHERE id=?', [$money('rate_presentiel'), $money('rate_visio'), $rows ? json_encode($rows, JSON_UNESCAPED_UNICODE) : null, $u['id']]);
                audit('rates_updated');
                flash('Tarifs enregistrés.');
                redirect('account');
            }
            if ($isPost && $act === 'avatar') {
                $m = save_avatar((int)$u['id'], $_FILES['avatar'] ?? []);
                flash($m ?? 'Photo de profil mise à jour.', $m ? 'err' : 'ok');
                redirect('account');
            }
            if ($isPost && $act === 'avatar_remove') {
                remove_avatar((int)$u['id']);
                flash('Photo supprimée.');
                redirect('account');
            }
            if ($isPost && $act === 'username') {
                $new = mb_strtolower(trim(post_str('new_username', 60)));
                $full = one('SELECT password_hash FROM users WHERE id=?', [$u['id']]);
                if (!password_verify((string)($_POST['current_password'] ?? ''), $full['password_hash'])) flash('Mot de passe actuel incorrect.', 'err');
                elseif ($new === $u['username']) flash('C\'est déjà votre identifiant.', 'err');
                elseif ($m = validate_username($new)) flash($m, 'err');
                elseif (one('SELECT 1 FROM users WHERE username=? AND id<>?', [$new, $u['id']])) flash('Cet identifiant est déjà pris, choisissez-en un autre.', 'err');
                else {
                    q('UPDATE users SET username=? WHERE id=?', [$new, $u['id']]);
                    audit('username_changed', $u['username'] . ' → ' . $new);
                    flash('Identifiant modifié : utilisez désormais « ' . $new . ' » pour vous connecter.');
                }
                redirect('account');
            }
            if ($isPost && $act === 'delete_account') {
                $full = one('SELECT password_hash FROM users WHERE id=?', [$u['id']]);
                if (!password_verify((string)($_POST['current_password'] ?? ''), $full['password_hash'])) { flash('Mot de passe incorrect : le compte n\'a pas été supprimé.', 'err'); redirect('account'); }
                if (post_str('confirm', 20) !== 'SUPPRIMER') { flash('Tapez SUPPRIMER pour confirmer.', 'err'); redirect('account'); }
                delete_account($u, !empty($_POST['with_families']));
                logout();
                flash('Votre compte et vos données ont été supprimés définitivement.');
                redirect('login');
            }
            if ($isPost) {
                $full = one('SELECT password_hash FROM users WHERE id=?', [$u['id']]);
                $new = (string)($_POST['new_password'] ?? '');
                if (!password_verify((string)($_POST['current_password'] ?? ''), $full['password_hash'])) flash('Mot de passe actuel incorrect.', 'err');
                elseif ($new !== ($_POST['new_password2'] ?? '')) flash('Les deux mots de passe ne correspondent pas.', 'err');
                elseif ($m = validate_password($new)) flash($m, 'err');
                else {
                    q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
                    remember_forget_user((int)$u['id']); // les autres appareils devront se reconnecter
                    session_regenerate_id(true);
                    audit('password_changed');
                    flash('Mot de passe modifié.');
                }
                redirect('account');
            }
            render('account', ['u' => $u]);

        // ---------------- Questionnaires ----------------
        case 'q': case 'q_save': case 'q_submit': case 'q_recap': case 'consent':
            questionnaire_route($r, $isPost);

        case 'passation_end':
            if (!$isPost) redirect('home');
            $p = $_SESSION['passation'] ?? null;
            if ($p) audit('passation_end', $p['code'] . " student={$p['student_id']}");
            $tutorName = $p['tutor_username'] ?? '';
            logout();
            render('passation_end', ['tutor' => $tutorName], 'layout_min');

        // ---------------- Tutrice ----------------
        default:
            if (str_starts_with($r, 't')) { require APP_ROOT . '/app/tutor.php'; tutor_route($r, $isPost); }
            if ($r === 'p' || $r === 's' || str_starts_with($r, 's_') || str_starts_with($r, 'p_')) { family_route($r, $isPost); }
            http_response_code(404);
            render('error', ['msg' => 'Page introuvable.'], current_user() ? 'layout' : 'layout_min');
    }
}

/** Contexte questionnaire : [user, student, code] selon passation ou connexion. */
function questionnaire_context(): array
{
    if (in_passation()) {
        $p = $_SESSION['passation'];
        if ($p['expires'] < time()) { logout(); flash('Passation expirée.', 'err'); redirect('login'); }
        $u = current_user();
        $s = student_for($u, (int)$p['student_id']);
        return [$u, $s, $p['code'], null];
    }
    $u = require_login();
    if ($u['role'] === 'tutor') {
        // Remplissage par la tutrice, pour le compte du répondant (même soumission que la passation)
        $code = in_array($_GET['code'] ?? '', ['parent', 'student'], true) ? $_GET['code'] : 'parent';
        $s = student_for($u, (int)($_GET['sid'] ?? 0));
        $resp = $code === 'student'
            ? one('SELECT id, username, display_name, role FROM users WHERE id=? AND is_active=1', [(int)$s['user_id']])
            : one('SELECT u.id, u.username, u.display_name, u.role FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE u.role="parent" AND u.is_active=1 ORDER BY u.id LIMIT 1', [$s['family_id']]);
        if (!$resp) { flash('Aucun compte ' . ($code === 'parent' ? 'parent' : 'élève') . ' actif pour ce dossier.', 'err'); redirect('t_rdv', ['sid' => $s['id']]); }
        return [$resp, $s, $code, $u];
    }
    $s = pick_student($u);
    if (!$s) render('error', ['msg' => 'Aucun dossier associé à ce compte.']);
    return [$u, $s, $u['role'] === 'parent' ? 'parent' : 'student', null];
}

function questionnaire_route(string $r, bool $isPost): never
{
    [$u, $s, $code, $tutor] = questionnaire_context();
    $layout = in_passation() ? 'layout_min' : 'layout';
    $qp = $tutor ? ['sid' => (int)$s['id'], 'code' => $code] : []; // paramètres conservés en mode tutrice
    $ctx = ['qp' => $qp, 'tutor' => $tutor, 'respondent' => $u];

    // Consentement du représentant légal (avant tout usage par le mineur)
    if ($r === 'consent') {
        if (!$isPost || $u['role'] !== 'parent') redirect('q', $qp);
        if (!empty($_POST['agree'])) {
            q('UPDATE families SET guardian_consent_at=NOW(), guardian_consent_user_id=? WHERE id=?', [$u['id'], $s['family_id']]);
            audit($tutor ? 'guardian_consent_recorded_by_tutor' : 'guardian_consent', null, (int)$s['family_id']);
        }
        redirect('q', $qp);
    }
    if (!$s['guardian_consent_at']) {
        if ($u['role'] === 'parent') render('consent', ['s' => $s] + $ctx, $layout);
        if ($tutor) { flash("L'autorisation du représentant légal doit d'abord être validée (questionnaire parent).", 'err'); redirect('t_rdv', ['sid' => $s['id']]); }
        render('error', ['msg' => "Ton espace sera disponible dès que ton parent aura validé l'autorisation d'utilisation."], $layout);
    }

    $cfg = qn_config($code);
    $sub = qn_submission($code, (int)$s['id'], (int)$u['id']);

    if ($r === 'q_save') {
        header('Content-Type: application/json');
        $raw = json_decode((string)file_get_contents('php://input'), true);
        $answers = is_array($raw['answers'] ?? null) ? $raw['answers'] : [];
        qn_save($sub, $code, $answers, isset($raw['step']) ? (int)$raw['step'] : null);
        if ($code === 'parent' && isset($answers['p_class']) && isset(CLASSES[$answers['p_class']])) q('UPDATE students SET grade=? WHERE id=?', [$answers['p_class'], $s['id']]);
        if ($code === 'student') student_profile_sync((int)$s['id'], $answers);
        exit(json_encode(['ok' => true, 'saved_at' => date('H:i')]));
    }
    if ($r === 'q_submit') {
        if (!$isPost) redirect('q');
        qn_save($sub, $code, (array)($_POST['a'] ?? []), count($cfg['steps']));
        if ($code === 'parent' && isset(CLASSES[$_POST['a']['p_class'] ?? ''])) q('UPDATE students SET grade=? WHERE id=?', [$_POST['a']['p_class'], $s['id']]);
        if ($code === 'student') student_profile_sync((int)$s['id'], (array)($_POST['a'] ?? []));
        qn_complete($sub);
        audit('questionnaire_completed', $code . ($tutor ? ' (rempli par la tutrice)' : ''), (int)$s['family_id']);
        run_analysis((int)$s['id'], $tutor ? (int)$tutor['id'] : null);
        if (needs_onboarding(current_user())) redirect('welcome', ['step' => 'questionnaire']);
        redirect('q_recap', $qp + ['done' => 1]);
    }
    $answers = qn_answers((int)$sub['id']);
    if ($r === 'q_recap') {
        render('q_recap', ['cfg' => $cfg, 'answers' => $answers, 'sub' => $sub, 's' => $s, 'done' => !empty($_GET['done'])] + $ctx, $layout);
    }
    $step = isset($_GET['step']) ? (int)$_GET['step'] : ($sub['status'] === 'completed' ? -1 : (int)$sub['current_step']);
    if ($step < 0) redirect('q_recap', $qp);
    if ($code === 'student') $answers = student_profile_defaults($s, $answers);
    render('q_form', ['cfg' => $cfg, 'answers' => $answers, 'sub' => $sub, 's' => $s, 'step' => min($step, count($cfg['steps']) - 1), 'code' => $code] + $ctx, $layout);
}

// ---------------- Parent & élève ----------------
function family_route(string $r, bool $isPost): never
{
    if ($r === 'p_planning') {
        $u = require_login('parent');
        $s = pick_student($u) ?? render('error', ['msg' => 'Aucun dossier associé.']);
        render('p_planning', ['u' => $u, 's' => $s] + family_view_data($s));
    }
    if ($r === 'p_finance') {
        $u = require_login('parent');
        $s = pick_student($u) ?? render('error', ['msg' => 'Aucun dossier associé.']);
        if (!finance_available()) render('error', ['msg' => 'Cette page sera bientôt disponible.']);
        try { $s += one('SELECT pay_frequency, pay_method FROM families WHERE id=?', [$s['family_id']]) ?? []; } catch (PDOException) {}
        render('p_finance', ['u' => $u, 's' => $s, 'billing' => month_billing($s, date('Y-m')), 'week' => week_sessions($s), 'payments' => payments_of((int)$s['id'])]);
    }
    if ($r === 'p_child') {
        $u = require_login('parent');
        $s = pick_student($u) ?? render('error', ['msg' => 'Aucun dossier associé.']);
        if ($isPost) {
            $act = (string)($_POST['action'] ?? '');
            try {
                if ($act === 'identity') {
                    $first = post_str('first_name', 80);
                    if ($first === '') { flash('Le prénom est obligatoire.', 'err'); redirect('p_child', ['sid' => $s['id']]); }
                    $bd = post_str('birth_date', 10); $bd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd) && strtotime($bd) < time() ? $bd : null;
                    $grade = isset(CLASSES[$_POST['grade'] ?? '']) ? $_POST['grade'] : $s['grade'];
                    q('UPDATE students SET first_name=?, last_name=?, birth_date=?, grade=?, school=? WHERE id=?', [$first, post_str('last_name', 80) ?: null, $bd, $grade, post_str('school', 120) ?: null, $s['id']]);
                    // le questionnaire parent reprend les mêmes informations
                    $sub = qn_latest('parent', (int)$s['id']);
                    if ($sub) qn_save($sub, 'parent', ['p_first_name' => $first, 'p_last_name' => post_str('last_name', 80), 'p_class' => $grade, 'p_class_school' => post_str('school', 120)]);
                    flash('Informations de ' . $first . ' enregistrées.');
                } elseif ($act === 'contacts') {
                    $email = post_str('email', 120); $phone = preg_replace('/[^0-9+ ().-]/', '', post_str('phone', 30));
                    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Adresse e-mail invalide.', 'err'); redirect('p_child', ['sid' => $s['id']]); }
                    q('UPDATE students SET student_phone=?, student_email=? WHERE id=?', [$phone ?: null, $email ?: null, $s['id']]);
                    if ($s['user_id']) q('UPDATE users SET phone=?, email=? WHERE id=?', [$phone ?: null, $email ?: null, $s['user_id']]);
                    flash('Coordonnées enregistrées.');
                } elseif ($act === 'needs') {
                    $allowedN = ['dys', 'tdah', 'hpi', 'anxiety', 'other']; $allowedA = ['pap', 'pps', 'pai', 'tiers_temps'];
                    $needs = ['items' => array_values(array_intersect((array)($_POST['needs'] ?? []), $allowedN)), 'acc' => array_values(array_intersect((array)($_POST['acc'] ?? []), $allowedA))];
                    q('UPDATE students SET needs_json=?, accommodations=?, parent_notes=? WHERE id=?', [json_encode($needs), post_str('accommodations', 500) ?: null, post_str('parent_notes', 2000) ?: null, $s['id']]);
                    flash('Besoins particuliers enregistrés.');
                } elseif ($act === 'perms') {
                    save_child_perms((int)$s['id']);
                    flash('Autorisations enregistrées.');
                }
                audit('child_info_' . $act, null, (int)$s['family_id']);
            } catch (PDOException) { flash('La base de données doit être mise à jour.', 'err'); }
            redirect('p_child', ['sid' => $s['id']]);
        }
        $tab = in_array($_GET['tab'] ?? '', ['profil', 'matieres', 'progression', 'bilans'], true) ? $_GET['tab'] : 'profil';
        $extra = [];
        if ($tab === 'matieres') $extra = ['subjects' => subjects_of((int)$s['id']), 'tests' => tests_upcoming((int)$s['id'])];
        if ($tab === 'progression') {
            $extra = ['goals' => all('SELECT * FROM goals WHERE student_id=? ORDER BY FIELD(status,"in_progress","rework","todo","acquired"), target_date', [$s['id']]),
                'subjects' => subjects_of((int)$s['id']),
                'doneSe' => all('SELECT id, starts_at, duration_min FROM sessions WHERE student_id=? AND (status="done" OR (status="planned" AND starts_at < NOW())) ORDER BY starts_at DESC', [$s['id']])];
            try { $extra['fbs'] = all('SELECT f.mood, s.starts_at FROM session_feedback f JOIN sessions s ON s.id=f.session_id WHERE s.student_id=? ORDER BY s.starts_at DESC LIMIT 8', [$s['id']]); } catch (PDOException) { $extra['fbs'] = []; }
        }
        if ($tab === 'bilans') $extra = ['monthlies' => monthly_published((int)$s['id'], 24), 'reports' => sent_reports((int)$s['id'], 30)];
        render('p_child', ['u' => $u, 's' => $s, 'tab' => $tab] + $extra);
    }
    if ($r === 'p_messages') {
        $u = require_login('parent');
        $s = pick_student($u) ?? render('error', ['msg' => 'Aucun dossier associé.']);
        if ($isPost) {
            $err = post_message((int)$s['family_id'], $u); flash($err ?? 'Message envoyé.', $err ? 'err' : 'ok');
            redirect('p_messages', ['sid' => $s['id']]);
        }
        $ch = ($_GET['ch'] ?? '') === 'student' && $s['user_id'] && messages_have_channels() ? 'student' : 'family';
        mark_messages_read((int)$s['family_id'], $u, $ch);
        render('p_messages', ['u' => $u, 's' => $s, 'tutor' => family_tutor((int)$s['family_id']), 'ch' => $ch, 'messages' => family_messages((int)$s['family_id'], $ch),
            'studentCount' => $s['user_id'] && messages_have_channels() ? (int)(one('SELECT COUNT(*) n FROM messages WHERE family_id=? AND channel="student"', [$s['family_id']])['n'] ?? 0) : 0]);
    }
    if ($r === 'p') {
        $u = require_login('parent');
        if (needs_onboarding($u)) redirect('welcome');
        $s = pick_student($u);
        if (!$s) render('error', ['msg' => 'Aucun dossier associé.']);
        render('p_dashboard', ['u' => $u, 's' => $s] + family_view_data($s));
    }
    $u = require_login('student');
    $s = pick_student($u);
    if (!$s) render('error', ['msg' => 'Aucun dossier associé.']);
    if (!$s['guardian_consent_at']) render('error', ['msg' => "Ton espace sera disponible dès que ton parent aura validé l'autorisation d'utilisation."]);
    if ($r === 's_planning') {
        render('p_planning', ['u' => $u, 's' => $s] + family_view_data($s));
    }
    if ($r === 's_goals') {
        render('s_goals', ['u' => $u, 's' => $s, 'goals' => all('SELECT * FROM goals WHERE student_id=? ORDER BY FIELD(status,"in_progress","rework","todo","acquired"), target_date, id', [$s['id']])]);
    }
    if ($r === 's_tools') {
        $ssub = qn_latest('student', (int)$s['id']);
        render('s_tools', ['u' => $u, 's' => $s, 'memory' => $ssub ? (qn_answers((int)$ssub['id'])['s_memory'] ?? []) : []] + family_view_data($s));
    }
    if ($r === 's_goal' && $isPost) {
        $g = one('SELECT * FROM goals WHERE id=? AND student_id=?', [post_int('goal_id'), $s['id']]);
        if ($g) {
            $feeling = post_enum('feeling', FEELINGS, '');
            if ($feeling) {
                q('UPDATE goals SET student_feeling=? WHERE id=?', [$feeling, $g['id']]);
                q('INSERT INTO progress_entries (student_id, user_id, goal_id, entry_type, value) VALUES (?,?,?,?,?)', [$s['id'], $u['id'], $g['id'], 'self_eval', $feeling]);
            }
            $ms = post_enum('mstatus', MISSION_STATUS, '');
            if ($ms && ($g['horizon'] ?? '') !== 'year') {
                q('UPDATE goals SET status=?, progress=?, student_done_at=? WHERE id=?', [$ms, $ms === 'acquired' ? 100 : ($ms === 'todo' ? 0 : max(10, (int)$g['progress'])), $ms === 'acquired' ? date('Y-m-d H:i:s') : null, $g['id']]);
                if ($ms === 'acquired') q('INSERT INTO progress_entries (student_id, user_id, goal_id, entry_type) VALUES (?,?,?,?)', [$s['id'], $u['id'], $g['id'], 'task_done']);
            }
            $df = post_enum('difficulty', DIFFICULTY, '');
            if ($df) { try { q('UPDATE goals SET difficulty=? WHERE id=?', [$df, $g['id']]); } catch (PDOException) {} }
            if (!empty($_POST['done'])) {
                q('UPDATE goals SET student_done_at=NOW() WHERE id=?', [$g['id']]);
                q('INSERT INTO progress_entries (student_id, user_id, goal_id, entry_type) VALUES (?,?,?,?)', [$s['id'], $u['id'], $g['id'], 'task_done']);
            }
            flash('Merci, c\'est noté !');
        }
        if (($_POST['back'] ?? '') === 's_goals') redirect('s_goals');
        header('Location: ' . url('s') . '#missions', true, 303); exit;
    }
    if ($r === 's_feedback' && $isPost) {
        $se = one('SELECT * FROM sessions WHERE id=? AND student_id=? AND starts_at < NOW()', [post_int('session_id'), $s['id']]);
        if ($se) {
            $mood = post_enum('mood', MOODS, '');
            if (array_key_exists('note', $_POST)) { // retour simplifié : émoji + petite note
                q('INSERT INTO session_feedback (session_id, mood, note) VALUES (?,?,?) ON DUPLICATE KEY UPDATE mood=VALUES(mood), note=VALUES(note)',
                    [$se['id'], $mood ?: null, post_str('note', 1000) ?: null]);
            } else {
                q('INSERT INTO session_feedback (session_id, mood, understood, to_deepen, needs_next) VALUES (?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE mood=VALUES(mood), understood=VALUES(understood), to_deepen=VALUES(to_deepen), needs_next=VALUES(needs_next)',
                    [$se['id'], $mood ?: null, post_str('understood', 1000) ?: null, post_str('to_deepen', 1000) ?: null, post_str('needs_next', 1000) ?: null]);
            }
            flash('Merci ! Ta tutrice verra ton retour avant la prochaine séance.');
        }
        ($_POST['back'] ?? '') === 's_planning' ? redirect('s_planning') : (($_POST['back'] ?? '') === 's_subjects' ? redirect('s_subjects', ['subj' => post_int('subj')]) : redirect('s'));
    }
    if ($r === 's_subjects') student_subjects_route($u, $s, $isPost);
    if ($r === 's_messages') {
        if ($isPost) {
            $err = post_message((int)$s['family_id'], $u, 'student');
            if ($err) flash($err, 'err'); else { audit('message_sent', 'student', (int)$s['family_id']); flash('Message envoyé à ta tutrice.'); }
            redirect('s_messages');
        }
        mark_messages_read((int)$s['family_id'], $u, 'student');
        render('s_messages', ['u' => $u, 's' => $s, 'tutor' => family_tutor((int)$s['family_id']), 'messages' => family_messages((int)$s['family_id'], 'student')]);
    }
    if ($r === 's_help' && $isPost) {
        if (!(int)($s['perm_help_requests'] ?? 1)) { flash("Le bouton d'aide n'est pas activé pour ton compte.", 'err'); redirect('s'); }
        q('INSERT INTO progress_entries (student_id, user_id, entry_type, comment) VALUES (?,?,?,?)', [$s['id'], $u['id'], 'help_request', post_str('comment', 1000) ?: null]);
        audit('help_request', null, (int)$s['family_id']);
        flash('Ta demande est envoyée à ta tutrice. Elle en parlera avec toi très vite.');
        redirect('s');
    }
    if ($r === 's_feeling' && $isPost) {
        q('INSERT INTO progress_entries (student_id, user_id, entry_type, value, comment) VALUES (?,?,?,?,?)', [$s['id'], $u['id'], 'feeling', post_enum('mood', ['good' => 1, 'ok' => 1, 'hard' => 1], 'ok'), post_str('comment', 500) ?: null]);
        flash('Merci pour ton ressenti !');
        redirect('s');
    }
    if ($r === 's' && needs_onboarding($u)) redirect('welcome');
    render('s_dashboard', ['u' => $u, 's' => $s] + family_view_data($s) + [
        'missions' => all('SELECT * FROM goals WHERE student_id=? AND is_mission=1 AND status<>"acquired" ORDER BY student_done_at IS NOT NULL, target_date IS NULL, target_date LIMIT 3', [$s['id']]),
        'wins' => all('SELECT title, updated_at FROM goals WHERE student_id=? AND (status="acquired" OR student_done_at IS NOT NULL) ORDER BY updated_at DESC LIMIT 4', [$s['id']]),
        'studentSub' => qn_latest('student', (int)$s['id']),
    ]);
}

/** Données partageables avec la famille (aucune réponse privée). */
function family_view_data(array $s): array
{
    $an = latest_analysis((int)$s['id']);
    $prio = effective_priorities((int)$s['id'], $an);
    return [
        'sessions' => all('SELECT * FROM sessions WHERE student_id=? ORDER BY starts_at', [$s['id']]),
        'goals' => all('SELECT * FROM goals WHERE student_id=? ORDER BY FIELD(status,"in_progress","rework","todo","acquired"), target_date', [$s['id']]),
        'prio' => $prio,
        'reco' => recommendations($prio['axes'], $an),
        'sharedObs' => all('SELECT content, created_at FROM tutor_observations WHERE student_id=? AND visibility="shared" ORDER BY id DESC LIMIT 5', [$s['id']]),
        'tests' => tests_upcoming((int)$s['id']),
        'reports' => sent_reports((int)$s['id']),
        'monthly' => monthly_published((int)$s['id'])[0] ?? null,
        'yearGoals' => year_goals((int)$s['id']),
        'subjects' => subjects_of((int)$s['id']),
        'recap' => last_session_recap((int)$s['id']),
        'study' => study_tasks((int)$s['id']),
    ];
}


/** Élève : « Mes matières » (notes, chapitres, quiz, fichiers, blocages, dernière séance). */
function student_subjects_route(array $u, array $s, bool $isPost): never
{
    $sid = (int)$s['id'];
    $subj = (int)($_GET['subj'] ?? $_POST['subj'] ?? 0);
    $back = fn(string $anchor = '') => redirect('s_subjects', ['subj' => $subj]) ;
    if ($isPost && subjects_available()) {
        $act = (string)($_POST['action'] ?? '');
        $subject = $subj ? subject_of_student($subj, $sid) : null;
        if ($act === 'grade_add' && $subject) {
            $err = grade_add_from_post($subject, $u);
            flash($err ?? 'Note ajoutée. Bravo de suivre tes résultats !', $err ? 'err' : 'ok');
        } elseif ($act === 'grade_del') {
            q('DELETE g FROM subject_grades g JOIN student_subjects ss ON ss.id=g.subject_id WHERE g.id=? AND ss.student_id=? AND g.created_by=?', [post_int('grade_id'), $sid, $u['id']]);
            flash('Note supprimée.');
        } elseif ($act === 'file_upload') {
            $err = save_student_file($sid, $subject['id'] ?? null, $u, $_FILES['file'] ?? [], post_str('note', 300));
            if (!$err) audit('file_uploaded', null, (int)$s['family_id']);
            flash($err ?? 'Fichier envoyé à ta tutrice.', $err ? 'err' : 'ok');
        } elseif ($act === 'file_del') {
            $f = one('SELECT * FROM student_files WHERE id=? AND student_id=? AND user_id=?', [post_int('file_id'), $sid, $u['id']]);
            if ($f) { delete_student_file($f); flash('Fichier supprimé.'); }
        } elseif ($act === 'comment' && $subject) {
            try { $err = subject_comment_add($subject, $u, true); } catch (PDOException) { $err = 'Indisponible pour le moment.'; }
            flash($err ?? 'Commentaire envoyé à ta tutrice.', $err ? 'err' : 'ok');
        } elseif ($act === 'comment_del') {
            q('DELETE m FROM subject_comments m JOIN student_subjects ss ON ss.id=m.subject_id WHERE m.id=? AND ss.student_id=? AND m.user_id=?', [post_int('comment_id'), $sid, $u['id']]);
            flash('Commentaire supprimé.');
        } elseif ($act === 'blocker') {
            $body = post_str('body', 1000);
            $ch = post_int('chapter_id') ? chapter_of_student(post_int('chapter_id'), $sid) : null;
            if ($body === '') flash('Explique en quelques mots ce qui te bloque.', 'err');
            else {
                q('INSERT INTO subject_blockers (student_id, subject_id, chapter_id, user_id, body) VALUES (?,?,?,?,?)', [$sid, $subject['id'] ?? null, $ch['id'] ?? null, $u['id'], $body]);
                audit('blocker_reported', null, (int)$s['family_id']);
                flash('C\'est noté ! Ta tutrice le verra et vous en parlerez à la prochaine séance.');
            }
        } elseif ($act === 'quiz') {
            $ch = chapter_of_student(post_int('chapter_id'), $sid);
            $qs = $ch ? chapter_questions((int)$ch['id']) : [];
            if ($qs) {
                [$score, $total, $detail] = quiz_grade($qs, (array)($_POST['q'] ?? []));
                q('INSERT INTO chapter_attempts (chapter_id, user_id, score, total, answers_json) VALUES (?,?,?,?,?)', [$ch['id'], $u['id'], $score, $total, json_encode($detail)]);
                $_SESSION['quiz_result'] = ['chapter_id' => (int)$ch['id'], 'score' => $score, 'total' => $total, 'detail' => $detail];
            }
        }
        $back();
    }
    $subjects = subjects_of($sid);
    $cur = null;
    foreach ($subjects as $x) if ((int)$x['id'] === $subj) $cur = $x;
    $cur ??= $subjects[0] ?? null;
    $quizResult = $_SESSION['quiz_result'] ?? null; unset($_SESSION['quiz_result']);
    $current = null;
    if ($cur) foreach ($cur['chapters'] as $c) if ($c['is_current']) { $current = $c; break; }
    if ($cur && !$current) foreach ($cur['chapters'] as $c) if ($c['status'] !== 'acquired') { $current = $c; break; }
    render('s_subjects', ['u' => $u, 's' => $s, 'subjects' => $subjects, 'cur' => $cur, 'current' => $current,
        'questions' => $current ? chapter_questions((int)$current['id']) : [],
        'lastAttempt' => $current ? one('SELECT * FROM chapter_attempts WHERE chapter_id=? AND user_id=? ORDER BY id DESC LIMIT 1', [$current['id'], $u['id']]) : null,
        'quizResult' => $quizResult, 'recap' => last_session_recap($sid),
        'files' => $cur ? student_files($sid, (int)$cur['id'], 12) : [], 'blockers' => $cur ? student_blockers($sid, (int)$cur['id'], 5) : []]);
}
