<?php
declare(strict_types=1);
// Parcours d'accueil des nouveaux inscrits (parent, élève) : étapes guidées avant d'arriver sur l'espace.

const WISH_HOURS = ['1' => '1 h', '1.5' => '1 h 30', '2' => '2 h', '3' => '3 h', '4' => '4 h ou plus', 'tbd' => 'À voir avec la tutrice'];
const WISH_NEEDS = ['subjects' => 'Réviser / consolider des matières', 'homework' => 'Aide aux devoirs', 'organisation' => 'Gagner en organisation et en méthode', 'exam' => 'Préparer un examen (brevet, bac…)', 'confidence' => 'Reprendre confiance', 'other' => 'Autre'];
const WISH_MODES = ['presentiel' => 'Présentiel', 'visio' => 'Visio', 'both' => 'Les deux', 'unsure' => 'Je ne sais pas encore', 'other' => 'Autre'];

function wishes_of(?array $s): array
{
    $w = json_decode((string)($s['wishes_json'] ?? ''), true);
    return is_array($w) ? $w + ['hours' => '', 'needs' => [], 'subjects' => [], 'general' => false, 'mode' => '', 'mode_other' => '', 'other' => '', 'slots' => '', 'note' => ''] : ['hours' => '', 'needs' => [], 'subjects' => [], 'general' => false, 'mode' => '', 'mode_other' => '', 'other' => '', 'slots' => '', 'note' => ''];
}
/** Matières proposées selon la classe de l'élève. */
function wish_subject_options(?array $s): array
{
    $lv = (require APP_ROOT . '/app/questionnaires/_levels.php')['subjects'];
    $cyc = grade_cycle($s['grade'] ?? null);
    $out = [];
    foreach ($lv as $k => [$name, $cycles]) if (!$cyc || in_array($cyc, (array)$cycles, true)) $out[$k] = $name;
    return $out;
}

function onboarding_available(): bool
{
    static $ok = null;
    if ($ok === null) { try { db()->query('SELECT onboarded_at FROM users LIMIT 1'); $ok = true; } catch (PDOException) { $ok = false; } }
    return $ok;
}
function needs_onboarding(?array $u): bool
{
    if (!$u || !in_array($u['role'], ['parent', 'student'], true) || in_passation() || !onboarding_available()) return false;
    return (one('SELECT onboarded_at FROM users WHERE id=?', [$u['id']])['onboarded_at'] ?? null) === null;
}
/** Étapes du parcours selon le rôle : clé => [titre court, icône]. */
function welcome_steps(array $u, ?array $s): array
{
    if ($u['role'] === 'student') return ['intro' => ['Bienvenue', 'smile'], 'questionnaire' => ['Ton questionnaire', 'clipboard-list'], 'tour' => ['Ton espace', 'house'], 'done' => ["C'est parti", 'rocket']];
    $steps = ['intro' => ['Bienvenue', 'smile'], 'contact' => ['Coordonnées', 'message-circle'], 'child' => ['Votre enfant', 'user'], 'questionnaire' => ['Questionnaire', 'clipboard-list']];
    $steps['wishes'] = ['Besoins', 'target'];
    if ($s && function_exists('finance_available') && finance_available()) $steps['payment'] = ['Paiement', 'file-text'];
    $steps['done'] = ["C'est prêt", 'rocket'];
    return $steps;
}
function welcome_next(array $steps, string $key): string
{
    $k = array_keys($steps); $i = array_search($key, $k, true);
    return $k[min(count($k) - 1, ($i === false ? 0 : $i + 1))];
}
function finish_onboarding(array $u): never
{
    q('UPDATE users SET onboarded_at=NOW() WHERE id=?', [$u['id']]);
    unset($_SESSION['wl_step']);
    if (($_POST['action'] ?? '') === 'finish') $_SESSION['tour_offer'] = 1;
    audit('onboarding_done');
    redirect('home');
}

function welcome_route(bool $isPost): never
{
    $u = require_login();
    if (!in_array($u['role'], ['parent', 'student'], true)) redirect('home');
    $s = $u['role'] === 'student' ? one('SELECT s.*, f.guardian_consent_at FROM students s JOIN families f ON f.id=s.family_id WHERE s.user_id=?', [$u['id']]) : pick_student($u);
    $steps = welcome_steps($u, $s);
    $step = (string)($_REQUEST['step'] ?? ($_SESSION['wl_step'] ?? 'intro'));
    if (!isset($steps[$step])) $step = 'intro';

    if ($isPost) {
        $act = (string)($_POST['action'] ?? 'next');
        if ($act === 'finish' || $act === 'skip_all') finish_onboarding($u);
        if ($act === 'save' && $step === 'contact' && $u['role'] === 'parent') {
            $name = post_str('display_name', 100); $email = post_str('email', 120); $phone = preg_replace('/[^0-9+ ().-]/', '', post_str('phone', 30));
            if ($name === '') { flash('Indiquez votre nom.', 'err'); redirect('welcome', ['step' => $step]); }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('Adresse e-mail invalide.', 'err'); redirect('welcome', ['step' => $step]); }
            q('UPDATE users SET display_name=?, phone=?, email=? WHERE id=?', [$name, $phone ?: null, $email ?: null, $u['id']]);
            $address = post_str('address', 255);
            try { q('UPDATE users SET address=?, notify_messages=? WHERE id=?', [$address ?: null, (int)!empty($_POST['notify_messages']), $u['id']]); } catch (PDOException) {}
            q('UPDATE students s JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=? SET s.parent_phone=COALESCE(?, s.parent_phone), s.parent_email=COALESCE(?, s.parent_email), s.parent_address=COALESCE(?, s.parent_address)',
                [$u['id'], $phone ?: null, $email ?: null, $address ?: null]);
            $notes = post_str('contact_notes', 500);
            if ($notes !== '' && $s) q('UPDATE students SET contact_notes=? WHERE id=?', [$notes, $s['id']]);
        }
        if ($act === 'save' && $step === 'child' && $u['role'] === 'parent' && $s) {
            $first = post_str('first_name', 80);
            if ($first === '') { flash('Indiquez le prénom de votre enfant.', 'err'); redirect('welcome', ['step' => $step]); }
            $bd = post_str('birth_date', 10); $bd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd) && strtotime($bd) < time() ? $bd : null;
            $grade = isset(CLASSES[$_POST['grade'] ?? '']) ? $_POST['grade'] : $s['grade'];
            q('UPDATE students SET first_name=?, grade=?, school=? WHERE id=?', [$first, $grade, post_str('school', 120) ?: null, $s['id']]);
            try {
                $needs = ['items' => array_values(array_intersect((array)($_POST['needs'] ?? []), ['dys', 'tdah', 'hpi', 'anxiety', 'other'])), 'acc' => array_values(array_intersect((array)($_POST['acc'] ?? []), ['pap', 'pps', 'pai', 'tiers_temps']))];
                q('UPDATE students SET last_name=?, birth_date=?, needs_json=?, accommodations=?, parent_notes=? WHERE id=?', [post_str('last_name', 80) ?: null, $bd, json_encode($needs), post_str('accommodations', 500) ?: null, post_str('parent_notes', 2000) ?: null, $s['id']]);
            } catch (PDOException) {}
            $sub = qn_latest('parent', (int)$s['id']);
            if ($sub) qn_save($sub, 'parent', ['p_first_name' => $first, 'p_last_name' => post_str('last_name', 80), 'p_class' => $grade, 'p_class_school' => post_str('school', 120)]);
        }
        if ($act === 'save' && $step === 'wishes' && $u['role'] === 'parent' && $s) {
            $subjOpts = wish_subject_options($s);
            $w = ['hours' => post_str('hours', 40),
                  'needs' => array_values(array_intersect((array)($_POST['needs'] ?? []), array_keys(WISH_NEEDS))),
                  'subjects' => array_values(array_intersect((array)($_POST['subjects'] ?? []), array_keys($subjOpts))),
                  'general' => !empty($_POST['general']),
                  'other' => post_str('other', 200), 'mode' => array_key_exists($_POST['mode'] ?? '', WISH_MODES) ? $_POST['mode'] : '', 'mode_other' => post_str('mode_other', 200),
                  'slots' => post_str('slots', 300), 'note' => post_str('note', 1000), 'at' => date('Y-m-d')];
            try { q('UPDATE students SET wishes_json=? WHERE id=?', [json_encode($w, JSON_UNESCAPED_UNICODE), $s['id']]); } catch (PDOException) {}
        }
        $next = welcome_next($steps, $step);
        $_SESSION['wl_step'] = $next;
        redirect('welcome', ['step' => $next]);
    }
    $_SESSION['wl_step'] = $step;
    $me = one('SELECT * FROM users WHERE id=?', [$u['id']]);
    $sub = $s ? qn_latest($u['role'] === 'parent' ? 'parent' : 'student', (int)$s['id']) : null;
    $extra = [];
    if ($step === 'payment' && $s) {
        try { $s += one('SELECT pay_frequency, pay_method FROM families WHERE id=?', [$s['family_id']]) ?? []; } catch (PDOException) {}
    }
    if ($step === 'tour' || $step === 'intro') $extra['tutor'] = $s ? one('SELECT u.display_name, u.id, u.avatar FROM users u JOIN family_members fm ON fm.user_id=u.id AND fm.family_id=? WHERE fm.member_role="tutor" LIMIT 1', [$s['family_id']]) : null;
    render('welcome', ['u' => $u, 'me' => $me, 's' => $s, 'steps' => $steps, 'step' => $step, 'sub' => $sub] + $extra, 'layout_min');
}
