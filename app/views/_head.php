<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#faf7f2">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Leevel">
<meta name="format-detection" content="telephone=no">
<link rel="manifest" href="<?= e(asset('manifest.json')) ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?= e(asset('icon-192.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(asset('apple-touch-icon.png')) ?>">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<meta name="robots" content="noindex,nofollow">
<title><?= e(isset($title) ? $title . ' · leevel.app' : 'leevel.app') ?></title>
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<script src="<?= e(asset('app.js')) ?>" defer></script>
</head>
