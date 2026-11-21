<?php $me = current_user(); $r = $_GET['r'] ?? 'home'; $role = $me['role'] ?? ''; $sidQ = isset($s['id']) ? ['sid' => $s['id']] : [];
$nav = match ($role) {
    'tutor' => [['t', 'house', 'Tableau de bord'], ['t_rdv', 'clipboard-list', 'Premier rendez-vous'], ['t_student', 'user', 'Fiche élève'], ['t_messages', 'message-circle', 'Échanges'], ['t_users', 'users', 'Comptes']],
    'parent' => [['p', 'house', 'Suivi'], ['p_planning', 'calendar', 'Planning'], ['p_messages', 'message-circle', 'Échanges'], ['q', 'clipboard-list', 'Mon questionnaire']],
    'student' => [['s', 'house', 'Mon espace'], ['s_tools', 'lightbulb', 'Mes outils'], ['q', 'clipboard-list', 'Mon questionnaire']],
    default => [],
};
$unread = in_array($role, ['tutor', 'parent'], true) ? unread_count($me) : 0; ?>
<?php require __DIR__ . '/_head.php' ?>
<body class="<?= e($role) ?>">
<header class="top no-print"><div class="top-in">
  <a class="brand" href="<?= e(url('home')) ?>"><span class="logo">leevel<span>.app</span></span></a>
  <nav class="nav" aria-label="Navigation principale">
    <?php foreach ($nav as [$route, $ic, $label]): ?>
      <a href="<?= e(url($route, $role === 'tutor' && $route !== 't_users' ? $sidQ : [])) ?>" class="<?= $r === $route ? 'on' : '' ?>"><?= icon($ic) ?><span class="hide-mobile"><?= e($label) ?></span><?php if ($unread && in_array($route, ['t_messages', 'p_messages'], true)): ?><span class="nav-badge"><?= $unread ?></span><?php endif ?></a>
    <?php endforeach ?>
    <a href="<?= e(url('account')) ?>" class="<?= $r === 'account' ? 'on' : '' ?> nav-me" title="Mon compte"><?= avatar_html($me, 'xs') ?><span class="hide-mobile">Mon compte</span></a>
    <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button title="Se déconnecter"><?= icon('log-out') ?><span class="hide-mobile">Quitter</span></button></form>
  </nav>
</div></header>
<main class="wrap">
<?php require __DIR__ . '/_flash.php' ?>
<?= $content ?>
</main>
<footer class="footer no-print"><a href="<?= e(url('privacy')) ?>">Données personnelles</a> · Aucune donnée transmise à des tiers</footer>
</body></html>
