<?php $me = current_user(); $r = $_GET['r'] ?? 'home'; $role = $me['role'] ?? ''; $sidQ = isset($s['id']) ? ['sid' => $s['id']] : [];
$nav = match ($role) {
    'tutor' => [['t', 'house', 'Tableau de bord', 'Accueil'], ['t_rdv', 'clipboard-list', 'Premier rendez-vous', '1er RDV'], ['t_student', 'user', 'Fiche élève', 'Élève'], ['t_messages', 'message-circle', 'Échanges', 'Échanges'], ['t_users', 'users', 'Familles', 'Familles']],
    'parent' => [['p', 'house', 'Suivi', 'Suivi'], ['p_planning', 'calendar', 'Planning', 'Planning'], ['p_messages', 'message-circle', 'Échanges', 'Échanges'], ['q', 'clipboard-list', 'Mon questionnaire', 'Questionnaire']],
    'student' => [['s', 'house', 'Mon espace', 'Accueil'], ['s_subjects', 'book-open', 'Mes matières', 'Matières'], ['s_tools', 'lightbulb', 'Mes outils', 'Outils'], ['s_messages', 'message-circle', 'Messages', 'Messages'], ['q', 'clipboard-list', 'Mon questionnaire', 'Questionnaire']],
    default => [],
};
$unread = $role ? unread_count($me) : 0; ?>
<?php require __DIR__ . '/_head.php' ?>
<body class="<?= e($role) ?> page-<?= e(preg_replace('/[^a-z_]/', '', $r)) ?><?= in_array($r, ['s_messages', 'p_messages', 't_messages'], true) ? ' page-chat' : '' ?>">
<header class="top no-print"><div class="top-in">
  <a class="brand" href="<?= e(url('home')) ?>"><span class="logo">leevel<span>.app</span></span></a>
  <nav class="nav nav-main" aria-label="Navigation principale">
    <?php foreach ($nav as [$route, $ic, $label, $short]): $on = $r === $route || ($route === 't_student' && str_starts_with($r, 't_student')); ?>
      <a href="<?= e(url($route, $role === 'tutor' && $route !== 't_users' ? $sidQ : [])) ?>" class="<?= $on ? 'on' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><span class="nav-ic"><?= icon($ic) ?><?php if ($unread && in_array($route, ['t_messages', 'p_messages', 's_messages'], true)): ?><span class="nav-badge"><?= $unread ?></span><?php endif ?></span><span class="lbl"><?= e($label) ?></span><span class="lbl-s"><?= e($short) ?></span></a>
    <?php endforeach ?>
  </nav>
  <div class="nav nav-side">
    <details class="acct" data-acct>
      <summary class="acct-btn<?= in_array($r, ['account', 'help'], true) ? ' on' : '' ?>" aria-label="Menu de mon compte">
        <span class="acct-av"><?= avatar_html($me, 'xs') ?></span><span class="acct-ic"><?= icon('user') ?></span><span class="acct-lbl">Mon compte</span><?= icon('chevron-right', 'acct-chev') ?>
      </summary>
      <div class="acct-menu" role="menu">
        <div class="acct-head"><?= avatar_html($me, 'md') ?><div><strong><?= e($me['display_name']) ?></strong><small><?= e(ROLE_LABELS[$role] ?? '') ?> · <?= e($me['username']) ?></small></div></div>
        <a role="menuitem" href="<?= e(url('account')) ?>" class="<?= $r === 'account' ? 'on' : '' ?>"><?= icon('settings') ?> Mon compte</a>
        <a role="menuitem" href="<?= e(url('help')) ?>" class="<?= $r === 'help' ? 'on' : '' ?>"><?= icon('circle-help') ?> Centre d'aide</a>
        <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button role="menuitem" class="acct-out"><?= icon('log-out') ?> Se déconnecter</button></form>
      </div>
    </details>
  </div>
</div></header>
<main class="wrap">
<?php require __DIR__ . '/_flash.php' ?>
<?= $content ?>
</main>
<footer class="footer no-print"><a href="<?= e(url('privacy')) ?>">Données personnelles</a> · Aucune donnée transmise à des tiers</footer>
</body></html>
