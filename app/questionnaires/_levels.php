<?php
// Niveaux scolaires : listes partagées (questionnaires, création de dossier, fiche élève).
// 'levels' indique pour quels cycles une option est proposée : primaire, college, lycee.
return [
    'subjects' => [
        'francais' => ['Français', ['primaire', 'college', 'lycee']],
        'maths' => ['Mathématiques', ['primaire', 'college', 'lycee']],
        'anglais' => ['Anglais', ['primaire', 'college', 'lycee']],
        'histgeo' => ['Histoire-géographie / EMC', ['primaire', 'college', 'lycee']],
        'sciences' => ['Sciences / Questionner le monde', ['primaire']],
        'lv2' => ['Langue vivante 2', ['college', 'lycee']],
        'svt' => ['SVT', ['college', 'lycee']],
        'physique' => ['Physique-chimie', ['college', 'lycee']],
        'techno' => ['Technologie', ['college']],
        'ses' => ['SES', ['lycee']],
        'philo' => ['Philosophie', ['lycee']],
        'nsi' => ['NSI / Numérique', ['lycee']],
        'spe' => ['Autre spécialité', ['lycee']],
        'arts' => ['Arts / musique', ['primaire', 'college', 'lycee']],
        'eps' => ['EPS / sport', ['primaire', 'college', 'lycee']],
    ],
    'goals' => [
        'notes' => ['Améliorer ses résultats', ['primaire', 'college', 'lycee']],
        'fondamentaux' => ['Consolider lecture, écriture, calcul', ['primaire']],
        'brevet' => ['Préparer le brevet', ['college']],
        'bac' => ['Préparer le bac', ['lycee']],
        'orientation' => ["Préparer l'orientation", ['college', 'lycee']],
        'methode' => ['Acquérir une méthode de travail', ['primaire', 'college', 'lycee']],
        'comprehension' => ['Combler des lacunes', ['primaire', 'college', 'lycee']],
        'confiance' => ['Reprendre confiance', ['primaire', 'college', 'lycee']],
        'autonomie' => ['Devenir plus autonome', ['primaire', 'college', 'lycee']],
        'motivation' => ['Retrouver la motivation', ['primaire', 'college', 'lycee']],
        'other' => ['Autre', ['primaire', 'college', 'lycee']],
    ],
];
