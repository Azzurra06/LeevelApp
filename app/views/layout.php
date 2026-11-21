<?php $me = current_user(); $r = $_GET['r'] ?? 'home'; $role = $me['role'] ?? ''; $sidQ = isset($s['id']) ? ['sid' => $s['id']] : [];
$nav = match ($role) {
    'tutor' => [['t', 'house', 'Tableau de bord', 'Accueil'], ['t_rdv', 'clipboard-list', 'Premier rendez-vous', '1er RDV'], ['t_student', 'user', 'Fiche élève', 'Élève'], ['t_messages', 'message-circle', 'Échanges', 'Échanges'], ['t_users', 'users', 'Comptes', 'Comptes']],
    'parent' => [['p', 'house', 'Suivi', 'Suivi'], ['p_planning', 'calendar', 'Planning', 'Planning'], ['p_messages', 'message-circle', 'Échanges', 'Échanges'], ['q', 'clipboard-list', 'Mon questionnaire', 'Questionnaire']],
    'student' => [['s', 'house', 'Mon espace', 'Accueil'], ['s_subjects', 'book-open', 'Mes matières', 'Matières'], ['s_tools', 'lightbulb', 'Mes outils', 'Outils'], ['q', 'clipboard-list', 'Mon questionnaire', 'Questionnaire']],
    default => [],
};
$unread = in_array($role, ['tutor', 'parent'], true) ? unread_count($me) : 0; ?>
<?php require __DIR__ . '/_head.php' ?>
<body class="<?= e($role) ?>">
<header class="top no-print"><div class="top-in">
  <a class="brand" href="<?= e(url('home')) ?>"><span class="logo">leevel<span>.app</span></span></a>
  <nav class="nav nav-main" aria-label="Navigation principale">
    <?php foreach ($nav as [$route, $ic, $label, $short]): $on = $r === $route || ($route === 't_student' && str_starts_with($r, 't_student')); ?>
      <a href="<?= e(url($route, $role === 'tutor' && $route !== 't_users' ? $sidQ : [])) ?>" class="<?= $on ? 'on' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><span class="nav-ic"><?= icon($ic) ?><?php if ($unread && in_array($route, ['t_messages', 'p_messages'], true)): ?><span class="nav-badge"><?= $unread ?></span><?php endif ?></span><span class="lbl"><?= e($label) ?></span><span class="lbl-s"><?= e($short) ?></span></a>
    <?php endforeach ?>
  </nav>
  <div class="nav nav-side">
    <a href="<?= e(url('account')) ?>" class="<?= $r === 'account' ? 'on' : '' ?> nav-me" title="Mon compte"><?= avatar_html($me, 'xs') ?><span class="hide-mobile">Mon compte</span></a>
    <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button title="Se déconnecter" aria-label="Se déconnecter"><?= icon('log-out') ?><span class="hide-mobile">Quitter</span></button></form>
  </div>
</div></header>
<main class="wrap">
<?php require __DIR__ . '/_flash.php' ?>
<?= $content ?>
</main>
<footer class="footer no-print"><a href="<?= e(url('privacy')) ?>">Données personnelles</a> · Aucune donnée transmise à des tiers</footer>
</body></html>
