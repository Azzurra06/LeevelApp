<?php
// Questionnaire élève - ton simple et bienveillant. 'sensitive' => bouton « Je préfère ne pas répondre ».
$lv = require __DIR__ . '/_levels.php';
$subjects = array_map(fn($x) => $x[0], $lv['subjects']);
$subjectLevels = array_map(fn($x) => $x[1], $lv['subjects']);
$yn = ['yes' => 'Oui', 'sometimes' => 'Parfois / ça dépend', 'no' => 'Pas trop'];
$c = fn(string $v) => ['key' => 's_improve_one', 'in' => [$v]];
$ages = []; for ($i = 6; $i <= 19; $i++) $ages[(string)$i] = "$i ans";
$all = ['primaire', 'college', 'lycee'];

return [
    'code' => 'student', 'title' => 'Ton questionnaire', 'role' => 'student', 'version' => 2,
    'intro' => "Il n'y a pas de bonne ou de mauvaise réponse. Ça sert juste à mieux te connaître pour que les séances te soient vraiment utiles.",
    'outro' => "Merci, c'est top ! Grâce à toi, je vais pouvoir préparer des séances qui te ressemblent. On commence bientôt 🚀",
    'steps' => [
        ['title' => 'Qui es-tu ?', 'icon' => 'user', 'intro' => 'Ces informations seront reprises dans ta page « Mon compte ».', 'questions' => [
            ['key' => 's_first_name', 'type' => 'text', 'label' => 'Ton prénom', 'half' => true],
            ['key' => 's_last_name', 'type' => 'text', 'label' => 'Ton nom', 'half' => true],
            ['key' => 's_age', 'type' => 'select', 'label' => 'Ton âge', 'options' => $ages, 'placeholder' => 'Choisir…', 'half' => true],
            ['key' => 's_class', 'type' => 'select', 'label' => 'Ta classe', 'options' => CLASSES, 'placeholder' => 'Choisir ta classe…', 'level_source' => true, 'half' => true],
            ['key' => 's_school', 'type' => 'text', 'label' => 'Ton établissement (facultatif)'],
            ['key' => 's_interests', 'type' => 'multi', 'label' => "Tes centres d'intérêt", 'help' => 'Plusieurs choix possibles.', 'options' => STUDENT_INTERESTS],
        ]],
        ['title' => 'Faisons connaissance', 'icon' => 'smile', 'questions' => [
            ['key' => 's_fav_subjects', 'type' => 'multi', 'label' => 'Quelles matières tu préfères ?', 'options' => $subjects, 'levels' => $subjectLevels],
            ['key' => 's_least_subjects', 'type' => 'multi', 'label' => 'Et celles que tu aimes le moins ?', 'options' => $subjects, 'levels' => $subjectLevels],
            ['key' => 's_hard_subjects', 'type' => 'multi', 'label' => 'Et celles qui te paraissent difficiles ?', 'options' => $subjects, 'levels' => $subjectLevels],
            ['key' => 's_hobbies', 'type' => 'textarea', 'label' => "Qu'est-ce que tu aimes faire en dehors des cours ?"],
            ['key' => 's_after_3e', 'type' => 'single', 'label' => "Tu as une idée de ce que tu aimerais faire plus tard ?", 'options' => ['lycee_gen' => 'Lycée général', 'lycee_techno' => 'Lycée technologique', 'lycee_pro' => 'Lycée pro / apprentissage', 'etudes' => 'Des études après le bac', 'metier' => 'Un métier précis', 'not_sure' => 'Je ne sais pas encore', 'other' => 'Autre idée'],
                'levels' => ['lycee_gen' => ['primaire', 'college'], 'lycee_techno' => ['primaire', 'college'], 'lycee_pro' => ['primaire', 'college'], 'etudes' => ['lycee'], 'metier' => $all, 'not_sure' => $all, 'other' => $all]],
            ['key' => 's_after_3e_detail', 'type' => 'text', 'label' => 'Tu veux en dire plus ?', 'hint' => 'Filière ? Métier ?', 'show_if' => ['key' => 's_after_3e', 'in' => ['lycee_gen', 'lycee_techno', 'lycee_pro', 'etudes', 'metier', 'other']]],
        ]],
        ['title' => 'Ton ressenti au collège', 'icon' => 'heart', 'intro' => 'Place le curseur là où tu te sens.', 'questions' => [
            ['key' => 's_feel_school', 'type' => 'scale', 'label' => 'Globalement, comment tu te sens au collège ?', 'min' => 'Pas bien', 'max' => 'Très bien', 'sensitive' => true, 'faces' => true],
            ['key' => 's_understands_teachers', 'type' => 'scale', 'label' => 'Tu comprends les explications des profs ?', 'min' => 'Rarement', 'max' => 'Presque toujours'],
            ['key' => 's_bored', 'type' => 'scale', 'label' => "Tu t'ennuies en cours ?", 'min' => 'Jamais', 'max' => 'Souvent'],
            ['key' => 's_finds_hard', 'type' => 'scale', 'label' => 'Les cours te paraissent difficiles ?', 'min' => 'Pas du tout', 'max' => 'Très'],
            ['key' => 's_stress', 'type' => 'scale', 'label' => 'Avant un contrôle, tu te sens stressé ?', 'min' => 'Pas du tout', 'max' => 'Beaucoup', 'sensitive' => true],
        ]],
        ['title' => 'Ce que tu aimerais changer', 'icon' => 'lightbulb', 'questions' => [
            ['key' => 's_improve_one', 'type' => 'multi', 'label' => 'Si tu pouvais améliorer des choses au collège, ce serait quoi ?', 'help' => 'Plusieurs choix possibles.', 'options' => [
                'grades' => 'Avoir de meilleures notes', 'understand' => 'Mieux comprendre les cours', 'motivation' => "Retrouver l'envie de travailler",
                'homework' => 'Avoir moins de mal à gérer mes devoirs', 'tests' => 'Être plus à l\'aise pendant les contrôles', 'other' => 'Autre chose']],
            ['key' => 's_improve_other', 'type' => 'text', 'label' => 'Raconte-moi', 'show_if' => $c('other')],
            // Meilleures notes
            ['key' => 's_g_revises', 'type' => 'single', 'label' => 'Tu révises avant les contrôles ?', 'options' => $yn, 'show_if' => $c('grades')],
            ['key' => 's_g_why_points', 'type' => 'single', 'label' => 'Tu comprends pourquoi tu perds des points ?', 'options' => $yn, 'show_if' => $c('grades')],
            ['key' => 's_g_rewarded', 'type' => 'single', 'label' => "Tu as l'impression que tes efforts sont récompensés ?", 'options' => $yn, 'show_if' => $c('grades')],
            // Mieux comprendre
            ['key' => 's_u_lost', 'type' => 'single', 'label' => 'À quel moment tu te sens perdu ?', 'options' => ['start' => 'Dès le début du cours', 'exercises' => 'Au moment des exercices', 'alone' => 'Quand je travaille seul à la maison', 'tests' => 'Pendant les contrôles'], 'show_if' => $c('understand')],
            ['key' => 's_u_dare_ask', 'type' => 'single', 'label' => 'Tu oses poser des questions ?', 'options' => $yn, 'show_if' => $c('understand')],
            ['key' => 's_u_prefers', 'type' => 'multi', 'label' => 'Tu préfères…', 'options' => ['examples' => 'Des exemples', 'diagrams' => 'Des schémas', 'oral' => 'Des explications à l\'oral', 'videos' => 'Des vidéos'], 'show_if' => $c('understand')],
            // Motivation
            ['key' => 's_m_bored_most', 'type' => 'textarea', 'label' => "Qu'est-ce qui t'ennuie le plus ?", 'show_if' => $c('motivation')],
            ['key' => 's_m_likes', 'type' => 'textarea', 'label' => 'Quelles activités te plaisent ?', 'show_if' => $c('motivation')],
            ['key' => 's_m_prefers', 'type' => 'multi', 'label' => 'Tu préfères…', 'options' => ['challenges' => 'Les défis', 'games' => 'Les jeux', 'concrete' => 'Les exercices concrets'], 'show_if' => $c('motivation')],
            // Devoirs
            ['key' => 's_h_start', 'type' => 'single', 'label' => "Tu as du mal à t'y mettre ?", 'options' => $yn, 'show_if' => $c('homework')],
            ['key' => 's_h_where', 'type' => 'single', 'label' => 'Tu sais par quoi commencer ?', 'options' => $yn, 'show_if' => $c('homework')],
            ['key' => 's_h_time', 'type' => 'single', 'label' => "Tu as l'impression de manquer de temps ?", 'options' => $yn, 'show_if' => $c('homework')],
            // Contrôles
            ['key' => 's_t_blank', 'type' => 'single', 'label' => "Ça t'arrive d'avoir un trou pendant un contrôle ?", 'options' => $yn, 'show_if' => $c('tests')],
            ['key' => 's_t_time', 'type' => 'single', 'label' => 'Tu termines tes contrôles à temps ?', 'options' => $yn, 'show_if' => $c('tests')],
            ['key' => 's_t_prepare', 'type' => 'single', 'label' => 'Tu sais comment préparer un contrôle ?', 'options' => $yn, 'show_if' => $c('tests')],
        ]],
        ['title' => 'Comment tu travailles', 'icon' => 'pencil', 'questions' => [
            ['key' => 's_memory', 'type' => 'multi', 'label' => 'Pour retenir quelque chose, ce qui marche le mieux pour toi ?', 'help' => 'Plusieurs choix possibles.', 'options' => MEMORY_TYPES],
            ['key' => 's_how_revise', 'type' => 'textarea', 'label' => 'Comment tu révises un contrôle, en général ?'],
            ['key' => 's_reread', 'type' => 'single', 'label' => 'Tu relis simplement ton cours ?', 'options' => $yn],
            ['key' => 's_exercises', 'type' => 'single', 'label' => "Tu fais des exercices pour t'entraîner ?", 'options' => $yn],
            ['key' => 's_flashcards', 'type' => 'single', 'label' => 'Tu utilises des fiches ?', 'options' => $yn],
            ['key' => 's_hard_exercise', 'type' => 'single', 'label' => 'Quand un exercice est difficile, tu fais quoi ?', 'options' => ['keep_trying' => 'Je cherche encore', 'ask' => "Je demande de l'aide", 'answer' => 'Je regarde la correction', 'skip' => 'Je passe à la suite', 'give_up' => "J'abandonne"]],
            ['key' => 's_alone_or_not', 'type' => 'single', 'label' => 'Tu préfères travailler…', 'options' => ['alone' => 'Seul', 'with' => 'Accompagné', 'depends' => 'Ça dépend']],
        ]],
        ['title' => 'Ton autonomie', 'icon' => 'compass', 'questions' => [
            ['key' => 's_20min', 'type' => 'single', 'label' => 'Tu peux travailler 20 minutes sans aide ?', 'options' => $yn],
            ['key' => 's_computer', 'type' => 'single', 'label' => 'Tu sais utiliser un ordinateur pour travailler ?', 'options' => $yn],
            ['key' => 's_follow_instructions', 'type' => 'single', 'label' => "Tu arrives à suivre une consigne tout seul ?", 'options' => $yn],
            ['key' => 's_asks_help', 'type' => 'single', 'label' => "Tu demandes de l'aide facilement ?", 'options' => $yn],
            ['key' => 's_visio_ok', 'type' => 'single', 'label' => 'Tu te sentirais à l\'aise en visio ?', 'options' => $yn],
        ]],
        ['title' => 'Tes objectifs', 'icon' => 'flag', 'questions' => [
            ['key' => 's_succeed', 'type' => 'textarea', 'label' => "Qu'aimerais-tu réussir cette année ?"],
            ['key' => 's_expect', 'type' => 'textarea', 'label' => "Qu'attends-tu des cours particuliers ?"],
            ['key' => 's_interesting', 'type' => 'textarea', 'label' => 'Qu\'est-ce qui pourrait rendre les séances plus intéressantes ?'],
            ['key' => 's_first_goal', 'type' => 'text', 'label' => 'Un premier petit objectif que tu aimerais atteindre ?'],
        ]],
    ],
];
