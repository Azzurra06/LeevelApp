<?php $me = current_user(); $r = $_GET['r'] ?? 'home'; $role = $me['role'] ?? ''; $sidQ = isset($s['id']) ? ['sid' => $s['id']] : [];
$nav = match ($role) {
    'tutor' => [['t', 'house', 'Tableau de bord', 'Accueil'], ['t_rdv', 'clipboard-list', 'Premier rendez-vous', '1er RDV'], ['t_student', 'user', 'Fiche élève', 'Élève'], ['t_messages', 'message-circle', 'Échanges', 'Échanges']],
    'parent' => [['p', 'house', 'Suivi', 'Suivi'], ['p_planning', 'calendar', 'Planning', 'Planning'], ['p_messages', 'message-circle', 'Échanges', 'Échanges'], ['q', 'clipboard-list', 'Mon questionnaire', 'Questionnaire']],
    'student' => [['s', 'house', 'Mon espace', 'Accueil'], ['s_subjects', 'book-open', 'Mes matières', 'Matières'], ['s_tools', 'lightbulb', 'Mes outils', 'Outils'], ['s_messages', 'message-circle', 'Messages', 'Messages'], ['q', 'clipboard-list', 'Mon questionnaire', 'Questionnaire']],
    default => [],
};
$unread = $role ? unread_count($me) : 0;
// Tutrice : choix de l'élève suivi dans la barre du haut (toutes les pages de suivi s'y rapportent)
$switchList = $role === 'tutor' ? students_of($me) : [];
$curSid = (int)($s['id'] ?? $_SESSION['current_sid'] ?? ($switchList[0]['id'] ?? 0));
$switchRoute = in_array($r, ['t', 't_rdv', 't_student', 't_messages', 't_synthesis'], true) ? $r : 't'; ?>
<?php require __DIR__ . '/_head.php' ?>
<body class="<?= e($role) ?> page-<?= e(preg_replace('/[^a-z_]/', '', $r)) ?><?= in_array($r, ['s_messages', 'p_messages', 't_messages'], true) ? ' page-chat' : '' ?>">
<header class="top no-print<?= $switchList ? ' has-switch' : '' ?>"><div class="top-in">
  <a class="brand" href="<?= e(url('home')) ?>"><span class="logo">leevel<span>.app</span></span></a>
  <?php if ($switchList): ?>
  <form method="get" action="<?= e(url()) ?>" class="st-switch">
    <input type="hidden" name="r" value="<?= e($switchRoute) ?>">
    <?php if ($switchRoute === 't_student' && !empty($_GET['tab'])): ?><input type="hidden" name="tab" value="<?= e((string)$_GET['tab']) ?>"><?php endif ?>
    <?php if ($switchRoute === 't_messages' && ($_GET['ch'] ?? '') === 'student'): ?><input type="hidden" name="ch" value="student"><?php endif ?>
    <?= icon('user', 'st-ic') ?>
    <select name="sid" data-autosubmit aria-label="Élève suivi">
      <?php foreach ($switchList as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)$x['id'] === $curSid ? 'selected' : '' ?>><?= e($x['first_name']) ?><?= $x['is_demo'] && !str_contains(mb_strtolower($x['first_name']), 'démo') ? ' (démo)' : '' ?> · <?= e($x['grade']) ?></option><?php endforeach ?>
    </select>
    <noscript><button class="btn ghost sm">OK</button></noscript>
  </form>
  <?php endif ?>
  <nav class="nav nav-main" aria-label="Navigation principale">
    <?php foreach ($nav as [$route, $ic, $label, $short]): $on = $r === $route || ($route === 't_student' && str_starts_with($r, 't_student')); ?>
      <a href="<?= e(url($route, $role === 'tutor' && $route !== 't_users' ? $sidQ : [])) ?>" class="<?= $on ? 'on' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><span class="nav-ic"><?= icon($ic) ?><?php if ($unread && in_array($route, ['t_messages', 'p_messages', 's_messages'], true)): ?><span class="nav-badge"><?= $unread ?></span><?php endif ?></span><span class="lbl"><?= e($label) ?></span><span class="lbl-s"><?= e($short) ?></span></a>
    <?php endforeach ?>
  </nav>
  <div class="nav nav-side">
    <details class="acct" data-acct>
      <summary class="acct-btn<?= in_array($r, ['account', 'help', 't_users'], true) ? ' on' : '' ?>" aria-label="Menu de mon compte">
        <span class="acct-av"><?= avatar_html($me, 'xs') ?></span><span class="acct-ic"><?= icon('user') ?></span><span class="acct-lbl">Mon compte</span><?= icon('chevron-right', 'acct-chev') ?>
      </summary>
      <div class="acct-menu" role="menu">
        <div class="acct-head"><?= avatar_html($me, 'md') ?><div><strong><?= e($me['display_name']) ?></strong><small><?= e(ROLE_LABELS[$role] ?? '') ?> · <?= e($me['username']) ?></small></div></div>
        <a role="menuitem" href="<?= e(url('account')) ?>" class="<?= $r === 'account' ? 'on' : '' ?>"><?= icon('settings') ?> Mon compte</a>
        <?php if ($role === 'tutor'): ?><a role="menuitem" href="<?= e(url('t_users')) ?>" class="<?= $r === 't_users' ? 'on' : '' ?>"><?= icon('users') ?> Familles et dossiers</a><?php endif ?>
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
