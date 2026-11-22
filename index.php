<?php
declare(strict_types=1);
// Contrôleur frontal. APP_ROOT = dossier contenant app/ et config/ (hors web de préférence).
define('APP_ROOT', is_dir(__DIR__ . '/../app') ? dirname(__DIR__) : __DIR__);
require APP_ROOT . '/app/core.php';
require APP_ROOT . '/app/questionnaire.php';
require APP_ROOT . '/app/analysis.php';
require APP_ROOT . '/app/profile.php';
require APP_ROOT . '/app/reports.php';
require APP_ROOT . '/app/subjects.php';
require APP_ROOT . '/app/ai.php';
require APP_ROOT . '/app/schedule.php';
require APP_ROOT . '/app/account_delete.php';
require APP_ROOT . '/app/finance.php';
require APP_ROOT . '/app/welcome.php';
require APP_ROOT . '/app/routes.php';

boot();
dispatch((string)($_GET['r'] ?? 'home'), $_SERVER['REQUEST_METHOD'] ?? 'GET');
