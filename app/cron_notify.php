<?php
declare(strict_types=1);
// Tâche planifiée (cron, toutes les 10 min) : php app/cron_notify.php <domaine> [--delay=30] [--cooldown=120]
// Exécutable uniquement en ligne de commande.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/core.php';
require APP_ROOT . '/app/notify.php';

$host = $argv[1] ?? '';
if (!preg_match('/^[a-z0-9.-]+(:\d+)?$/i', $host)) { fwrite(STDERR, "Usage : php app/cron_notify.php <domaine>\n"); exit(2); }
$opt = [];
foreach (array_slice($argv, 2) as $a) if (preg_match('/^--(delay|cooldown)=(\d+)$/', $a, $m)) $opt[$m[1]] = $m[2];
date_default_timezone_set(config('timezone') ?: 'Europe/Paris');
db()->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
$delay = isset($opt['delay']) ? max(0, (int)$opt['delay']) : NOTIFY_DELAY_MIN;
$cooldown = isset($opt['cooldown']) ? max(0, (int)$opt['cooldown']) : NOTIFY_COOLDOWN_MIN;
$stats = run_message_notifications($host, $delay, $cooldown);
echo date('c') . ' ' . json_encode($stats) . "\n";
