<?php
// Règles d'analyse pédagogique - explicites et modifiables.
// Chaque règle transforme une réponse en "niveau de besoin" entre 0 (faible) et 1 (élevé),
// pondéré par axe. Score d'axe = somme(besoin × poids) / somme(poids des règles répondues) × 100.
//   scale: 'asc'  → 5 = besoin élevé ; 'desc' → 1 = besoin élevé
//   map:   valeur => besoin
//   has:   option cochée (choix multiple) → besoin 1, sinon 0
//   strength: libellé affiché comme point fort si le besoin est ≤ 0.25
$yn = ['yes' => 0, 'sometimes' => 0.5, 'no' => 1];
$ynInv = ['yes' => 1, 'sometimes' => 0.5, 'no' => 0];

return [
    'rules' => [
        // ---------- Parent ----------
        ['src' => 'parent', 'key' => 'p_understands', 'scale' => 'desc', 'axes' => ['comprehension' => 3], 'label' => 'Compréhension des cours', 'strength' => 'Comprend bien ses cours (parent)'],
        ['src' => 'parent', 'key' => 'p_retains', 'scale' => 'desc', 'axes' => ['comprehension' => 2, 'evaluations' => 1], 'label' => 'Mémorisation des notions', 'strength' => 'Retient bien les notions (parent)'],
        ['src' => 'parent', 'key' => 'p_applies', 'scale' => 'desc', 'axes' => ['comprehension' => 2], 'label' => 'Application en exercice'],
        ['src' => 'parent', 'key' => 'p_results_trend', 'map' => ['down_a_lot' => 1, 'down_bit' => 0.6, 'stable' => 0.3, 'up' => 0], 'axes' => ['comprehension' => 1, 'evaluations' => 1], 'label' => 'Évolution des résultats', 'strength' => 'Résultats en progression'],
        ['src' => 'parent', 'key' => 'p_since', 'map' => ['long' => 1, 'one_two' => 0.6, 'recent' => 0.3, 'none' => 0], 'axes' => ['comprehension' => 1], 'label' => 'Ancienneté des difficultés'],
        ['src' => 'parent', 'key' => 'p_finishes_homework', 'map' => ['always' => 0, 'often' => 0.25, 'sometimes' => 0.6, 'rarely' => 1], 'axes' => ['methodo' => 1, 'autonomie' => 1, 'motivation' => 1], 'label' => 'Devoirs terminés', 'strength' => 'Termine ses devoirs'],
        ['src' => 'parent', 'key' => 'p_main_difficulties', 'has' => 'comprehension', 'axes' => ['comprehension' => 2], 'label' => 'Difficulté signalée : compréhension'],
        ['src' => 'parent', 'key' => 'p_main_difficulties', 'has' => 'memorisation', 'axes' => ['comprehension' => 1, 'evaluations' => 2], 'label' => 'Difficulté signalée : mémorisation'],
        ['src' => 'parent', 'key' => 'p_main_difficulties', 'has' => 'concentration', 'axes' => ['methodo' => 1, 'motivation' => 1], 'label' => 'Difficulté signalée : concentration'],
        ['src' => 'parent', 'key' => 'p_main_difficulties', 'has' => 'organisation', 'axes' => ['methodo' => 2], 'label' => 'Difficulté signalée : organisation'],
        ['src' => 'parent', 'key' => 'p_main_difficulties', 'has' => 'motivation', 'axes' => ['motivation' => 2], 'label' => 'Difficulté signalée : motivation'],
        ['src' => 'parent', 'key' => 'p_main_difficulties', 'has' => 'confiance', 'axes' => ['motivation' => 1, 'evaluations' => 1], 'label' => 'Difficulté signalée : confiance en soi'],
        ['src' => 'parent', 'key' => 'p_main_difficulties', 'has' => 'temps', 'axes' => ['methodo' => 2, 'evaluations' => 1], 'label' => 'Difficulté signalée : gestion du temps'],
        ['src' => 'parent', 'key' => 'p_main_difficulties', 'has' => 'methodo', 'axes' => ['methodo' => 2, 'evaluations' => 1], 'label' => 'Difficulté signalée : méthodologie'],
        ['src' => 'parent', 'key' => 'p_wants_work', 'scale' => 'desc', 'axes' => ['motivation' => 3], 'label' => 'Envie de travailler', 'strength' => 'A envie de travailler (parent)'],
        ['src' => 'parent', 'key' => 'p_discouraged', 'scale' => 'asc', 'axes' => ['motivation' => 2], 'label' => 'Découragement', 'strength' => 'Persévérant face aux difficultés'],
        ['src' => 'parent', 'key' => 'p_bad_grade', 'map' => ['rebounds' => 0, 'hides' => 0.6, 'angry' => 0.6, 'indifferent' => 0.8, 'discouraged' => 1], 'axes' => ['motivation' => 2], 'label' => 'Réaction à une mauvaise note', 'strength' => 'Veut se rattraper après une mauvaise note'],
        ['src' => 'parent', 'key' => 'p_needs_reminding', 'map' => ['never' => 0, 'sometimes' => 0.35, 'often' => 0.75, 'always' => 1], 'axes' => ['motivation' => 1, 'autonomie' => 2], 'label' => 'Besoin de relances'],
        ['src' => 'parent', 'key' => 'p_starts_alone', 'scale' => 'desc', 'axes' => ['autonomie' => 3], 'label' => 'Commence ses devoirs seul', 'strength' => 'Se met au travail seul'],
        ['src' => 'parent', 'key' => 'p_organizes_revisions', 'scale' => 'desc', 'axes' => ['methodo' => 2, 'evaluations' => 2], 'label' => 'Organisation des révisions', 'strength' => 'Sait organiser ses révisions'],
        ['src' => 'parent', 'key' => 'p_needs_presence', 'scale' => 'asc', 'axes' => ['autonomie' => 3], 'label' => "Besoin d'une présence"],
        ['src' => 'parent', 'key' => 'p_asks_help', 'scale' => 'desc', 'axes' => ['autonomie' => 1, 'comprehension' => 1], 'label' => "Demande de l'aide", 'strength' => "Sait demander de l'aide"],
        ['src' => 'parent', 'key' => 'p_computer', 'map' => ['yes' => 0, 'some' => 0.5, 'no' => 1], 'axes' => ['autonomie' => 1], 'label' => 'Travail sur ordinateur', 'strength' => "À l'aise sur ordinateur"],
        ['src' => 'parent', 'key' => 'p_revision_freq', 'map' => ['regular' => 0, 'before_tests' => 0.6, 'rarely' => 0.85, 'never' => 1], 'axes' => ['evaluations' => 3, 'methodo' => 1], 'label' => 'Fréquence des révisions', 'strength' => 'Révise régulièrement'],
        ['src' => 'parent', 'key' => 'p_methods', 'has' => 'none', 'axes' => ['methodo' => 2], 'label' => 'Pas de méthode de révision identifiée'],
        ['src' => 'parent', 'key' => 'p_anticipates', 'map' => ['yes' => 0, 'day_before' => 0.6, 'no' => 1], 'axes' => ['evaluations' => 2, 'methodo' => 1], 'label' => 'Anticipation des contrôles', 'strength' => 'Anticipe ses contrôles'],
        ['src' => 'parent', 'key' => 'p_conditions', 'map' => ['calm' => 0, 'ok' => 0.3, 'noisy' => 1], 'axes' => ['methodo' => 1], 'label' => 'Conditions de travail'],

        // ---------- Élève ----------
        ['src' => 'student', 'key' => 's_feel_school', 'scale' => 'desc', 'axes' => ['motivation' => 2], 'label' => 'Bien-être au collège (ressenti)', 'strength' => 'Se sent bien au collège'],
        ['src' => 'student', 'key' => 's_understands_teachers', 'scale' => 'desc', 'axes' => ['comprehension' => 3], 'label' => 'Comprend les explications (ressenti)', 'strength' => 'Comprend les explications des profs'],
        ['src' => 'student', 'key' => 's_bored', 'scale' => 'asc', 'axes' => ['motivation' => 2], 'label' => 'Ennui en cours'],
        ['src' => 'student', 'key' => 's_finds_hard', 'scale' => 'asc', 'axes' => ['comprehension' => 2], 'label' => 'Cours perçus comme difficiles'],
        ['src' => 'student', 'key' => 's_stress', 'scale' => 'asc', 'axes' => ['evaluations' => 2], 'label' => 'Stress avant les contrôles', 'strength' => 'Plutôt serein avant les contrôles'],
        ['src' => 'student', 'key' => 's_improve_one', 'map' => ['understand' => 1], 'axes' => ['comprehension' => 1], 'label' => 'Souhaite mieux comprendre les cours'],
        ['src' => 'student', 'key' => 's_improve_one', 'map' => ['motivation' => 1], 'axes' => ['motivation' => 1], 'label' => "Souhaite retrouver l'envie de travailler"],
        ['src' => 'student', 'key' => 's_improve_one', 'map' => ['homework' => 1], 'axes' => ['methodo' => 1], 'label' => 'Souhaite mieux gérer ses devoirs'],
        ['src' => 'student', 'key' => 's_improve_one', 'map' => ['tests' => 1, 'grades' => 0.6], 'axes' => ['evaluations' => 1], 'label' => 'Souhaite progresser sur les contrôles / notes'],
        ['src' => 'student', 'key' => 's_g_revises', 'map' => $yn, 'axes' => ['evaluations' => 2], 'label' => 'Révise avant les contrôles'],
        ['src' => 'student', 'key' => 's_g_why_points', 'map' => $yn, 'axes' => ['evaluations' => 1, 'comprehension' => 1], 'label' => 'Comprend pourquoi il perd des points'],
        ['src' => 'student', 'key' => 's_g_rewarded', 'map' => $yn, 'axes' => ['motivation' => 1], 'label' => 'Efforts perçus comme récompensés'],
        ['src' => 'student', 'key' => 's_u_lost', 'map' => ['start' => 1, 'exercises' => 0.7, 'alone' => 0.6, 'tests' => 0.5], 'axes' => ['comprehension' => 2], 'label' => 'Moment où il se sent perdu'],
        ['src' => 'student', 'key' => 's_u_dare_ask', 'map' => $yn, 'axes' => ['autonomie' => 1, 'comprehension' => 1], 'label' => 'Ose poser des questions', 'strength' => 'Ose poser des questions'],
        ['src' => 'student', 'key' => 's_h_start', 'map' => $ynInv, 'axes' => ['autonomie' => 2, 'motivation' => 1], 'label' => "Difficulté à s'y mettre"],
        ['src' => 'student', 'key' => 's_h_where', 'map' => $yn, 'axes' => ['methodo' => 2], 'label' => 'Sait par quoi commencer'],
        ['src' => 'student', 'key' => 's_h_time', 'map' => $ynInv, 'axes' => ['methodo' => 2], 'label' => 'Sentiment de manquer de temps'],
        ['src' => 'student', 'key' => 's_t_blank', 'map' => $ynInv, 'axes' => ['evaluations' => 2], 'label' => 'Trous de mémoire en contrôle'],
        ['src' => 'student', 'key' => 's_t_time', 'map' => $yn, 'axes' => ['evaluations' => 1, 'methodo' => 1], 'label' => 'Termine ses contrôles à temps'],
        ['src' => 'student', 'key' => 's_t_prepare', 'map' => $yn, 'axes' => ['evaluations' => 2, 'methodo' => 1], 'label' => 'Sait préparer un contrôle'],
        ['src' => 'student', 'key' => 's_reread', 'map' => ['yes' => 0.7, 'sometimes' => 0.4, 'no' => 0.2], 'axes' => ['methodo' => 1], 'label' => 'Révision limitée à la relecture'],
        ['src' => 'student', 'key' => 's_exercises', 'map' => $yn, 'axes' => ['methodo' => 1, 'evaluations' => 2], 'label' => "S'entraîne avec des exercices", 'strength' => "S'entraîne avec des exercices"],
        ['src' => 'student', 'key' => 's_flashcards', 'map' => $yn, 'axes' => ['methodo' => 1, 'evaluations' => 1], 'label' => 'Utilise des fiches', 'strength' => 'Utilise des fiches'],
        ['src' => 'student', 'key' => 's_hard_exercise', 'map' => ['keep_trying' => 0, 'ask' => 0.2, 'answer' => 0.6, 'skip' => 0.7, 'give_up' => 1], 'axes' => ['autonomie' => 2, 'motivation' => 1], 'label' => 'Réaction face à un exercice difficile', 'strength' => 'Persévère face à un exercice difficile'],
        ['src' => 'student', 'key' => 's_20min', 'map' => $yn, 'axes' => ['autonomie' => 3], 'label' => 'Travaille 20 min sans aide', 'strength' => 'Peut travailler 20 min sans aide'],
        ['src' => 'student', 'key' => 's_computer', 'map' => $yn, 'axes' => ['autonomie' => 1], 'label' => 'Utilise un ordinateur pour travailler'],
        ['src' => 'student', 'key' => 's_follow_instructions', 'map' => $yn, 'axes' => ['autonomie' => 2, 'comprehension' => 1], 'label' => 'Suit une consigne seul', 'strength' => 'Suit une consigne seul'],
        ['src' => 'student', 'key' => 's_asks_help', 'map' => $yn, 'axes' => ['autonomie' => 1], 'label' => "Demande de l'aide facilement"],
        ['src' => 'student', 'key' => 's_visio_ok', 'map' => $yn, 'axes' => ['autonomie' => 1], 'label' => "À l'aise en visio", 'strength' => "À l'aise en visio"],
    ],

    // Paires de questions comparables (besoin 0..1 de chaque côté). Écart ≥ seuil = divergence.
    'pairs' => [
        ['p' => 'p_understands', 's' => 's_understands_teachers', 'label' => 'Compréhension des cours'],
        ['p' => 'p_starts_alone', 's' => 's_20min', 'label' => 'Capacité à travailler seul'],
        ['p' => 'p_asks_help', 's' => 's_asks_help', 'label' => "Demander de l'aide"],
        ['p' => 'p_computer', 's' => 's_computer', 'label' => 'Travail sur ordinateur'],
        ['p' => 'p_revision_freq', 's' => 's_g_revises', 'label' => 'Révisions avant les contrôles'],
        ['p' => 'p_wants_work', 's' => 's_bored', 'label' => 'Engagement en cours'],
    ],
    'pair_threshold' => 0.5,
    'axis_divergence_threshold' => 30,
    'min_rules_per_axis' => 2,
    'priority_threshold' => 50,
];
