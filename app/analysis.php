<?php
declare(strict_types=1);
// Analyse pédagogique déterministe (aucune IA). Indicatif : ce n'est pas un diagnostic.

function axis_templates(): array
{
    return [
        'comprehension' => [
            'profile' => 'Difficultés de compréhension',
            'methods' => ['Diagnostic des prérequis', 'Explications illustrées (schémas, exemples concrets)', 'Reformulation par l\'élève', 'Progression du simple au complexe'],
            'exercises' => ['Exercices progressifs à difficulté croissante', 'Exercices « à trous » guidés', 'Explique-moi : l\'élève réexplique une notion'],
            'supports' => ['Carte des compétences acquises', 'Fiches « notion clé » illustrées'],
            'tools' => [['icon' => 'brain', 'title' => 'Mes notions clés', 'desc' => 'Les notions qu\'on consolide ensemble, une par une.'], ['icon' => 'lightbulb', 'title' => 'Explique-le à ta façon', 'desc' => 'Reformule une notion en 3 phrases : c\'est le meilleur test.']],
            'goals' => [['Identifier les notions non acquises', 'Repérer avec la tutrice les prérequis à consolider en priorité.'], ['Réexpliquer une notion avec ses mots', 'Être capable de reformuler une notion vue en séance.']],
            'plan' => ['Tests de prérequis dans les matières difficiles', 'Fiches notions illustrées', 'Exercices progressifs en autonomie guidée', 'Point sur les notions acquises / à revoir'],
        ],
        'methodo' => [
            'profile' => 'Manque de méthodologie',
            'methods' => ['Planning de révision', 'Fiches méthodes', 'Décomposition des tâches', 'Routine de travail courte et régulière'],
            'exercises' => ['Construire une fiche de révision', 'Flashcards', 'Quiz de mémorisation active'],
            'supports' => ['Planning hebdomadaire', 'Checklist des contrôles', 'Modèle de fiche de révision'],
            'tools' => [['icon' => 'calendar', 'title' => 'Mon planning de révision', 'desc' => 'Prévoir ses révisions sur la semaine, sans surcharge.'], ['icon' => 'list-checks', 'title' => 'Checklist contrôle', 'desc' => 'Les étapes pour préparer un contrôle sereinement.']],
            'goals' => [['Utiliser un planning hebdomadaire', 'Noter devoirs et révisions de la semaine et s\'y tenir.'], ['Réaliser une fiche de révision efficace', 'Savoir résumer un chapitre en une fiche claire.']],
            'plan' => ['Observation des méthodes actuelles', 'Mise en place du planning et des fiches', 'Utilisation autonome du planning (visio)', 'Bilan : quelles méthodes gardons-nous ?'],
        ],
        'motivation' => [
            'profile' => 'Motivation à relancer',
            'methods' => ['Objectifs courts et atteignables', 'Choix d\'activités par l\'élève', 'Liens avec ses centres d\'intérêt', 'Valorisation des progrès'],
            'exercises' => ['Défis hebdomadaires', 'Activités interactives (quiz, jeux de rôle)', 'Exercices concrets liés au quotidien'],
            'supports' => ['Suivi des réussites', 'Mission de la semaine'],
            'tools' => [['icon' => 'rocket', 'title' => 'Défi de la semaine', 'desc' => 'Un petit défi à relever, choisi avec toi.'], ['icon' => 'sparkles', 'title' => 'Mes réussites', 'desc' => 'Tout ce que tu as déjà réussi, pour garder le cap.']],
            'goals' => [['Relever un défi par semaine', 'Choisir et réussir un petit défi hebdomadaire.'], ['Fixer son propre objectif court', 'Définir lui-même un objectif atteignable en une semaine.']],
            'plan' => ['Découvrir ses centres d\'intérêt et premiers défis', 'Objectifs courts choisis par l\'élève', 'Défi à distance en autonomie', 'Retour sur les réussites du mois'],
        ],
        'autonomie' => [
            'profile' => 'Autonomie à développer',
            'methods' => ['Exercices guidés puis autonomes', 'Retrait progressif de l\'aide', 'Autoévaluation régulière'],
            'exercises' => ['Petites missions indépendantes (15-20 min)', 'Checklist de travail', 'Exercices avec correction autonome'],
            'supports' => ['Checklist de travail', 'Suivi de l\'autonomie', 'Autoévaluation après chaque mission'],
            'tools' => [['icon' => 'list-checks', 'title' => 'Ma checklist de travail', 'desc' => 'Les étapes pour démarrer seul : je lis, je fais, je vérifie.'], ['icon' => 'timer', 'title' => 'Mission 20 minutes', 'desc' => 'Une mission courte à faire seul, puis tu dis comment ça s\'est passé.']],
            'goals' => [['Réaliser un exercice sans aide', 'Mener un exercice de bout en bout sans intervention.'], ['Commencer ses devoirs seul', 'Se mettre au travail sans relance, avec la checklist.']],
            'plan' => ['Observer le niveau d\'autonomie', 'Exercices guidés puis semi-autonomes', 'Missions à distance et autoévaluation', 'Bilan de l\'autonomie à distance'],
        ],
        'evaluations' => [
            'profile' => 'Préparation des évaluations',
            'methods' => ['Révisions espacées', 'Mémorisation active (se tester plutôt que relire)', 'Simulation de contrôle', 'Gestion du stress et du temps'],
            'exercises' => ['Contrôles blancs courts', 'Quiz de mémorisation', 'Analyse d\'une copie corrigée'],
            'supports' => ['Checklist des contrôles', 'Calendrier J-3 / J-1', 'Fiches de brevet'],
            'tools' => [['icon' => 'flag', 'title' => 'Prépa contrôle J-3', 'desc' => 'Réviser en trois jours plutôt que la veille.'], ['icon' => 'file-text', 'title' => 'Analyse de copie', 'desc' => 'Comprendre où sont passés les points pour les récupérer.']],
            'goals' => [['Anticiper ses révisions trois jours avant un contrôle', 'Planifier J-3, J-2, J-1 pour chaque contrôle.'], ['Savoir préparer un contrôle de manière autonome', 'Utiliser la checklist contrôle sans aide.']],
            'plan' => ['Analyse de copies récentes', 'Méthode J-3 et quiz de mémorisation', 'Contrôle blanc court en visio', 'Bilan et préparation des prochaines évaluations'],
        ],
    ];
}

function plan_weeks(): array
{
    return [
        1 => ['title' => 'Diagnostic et découverte', 'slots' => [['presentiel', 120], ['presentiel', 120], ['presentiel', 120]]],
        2 => ['title' => 'Méthodologie et organisation', 'slots' => [['presentiel', 120], ['presentiel', 120], ['presentiel', 120]]],
        3 => ['title' => "Première évaluation de l'autonomie à distance", 'slots' => [['presentiel', 120], ['presentiel', 120], ['visio', 120]]],
        4 => ['title' => 'Bilan du premier mois', 'slots' => [['presentiel', 120], ['visio', 120], ['visio', 120]]],
    ];
}

function rules_config(): array { static $r = null; return $r ??= require APP_ROOT . '/app/analysis_rules.php'; }

function rule_need(array $rule, mixed $v): ?float
{
    if (isset($rule['has'])) return in_array($rule['has'], (array)$v, true) ? 1.0 : 0.0;
    if (isset($rule['scale'])) {
        if (!is_int($v)) return null;
        return $rule['scale'] === 'asc' ? ($v - 1) / 4 : (5 - $v) / 4;
    }
    if (is_array($v)) { // choix multiples : besoin le plus fort parmi les réponses
        $m = array_intersect_key($rule['map'], array_flip(array_filter($v, 'is_string')));
        return $m ? (float)max($m) : null;
    }
    if (!isset($rule['map'][$v])) return null;
    return (float)$rule['map'][$v];
}

/** Réponses visibles de chaque questionnaire + métadonnées. */
function load_answers(int $studentId): array
{
    $out = [];
    foreach (['parent', 'student'] as $code) {
        $sub = qn_latest($code, $studentId);
        $ans = $sub ? qn_answers((int)$sub['id']) : [];
        $qs = qn_questions(qn_config($code));
        $vis = array_filter($ans, fn($v, $k) => isset($qs[$k]) && qn_visible($qs[$k], $ans), ARRAY_FILTER_USE_BOTH);
        $out[$code] = ['sub' => $sub, 'answers' => $vis, 'all' => $ans, 'questions' => $qs];
    }
    return $out;
}

function compute_analysis(int $studentId): array
{
    $cfg = rules_config();
    $data = load_answers($studentId);
    $axes = [];
    foreach (AXES as $a => $l) $axes[$a] = ['num' => 0.0, 'den' => 0.0, 'n' => 0, 'contribs' => [], 'src' => ['parent' => [0.0, 0.0], 'student' => [0.0, 0.0]]];
    $missing = []; $skipped = []; $strengths = [];

    foreach ($cfg['rules'] as $rule) {
        $src = $rule['src'];
        $qs = $data[$src]['questions'];
        $question = $qs[$rule['key']] ?? null;
        if (!$question) continue;
        $answers = $data[$src]['answers'];
        if (!qn_visible($question, $data[$src]['all'])) continue; // question conditionnelle non affichée
        $v = $answers[$rule['key']] ?? null;
        if ($v === null) { $missing[$src . ':' . $rule['key']] = ['src' => $src, 'label' => $question['label']]; continue; }
        if ($v === SKIP) { $skipped[$rule['key']] = $question['label']; continue; }
        $need = rule_need($rule, $v);
        if ($need === null) continue;
        if ($need <= 0.25 && !empty($rule['strength'])) $strengths[$rule['strength']] = true;
        foreach ($rule['axes'] as $axis => $w) {
            $axes[$axis]['num'] += $need * $w;
            $axes[$axis]['den'] += $w;
            $axes[$axis]['n']++;
            $axes[$axis]['src'][$src][0] += $need * $w;
            $axes[$axis]['src'][$src][1] += $w;
            $axes[$axis]['contribs'][] = ['label' => $rule['label'], 'src' => $src, 'answer' => qn_display($question, $v), 'need' => round($need, 2), 'weight' => $w, 'points' => round($need * $w, 2)];
        }
    }

    // Observations validées de la tutrice (ajustement ± points sur un axe)
    $obs = all('SELECT axis, adjustment, content, created_at FROM tutor_observations WHERE student_id=? AND axis IS NOT NULL AND adjustment<>0', [$studentId]);

    $scores = [];
    foreach ($axes as $a => $ax) {
        $base = ($ax['n'] >= $cfg['min_rules_per_axis'] && $ax['den'] > 0) ? $ax['num'] / $ax['den'] * 100 : null;
        $adj = 0;
        foreach ($obs as $o) if ($o['axis'] === $a) {
            $adj += (int)$o['adjustment'];
            $axes[$a]['contribs'][] = ['label' => 'Observation tutrice : ' . mb_substr($o['content'], 0, 120), 'src' => 'tutor', 'answer' => ($o['adjustment'] > 0 ? '+' : '') . $o['adjustment'] . ' pts', 'need' => null, 'weight' => null, 'points' => null];
        }
        $score = $base === null ? null : (int)round(max(0, min(100, $base + $adj)));
        $side = fn($s) => $ax['src'][$s][1] > 0 ? (int)round($ax['src'][$s][0] / $ax['src'][$s][1] * 100) : null;
        usort($axes[$a]['contribs'], fn($x, $y) => ($y['points'] ?? 99) <=> ($x['points'] ?? 99));
        $scores[$a] = ['score' => $score, 'base' => $base === null ? null : (int)round($base), 'adjust' => $adj, 'parent' => $side('parent'), 'student' => $side('student'), 'n' => $ax['n'], 'contribs' => $axes[$a]['contribs']];
    }

    // Divergences
    $div = [];
    $rulesByKey = [];
    foreach ($cfg['rules'] as $r) $rulesByKey[$r['key']] ??= $r;
    foreach ($cfg['pairs'] as $p) {
        $pv = $data['parent']['answers'][$p['p']] ?? null; $sv = $data['student']['answers'][$p['s']] ?? null;
        if ($pv === null || $sv === null || $pv === SKIP || $sv === SKIP) continue;
        $pn = rule_need($rulesByKey[$p['p']], $pv); $sn = rule_need($rulesByKey[$p['s']], $sv);
        if ($pn === null || $sn === null) continue;
        $gap = abs($pn - $sn);
        $item = ['label' => $p['label'], 'parent' => qn_display($data['parent']['questions'][$p['p']], $pv), 'student' => qn_display($data['student']['questions'][$p['s']], $sv), 'gap' => round($gap, 2)];
        $item['type'] = $gap >= $cfg['pair_threshold'] ? 'divergence' : 'convergence';
        $item['note'] = $gap >= $cfg['pair_threshold'] ? ($pn > $sn ? 'Le parent perçoit plus de difficulté que l\'élève.' : 'L\'élève perçoit plus de difficulté que le parent.') : 'Perceptions proches.';
        $div[] = $item;
    }
    foreach ($scores as $a => $s) {
        if ($s['parent'] !== null && $s['student'] !== null && abs($s['parent'] - $s['student']) >= $cfg['axis_divergence_threshold']) {
            $div[] = ['label' => AXES[$a] . ' (score global)', 'parent' => $s['parent'] . '/100', 'student' => $s['student'] . '/100', 'gap' => abs($s['parent'] - $s['student']) / 100, 'type' => 'divergence',
                'note' => $s['parent'] > $s['student'] ? 'Besoin perçu plus fort par le parent.' : 'Besoin perçu plus fort par l\'élève.'];
        }
    }

    // Priorités automatiques
    $ranked = array_filter($scores, fn($s) => $s['score'] !== null);
    uasort($ranked, fn($x, $y) => $y['score'] <=> $x['score']);
    $auto = array_slice(array_keys($ranked), 0, 2);
    $third = array_keys($ranked)[2] ?? null;
    if ($third && $ranked[$third]['score'] >= $cfg['priority_threshold'] + 5) $auto[] = $third;

    // Points forts complémentaires
    foreach ($ranked as $a => $s) if ($s['score'] < 35) $strengths['Point d\'appui : ' . mb_strtolower(AXES[$a])] = true;
    $fav = array_unique(array_merge((array)($data['parent']['answers']['p_fav_subjects'] ?? []), (array)($data['student']['answers']['s_fav_subjects'] ?? [])));
    if ($fav) {
        $opts = $data['student']['questions']['s_fav_subjects']['options'];
        $strengths['Matières appréciées : ' . implode(', ', array_map(fn($x) => $opts[$x] ?? $x, $fav))] = true;
    }

    // Difficultés à explorer
    $explore = [];
    foreach ($scores as $a => $s) foreach ($s['contribs'] as $c) if (($c['need'] ?? 0) >= 0.75 && ($c['weight'] ?? 0) >= 2) $explore[$c['label']] = ($c['src'] === 'parent' ? 'Parent' : 'Élève') . ' : ' . $c['answer'];
    foreach ($div as $d) if ($d['type'] === 'divergence') $explore['Écart de perception - ' . $d['label']] = $d['note'];
    $impr = $data['student']['answers']['s_improve_one'] ?? null;
    if ($impr && $impr !== SKIP) $explore['Priorité exprimée par l\'élève'] = qn_display($data['student']['questions']['s_improve_one'], $impr);
    $pp = $data['parent']['answers']['p_priority'] ?? null;
    if ($pp) $explore['Priorité exprimée par le parent'] = qn_display($data['parent']['questions']['p_priority'], $pp);

    $status = [];
    foreach (['parent', 'student'] as $code) {
        $sub = $data[$code]['sub'];
        $status[$code] = $sub ? $sub['status'] : 'none';
        if (!$sub || $sub['status'] !== 'completed') $missing['_' . $code] = ['src' => $code, 'label' => 'Questionnaire ' . ($code === 'parent' ? 'parent' : 'élève') . ($sub ? ' non terminé' : ' non commencé')];
    }

    return [
        'scores' => $scores, 'auto_priorities' => $auto, 'strengths' => array_keys($strengths), 'explore' => $explore,
        'divergences' => $div, 'missing' => array_values($missing), 'skipped' => array_values($skipped), 'status' => $status,
    ];
}

/** Recalcule et enregistre l'analyse. */
function run_analysis(int $studentId, ?int $by): array
{
    $res = compute_analysis($studentId);
    $scores = array_map(fn($s) => $s['score'], $res['scores']);
    q('INSERT INTO pedagogical_assessments (student_id, scores_json, details_json, computed_by) VALUES (?,?,?,?)',
        [$studentId, json_encode($scores), json_encode($res, JSON_UNESCAPED_UNICODE), $by]);
    return $res;
}

function latest_analysis(int $studentId): ?array
{
    $r = one('SELECT details_json, computed_at FROM pedagogical_assessments WHERE student_id=? ORDER BY id DESC LIMIT 1', [$studentId]);
    if (!$r) return null;
    $d = json_decode($r['details_json'], true);
    $d['computed_at'] = $r['computed_at'];
    return $d;
}

/** Priorités effectives : manuelles si définies par la tutrice, sinon automatiques. */
function effective_priorities(int $studentId, ?array $analysis = null): array
{
    $manual = all('SELECT axis, note FROM assessment_priorities WHERE student_id=? ORDER BY rank_no', [$studentId]);
    if ($manual) return ['source' => 'manual', 'axes' => array_column($manual, 'axis'), 'notes' => array_column($manual, 'note', 'axis')];
    $analysis ??= latest_analysis($studentId);
    return ['source' => 'auto', 'axes' => $analysis['auto_priorities'] ?? [], 'notes' => []];
}

/** Recommandations construites à partir des priorités (modèles prédéfinis). */
function recommendations(array $prioAxes, ?array $analysis): array
{
    $tpl = axis_templates();
    $out = ['profiles' => [], 'methods' => [], 'exercises' => [], 'supports' => [], 'tools' => [], 'goals' => [], 'plan' => []];
    foreach ($prioAxes as $a) {
        if (!isset($tpl[$a])) continue;
        $t = $tpl[$a];
        $out['profiles'][] = $t['profile'];
        foreach (['methods', 'exercises', 'supports'] as $k) $out[$k] = array_merge($out[$k], $t[$k]);
        $out['tools'] = array_merge($out['tools'], $t['tools']);
        foreach ($t['goals'] as $g) $out['goals'][] = ['title' => $g[0], 'description' => $g[1], 'category' => $a];
    }
    foreach (plan_weeks() as $w => $pw) {
        $items = [];
        foreach ($prioAxes as $a) if (isset($tpl[$a])) $items[] = $tpl[$a]['plan'][$w - 1];
        $out['plan'][$w] = ['title' => $pw['title'], 'items' => $items];
    }
    $names = array_map(fn($a) => mb_strtolower(AXES[$a]), $prioAxes);
    if (!$analysis || !$prioAxes) {
        $out['summary'] = 'Analyse en attente : complétez les questionnaires pour obtenir une synthèse.';
    } else {
        $last = array_pop($names);
        $txt = 'D\'après les réponses croisées du parent et de l\'élève, l\'accompagnement portera en priorité sur ' . ($names ? implode(', ', $names) . ' et ' : '') . $last . '. ';
        if ($analysis['strengths']) $txt .= 'Points d\'appui : ' . mb_strtolower(implode(' ; ', array_slice($analysis['strengths'], 0, 3))) . '. ';
        $nd = count(array_filter($analysis['divergences'], fn($d) => $d['type'] === 'divergence'));
        if ($nd) $txt .= "$nd écart(s) de perception à explorer ensemble. ";
        $txt .= 'Ces indications sont un point de départ, à confirmer par l\'observation en séance.';
        $out['summary'] = $txt;
    }
    return $out;
}
