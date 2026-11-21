<?php
// Questionnaire parent - configuration déclarative
// Types : single, multi, multi_prio, select, scale, text, textarea, grades. 'half' => true : question affichée sur une demi-largeur.
// Les clés existantes sont conservées (réponses déjà enregistrées) ; les questions retirées restent en base, non affichées.
$lv = require __DIR__ . '/_levels.php';
$subjects = array_map(fn($x) => $x[0], $lv['subjects']);
$subjectLevels = array_map(fn($x) => $x[1], $lv['subjects']);
$goals = array_map(fn($x) => $x[0], $lv['goals']);
$goalLevels = array_map(fn($x) => $x[1], $lv['goals']);
$freq4 = ['always' => 'Toujours', 'often' => 'Souvent', 'sometimes' => 'Parfois', 'rarely' => 'Rarement'];

return [
    'code' => 'parent', 'title' => 'Questionnaire parent', 'role' => 'parent', 'version' => 2,
    'intro' => "Ce questionnaire aide à comprendre la situation de votre enfant. Toutes les questions sont facultatives ; vos réponses sont enregistrées automatiquement et vous pouvez reprendre plus tard.",
    'outro' => "Merci ! Vos réponses vont permettre de construire un accompagnement adapté.",
    'steps' => [
        ['title' => 'Votre enfant', 'icon' => 'user', 'questions' => [
            ['key' => 'p_first_name', 'type' => 'text', 'label' => 'Prénom', 'half' => true],
            ['key' => 'p_last_name', 'type' => 'text', 'label' => 'Nom', 'half' => true],
            ['key' => 'p_class', 'type' => 'select', 'label' => 'Classe', 'options' => CLASSES, 'placeholder' => 'Choisir la classe…', 'level_source' => true, 'half' => true],
            ['key' => 'p_class_school', 'type' => 'text', 'label' => 'Établissement (facultatif)', 'half' => true],
            ['key' => 'p_averages', 'type' => 'grades', 'label' => 'Moyennes approximatives, si vous les connaissez', 'help' => 'Une ligne par matière.', 'options' => $subjects, 'levels' => $subjectLevels],
            ['key' => 'p_main_goal', 'type' => 'multi_prio', 'label' => 'Objectifs du soutien scolaire', 'help' => 'Plusieurs choix possibles. 1er clic : sélectionner · 2e clic : définir comme priorité · 3e clic : retirer.', 'options' => $goals, 'levels' => $goalLevels],
            ['key' => 'p_main_goal_other', 'type' => 'text', 'label' => 'Précisez', 'show_if' => ['key' => 'p_main_goal', 'in' => ['other']]],
        ]],
        ['title' => 'Les difficultés', 'icon' => 'compass', 'questions' => [
            ['key' => 'p_since', 'type' => 'single', 'label' => 'Depuis quand rencontre-t-il des difficultés ?', 'options' => ['recent' => 'Depuis cette année', 'one_two' => 'Depuis 1 à 2 ans', 'long' => 'Depuis longtemps', 'none' => 'Pas vraiment de difficultés']],
            ['key' => 'p_results_trend', 'type' => 'single', 'label' => 'Ses résultats sont-ils en baisse ?', 'options' => ['down_a_lot' => 'Oui, nettement', 'down_bit' => 'Un peu', 'stable' => 'Ils sont stables', 'up' => 'Ils progressent']],
            ['key' => 'p_main_difficulties', 'type' => 'multi', 'label' => 'Quelles difficultés observez-vous ?', 'options' => [
                'comprehension' => 'Compréhension', 'memorisation' => 'Mémorisation', 'concentration' => 'Concentration', 'organisation' => 'Organisation',
                'motivation' => 'Motivation', 'confiance' => 'Confiance en soi', 'temps' => 'Gestion du temps', 'methodo' => 'Méthodologie', 'other' => 'Autre']],
            ['key' => 'p_main_difficulties_other', 'type' => 'text', 'label' => 'Autre difficulté : précisez', 'show_if' => ['key' => 'p_main_difficulties', 'in' => ['other']]],
            ['key' => 'p_difficulty_example', 'type' => 'textarea', 'label' => 'Pouvez-vous décrire une situation récente où il a été en difficulté ?', 'help' => 'Facultatif : un devoir, un contrôle, une matière…'],
            ['key' => 'p_understands', 'type' => 'scale', 'label' => 'Comprend-il ses cours ?', 'min' => 'Rarement', 'max' => 'Très bien'],
            ['key' => 'p_retains', 'type' => 'scale', 'label' => 'Retient-il les notions ?', 'min' => 'Rarement', 'max' => 'Très bien'],
            ['key' => 'p_applies', 'type' => 'scale', 'label' => 'Arrive-t-il à les appliquer dans les exercices ?', 'min' => 'Difficilement', 'max' => 'Facilement'],
        ]],
        ['title' => 'Ce que vous avez déjà essayé', 'icon' => 'lightbulb', 'questions' => [
            ['key' => 'p_tried', 'type' => 'multi', 'label' => "Qu'avez-vous déjà mis en place ?", 'options' => [
                'homework_help' => "Aide aux devoirs à la maison", 'private_lessons' => 'Cours particuliers', 'school_support' => "Soutien proposé par l'établissement",
                'apps' => 'Applications / sites de révision', 'holiday_course' => 'Stage pendant les vacances', 'teachers' => 'Rendez-vous avec les enseignants',
                'planning' => 'Planning / routine de travail', 'rewards' => 'Système de motivation', 'nothing' => 'Rien pour le moment', 'other' => 'Autre']],
            ['key' => 'p_tried_other', 'type' => 'text', 'label' => 'Autre : précisez', 'show_if' => ['key' => 'p_tried', 'in' => ['other']]],
            ['key' => 'p_tried_feedback', 'type' => 'textarea', 'label' => "Qu'est-ce qui a fonctionné, ou pas ?"],
            ['key' => 'p_teacher_feedback', 'type' => 'textarea', 'label' => 'Que disent les enseignants (bulletins, rendez-vous) ?'],
        ]],
        ['title' => 'Motivation et autonomie', 'icon' => 'rocket', 'questions' => [
            ['key' => 'p_wants_work', 'type' => 'scale', 'label' => 'A-t-il envie de travailler ?', 'min' => 'Pas du tout', 'max' => 'Beaucoup'],
            ['key' => 'p_discouraged', 'type' => 'scale', 'label' => 'Se décourage-t-il facilement ?', 'min' => 'Rarement', 'max' => 'Très souvent'],
            ['key' => 'p_bad_grade', 'type' => 'single', 'label' => 'Comment réagit-il face à une mauvaise note ?', 'options' => ['rebounds' => 'Il veut se rattraper', 'indifferent' => 'Il semble indifférent', 'discouraged' => 'Il se décourage', 'angry' => "Il s'énerve", 'hides' => "Il n'en parle pas"]],
            ['key' => 'p_needs_reminding', 'type' => 'single', 'label' => 'Faut-il régulièrement le relancer ?', 'options' => ['never' => 'Jamais', 'sometimes' => 'Parfois', 'often' => 'Souvent', 'always' => 'Tout le temps']],
            ['key' => 'p_starts_alone', 'type' => 'scale', 'label' => 'Commence-t-il ses devoirs seul ?', 'min' => 'Jamais', 'max' => 'Toujours'],
            ['key' => 'p_organizes_revisions', 'type' => 'scale', 'label' => 'Sait-il organiser ses révisions ?', 'min' => 'Pas du tout', 'max' => 'Très bien'],
            ['key' => 'p_needs_presence', 'type' => 'scale', 'label' => "A-t-il besoin d'une présence constante ?", 'min' => 'Pas du tout', 'max' => 'Tout le temps'],
            ['key' => 'p_asks_help', 'type' => 'scale', 'label' => "Demande-t-il de l'aide lorsqu'il ne comprend pas ?", 'min' => 'Jamais', 'max' => 'Toujours'],
            ['key' => 'p_motivators', 'type' => 'textarea', 'label' => "Qu'est-ce qui semble le motiver ?"],
        ]],
        ['title' => 'Habitudes de travail', 'icon' => 'clock', 'questions' => [
            ['key' => 'p_finishes_homework', 'type' => 'single', 'label' => 'Termine-t-il ses devoirs ?', 'options' => $freq4],
            ['key' => 'p_homework_time', 'type' => 'single', 'label' => 'Temps consacré aux devoirs (par jour)', 'options' => ['lt30' => 'Moins de 30 min', '30_60' => '30 min à 1 h', '60_90' => '1 h à 1 h 30', 'gt90' => 'Plus d\'1 h 30', 'varies' => 'Très variable']],
            ['key' => 'p_revision_freq', 'type' => 'single', 'label' => 'Fréquence des révisions', 'options' => ['regular' => 'Régulièrement', 'before_tests' => 'Seulement juste avant les contrôles', 'rarely' => 'Rarement', 'never' => 'Jamais']],
            ['key' => 'p_anticipates', 'type' => 'single', 'label' => 'Anticipe-t-il les contrôles ?', 'options' => ['yes' => 'Oui, plusieurs jours avant', 'day_before' => 'La veille', 'no' => 'Non, souvent au dernier moment', 'unknown' => 'Je ne sais pas']],
            ['key' => 'p_methods', 'type' => 'multi', 'label' => 'Méthodes utilisées', 'options' => ['reread' => 'Relire le cours', 'fiches' => 'Faire des fiches', 'exercises' => 'Faire des exercices', 'recite' => 'Réciter / se faire interroger', 'videos' => 'Vidéos / applis', 'none' => 'Pas de méthode particulière']],
            ['key' => 'p_computer', 'type' => 'single', 'label' => 'Est-il capable de travailler sur ordinateur ?', 'options' => ['yes' => 'Oui, il est à l\'aise', 'some' => "Avec un peu d'aide", 'no' => 'Difficilement']],
            ['key' => 'p_conditions', 'type' => 'single', 'label' => 'Conditions de travail à la maison', 'options' => ['calm' => 'Calmes, avec un endroit dédié', 'ok' => 'Correctes', 'noisy' => 'Souvent bruyantes ou partagées']],
            ['key' => 'p_distractions', 'type' => 'multi', 'label' => 'Sources de distraction', 'options' => ['phone' => 'Téléphone', 'social' => 'Réseaux sociaux', 'games' => 'Jeux vidéo', 'tv' => 'Télévision / séries', 'noise' => 'Bruit / fratrie', 'none' => 'Pas particulièrement', 'other' => 'Autre']],
            ['key' => 'p_distractions_other', 'type' => 'text', 'label' => 'Autre : précisez', 'show_if' => ['key' => 'p_distractions', 'in' => ['other']]],
        ]],
        ['title' => 'Vos attentes', 'icon' => 'target', 'questions' => [
            ['key' => 'p_priority', 'type' => 'single', 'label' => 'Votre priorité pour les premières semaines', 'options' => array_diff_key($goals, ['other' => 1]), 'levels' => $goalLevels],
            ['key' => 'p_hoped_results', 'type' => 'textarea', 'label' => 'Résultats espérés'],
            ['key' => 'p_expectations', 'type' => 'textarea', 'label' => 'Vos attentes vis-à-vis de la tutrice'],
            ['key' => 'p_availability', 'type' => 'textarea', 'label' => 'Disponibilités (jours, horaires)'],
            ['key' => 'p_comments', 'type' => 'textarea', 'label' => 'Commentaires complémentaires'],
        ]],
    ],
];
