<?php
// Copier en config/config.php (hors du dossier public) et compléter.
// Ce fichier ne doit JAMAIS être placé dans public_html ni versionné avec de vraies valeurs.
return [
    'env'        => 'prod',              // 'prod' force HTTPS + cookies Secure ; 'dev' pour local
    'base_url'   => '',                  // ex. '' si l'appli est à la racine du domaine, '/suivi' sinon
    'db' => [
        'host'    => '127.0.0.1',        // Hostinger : 127.0.0.1 ou localhost
        'port'    => 3306,
        'name'    => 'u123456789_suivi',
        'user'    => 'u123456789_suivi',
        'pass'    => 'CHANGER_MOI',
    ],
    // Jeton d'installation : chaîne aléatoire longue (ex. `openssl rand -hex 24`).
    // Requis une seule fois par install.php. Vider après installation.
    'install_token' => '',
    'timezone'   => 'Europe/Paris',
    'session_name' => 'leevel_sid',
];
