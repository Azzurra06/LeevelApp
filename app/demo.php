<?php
declare(strict_types=1);
// Dossier de démonstration fictif (séparé : families.is_demo = 1). Mots de passe aléatoires, affichés une seule fois.

function create_demo(int $tutorId): array
{
    $pdo = db();
    $pw = ['demo.parent' => bin2hex(random_bytes(6)) . 'A1', 'demo.eleve' => bin2hex(random_bytes(6)) . 'B2'];
    $pdo->beginTransaction();
    q('INSERT INTO families (name, is_demo, guardian_consent_at) VALUES (?,1,NOW())', ['DÉMO - Famille fictive']);
    $fid = (int)$pdo->lastInsertId();
    q('INSERT INTO users (username, display_name, role, password_hash) VALUES (?,?,?,?)', ['demo.parent', 'Parent démo', 'parent', password_hash($pw['demo.parent'], PASSWORD_DEFAULT)]);
    $pid = (int)$pdo->lastInsertId();
    q('INSERT INTO users (username, display_name, role, password_hash) VALUES (?,?,?,?)', ['demo.eleve', 'Léo (démo)', 'student', password_hash($pw['demo.eleve'], PASSWORD_DEFAULT)]);
    $uid = (int)$pdo->lastInsertId();
    q('INSERT INTO family_members VALUES (?,?,?),(?,?,?),(?,?,?)', [$fid, $tutorId, 'tutor', $fid, $pid, 'parent', $fid, $uid, 'student']);
    q('INSERT INTO students (family_id, user_id, first_name, school) VALUES (?,?,?,?)', [$fid, $uid, 'Léo (démo)', 'Collège fictif']);
    $sid = (int)$pdo->lastInsertId();
    $pdo->commit();
    $sp = qn_submission('parent', $sid, $pid);
    qn_save($sp, 'parent', ['p_first_name' => 'Léo', 'p_hard_subjects' => ['maths', 'physique'], 'p_fav_subjects' => ['histgeo'], 'p_understands' => 3, 'p_retains' => 2,
        'p_applies' => 2, 'p_finishes_homework' => 'sometimes', 'p_main_difficulties' => ['organisation', 'methodo', 'memorisation'], 'p_wants_work' => 3, 'p_discouraged' => 4,
        'p_needs_reminding' => 'often', 'p_starts_alone' => 2, 'p_organizes_revisions' => 1, 'p_needs_presence' => 4, 'p_identifies_notions' => 2, 'p_asks_help' => 3,
        'p_computer' => 'yes', 'p_revision_freq' => 'before_tests', 'p_methods' => ['reread'], 'p_anticipates' => 'day_before', 'p_priority' => 'methode'], 6);
    qn_complete($sp);
    $ss = qn_submission('student', $sid, $uid);
    qn_save($ss, 'student', ['s_fav_subjects' => ['histgeo', 'eps'], 's_feel_school' => 3, 's_understands_teachers' => 4, 's_bored' => 3, 's_finds_hard' => 3, 's_stress' => 4,
        's_improve_one' => 'grades', 's_g_revises' => 'sometimes', 's_g_why_points' => 'no', 's_g_rewarded' => 'no', 's_reread' => 'yes', 's_exercises' => 'no', 's_flashcards' => 'no',
        's_hard_exercise' => 'skip', 's_20min' => 'yes', 's_computer' => 'yes', 's_asks_help' => 'sometimes', 's_visio_ok' => 'yes', 's_first_goal' => 'Avoir 12 en maths'], 6);
    qn_complete($ss);
    run_analysis($sid, $tutorId);
    return $pw;
}

/** Crée une famille démo (comptes parent + élève). Retourne [fid, sid, pid, uid, passwords]. */
function demo_family(int $tutorId, string $name, string $prefix, string $child, string $parentName, bool $consent): array
{
    $pdo = db();
    $pw = ["$prefix.parent" => bin2hex(random_bytes(5)) . 'Pa1', "$prefix.eleve" => bin2hex(random_bytes(5)) . 'El2'];
    q('INSERT INTO families (name, is_demo, guardian_consent_at) VALUES (?,1,' . ($consent ? 'NOW() - INTERVAL 21 DAY' : 'NULL') . ')', [$name]);
    $fid = (int)$pdo->lastInsertId();
    q('INSERT INTO users (username, display_name, role, password_hash) VALUES (?,?,?,?)', ["$prefix.parent", $parentName, 'parent', password_hash($pw["$prefix.parent"], PASSWORD_DEFAULT)]);
    $pid = (int)$pdo->lastInsertId();
    q('INSERT INTO users (username, display_name, role, password_hash) VALUES (?,?,?,?)', ["$prefix.eleve", $child, 'student', password_hash($pw["$prefix.eleve"], PASSWORD_DEFAULT)]);
    $uid = (int)$pdo->lastInsertId();
    q('INSERT INTO family_members VALUES (?,?,?),(?,?,?),(?,?,?)', [$fid, $tutorId, 'tutor', $fid, $pid, 'parent', $fid, $uid, 'student']);
    q('INSERT INTO students (family_id, user_id, first_name, school) VALUES (?,?,?,?)', [$fid, $uid, $child, 'Collège fictif']);
    $sid = (int)$pdo->lastInsertId();
    if ($consent) q('UPDATE families SET guardian_consent_user_id=? WHERE id=?', [$pid, $fid]);
    return [$fid, $sid, $pid, $uid, $pw];
}

/** Dossier vide : comme juste après la création du dossier famille. */
function create_demo_new(int $tutorId): array
{
    [, , , , $pw] = demo_family($tutorId, 'DÉMO - Nouveau dossier (Noah)', 'demo-nouveau', 'Noah (démo)', 'Parent de Noah (démo)', false);
    return $pw;
}

/** Dossier « déjà suivi » depuis 3 semaines : questionnaires, analyse, priorités, séances, objectifs, retours. */
function create_demo_followed(int $tutorId): array
{
    [$fid, $sid, $pid, $uid, $pw] = demo_family($tutorId, 'DÉMO - Suivi en cours (Inès)', 'demo-suivi', 'Inès (démo)', 'Parent d\'Inès (démo)', true);
    $sp = qn_submission('parent', $sid, $pid);
    qn_save($sp, 'parent', ['p_first_name' => 'Inès', 'p_fav_subjects' => ['francais', 'arts'], 'p_hard_subjects' => ['maths', 'physique'], 'p_averages' => 'Maths 8, physique 9, français 13',
        'p_main_goal' => 'brevet', 'p_since' => 'one_two', 'p_results_trend' => 'down_bit', 'p_understands' => 3, 'p_retains' => 2, 'p_applies' => 2, 'p_finishes_homework' => 'often',
        'p_main_difficulties' => ['organisation', 'memorisation', 'confiance'], 'p_wants_work' => 3, 'p_discouraged' => 4, 'p_bad_grade' => 'discouraged', 'p_needs_reminding' => 'often',
        'p_motivators' => 'Le dessin, les projets concrets', 'p_interests' => 'Dessin, mangas, danse', 'p_starts_alone' => 3, 'p_organizes_revisions' => 1, 'p_needs_presence' => 3,
        'p_identifies_notions' => 2, 'p_asks_help' => 2, 'p_computer' => 'yes', 'p_homework_time' => '30_60', 'p_revision_freq' => 'before_tests', 'p_methods' => ['reread'],
        'p_anticipates' => 'day_before', 'p_conditions' => 'ok', 'p_distractions' => ['phone', 'social'], 'p_priority' => 'methode',
        'p_hoped_results' => 'Retrouver 12 de moyenne et aborder le brevet sereinement', 'p_availability' => 'Lundi soir, mercredi après-midi, samedi matin'], 6);
    qn_complete($sp);
    $ss = qn_submission('student', $sid, $uid);
    qn_save($ss, 'student', ['s_fav_subjects' => ['francais', 'arts'], 's_least_subjects' => ['maths'], 's_hobbies' => 'Dessiner, la danse', 's_after_3e' => 'lycee_gen',
        's_feel_school' => 3, 's_understands_teachers' => 3, 's_bored' => 3, 's_finds_hard' => 3, 's_stress' => 5, 's_improve_one' => 'tests',
        's_t_blank' => 'yes', 's_t_time' => 'sometimes', 's_t_prepare' => 'no', 's_how_revise' => 'Je relis la veille', 's_reread' => 'yes', 's_exercises' => 'sometimes',
        's_flashcards' => 'no', 's_hard_exercise' => 'answer', 's_alone_or_not' => 'depends', 's_20min' => 'yes', 's_computer' => 'yes', 's_follow_instructions' => 'yes',
        's_asks_help' => 'sometimes', 's_visio_ok' => 'yes', 's_succeed' => 'Avoir mon brevet avec mention', 's_expect' => 'Comprendre comment réviser', 's_first_goal' => 'Ne plus avoir de trou en contrôle'], 6);
    qn_complete($ss);
    q('UPDATE questionnaire_submissions SET completed_at = NOW() - INTERVAL 21 DAY WHERE id IN (?,?)', [$sp['id'], $ss['id']]);

    q('INSERT INTO tutor_observations (student_id, author_id, axis, adjustment, content, visibility, created_at) VALUES
        (?,?,?,?,?,?, NOW() - INTERVAL 20 DAY), (?,?,?,?,?,?, NOW() - INTERVAL 12 DAY), (?,?,?,?,?,?, NOW() - INTERVAL 4 DAY)', [
        $sid, $tutorId, 'comprehension', -10, 'Comprend vite quand on passe par un schéma : les notions de base sont là.', 'private',
        $sid, $tutorId, 'methodo', 0, 'Les fiches de révision commencent à être utilisées spontanément.', 'shared',
        $sid, $tutorId, 'motivation', -10, 'Beaucoup plus impliquée depuis les défis hebdomadaires.', 'private']);
    run_analysis($sid, $tutorId);
    foreach ([['evaluations', 'Brevet blanc en janvier'], ['methodo', 'Planning + fiches'], ['motivation', 'Valoriser chaque progrès']] as $i => [$a, $n]) {
        q('INSERT INTO assessment_priorities (student_id, axis, rank_no, note, updated_by) VALUES (?,?,?,?,?)', [$sid, $a, $i + 1, $n, $tutorId]);
    }

    // Planning : semaine 1 il y a 3 semaines → S1-S2 réalisées, S3 en cours, S4 à venir
    $reco = recommendations(['evaluations', 'methodo', 'motivation'], latest_analysis($sid));
    $mon = new DateTime('monday this week'); $mon->modify('-14 days');
    $slots = [[0, '17:00'], [2, '14:00'], [5, '10:00']];
    $now = time();
    foreach (plan_weeks() as $w => $pw8) {
        foreach ($pw8['slots'] as $i => [$mode, $dur]) {
            $dt = (clone $mon)->modify('+' . (($w - 1) * 7 + $slots[$i][0]) . ' days');
            [$h, $m] = explode(':', $slots[$i][1]); $dt->setTime((int)$h, (int)$m);
            $status = $dt->getTimestamp() < $now ? 'done' : 'planned';
            if ($w === 2 && $i === 1) { $status = 'postponed'; }
            q('INSERT INTO sessions (student_id, week_no, title, starts_at, duration_min, mode, status, objectives) VALUES (?,?,?,?,?,?,?,?)',
                [$sid, $w, $pw8['title'], $dt->format('Y-m-d H:i:s'), $dur, $mode, $status, '• ' . $pw8['title'] . "\n• " . implode("\n• ", $reco['plan'][$w]['items'])]);
            $sessId = (int)db()->lastInsertId();
            if ($status === 'done' && $i === 0) {
                q('INSERT INTO session_notes (session_id, author_id, content, visibility) VALUES (?,?,?,?)', [$sessId, $tutorId,
                    $w === 1 ? 'Séance de découverte très positive. Analyse de deux copies de maths.' : 'Mise en place du planning hebdomadaire, Inès l\'a bien pris en main.', $w === 1 ? 'shared' : 'private']);
            }
        }
    }

    // Objectifs à différents stades
    $target = (clone $mon)->modify('+27 days')->format('Y-m-d');
    $goals = [
        ['Utiliser un planning hebdomadaire', 'Noter devoirs et révisions de la semaine et s\'y tenir.', 'methodo', 'acquired', 100, 0, 'Validé : planning tenu deux semaines de suite.', 'understood', true],
        ['Réaliser une fiche de révision efficace', 'Savoir résumer un chapitre en une fiche claire.', 'methodo', 'in_progress', 60, 1, 'Bonne structure, encore un peu longues.', 'practice', true],
        ['Anticiper ses révisions trois jours avant un contrôle', 'Planifier J-3, J-2, J-1 pour chaque contrôle.', 'evaluations', 'in_progress', 40, 1, null, 'practice', false],
        ['Maîtriser les calculs avec les fractions', 'Additions, multiplications et simplifications sans erreur.', 'comprehension', 'rework', 30, 0, 'À revoir : confusion sur la mise au même dénominateur.', 'not_understood', false],
        ['Relever un défi par semaine', 'Choisir et réussir un petit défi hebdomadaire.', 'motivation', 'in_progress', 70, 1, null, 'understood', true],
        ['Savoir préparer un contrôle de manière autonome', 'Utiliser la checklist contrôle sans aide.', 'evaluations', 'todo', 0, 0, null, null, false],
    ];
    $gids = [];
    foreach ($goals as [$t, $d, $c, $st, $pr, $mis, $com, $feel, $done]) {
        q('INSERT INTO goals (student_id, title, description, category, target_date, status, progress, tutor_comment, is_mission, student_done_at, student_feeling, created_by, created_at)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?, NOW() - INTERVAL 18 DAY)', [$sid, $t, $d, $c, $target, $st, $pr, $com, $mis, $done ? date('Y-m-d H:i:s', $now - 86400 * 3) : null, $feel, $tutorId]);
        $gids[] = (int)db()->lastInsertId();
    }
    $entries = [
        [$gids[0], 'task_done', null, null, 12], [$gids[0], 'self_eval', 'understood', null, 12], [$gids[1], 'self_eval', 'practice', null, 6],
        [null, 'feeling', 'good', 'Le contrôle de français s\'est bien passé !', 5], [$gids[3], 'self_eval', 'not_understood', null, 4],
        [null, 'help_request', null, 'Je bloque sur les fractions de l\'exercice 4', 2], [$gids[4], 'task_done', null, null, 1], [null, 'feeling', 'ok', null, 1],
    ];
    foreach ($entries as [$g, $type, $val, $com, $days]) {
        q('INSERT INTO progress_entries (student_id, user_id, goal_id, entry_type, value, comment, created_at) VALUES (?,?,?,?,?,?, NOW() - INTERVAL ? DAY)', [$sid, $uid, $g, $type, $val, $com, $days]);
    }
    q('UPDATE students SET shared_message=?, shared_strengths=?, shared_to_work=?, shared_updated_at=NOW() - INTERVAL 2 DAY WHERE id=?', [
        "Trois semaines déjà : Inès s'est bien approprié son planning et ses fiches. Prochaine étape : la méthode J-3 avant chaque contrôle et le travail sur les fractions.",
        "Organisation en net progrès\nTrès bonne expression écrite\nImpliquée dans les défis hebdomadaires",
        "Préparer les contrôles plus tôt (méthode J-3)\nFractions : mise au même dénominateur\nGestion du stress pendant les évaluations", $sid]);
    run_analysis($sid, $tutorId);
    return $pw;
}
