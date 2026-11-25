<?php $me = current_user(); $r = $_GET['r'] ?? 'home'; $role = $me['role'] ?? ''; $sidQ = isset($s['id']) ? ['sid' => $s['id']] : [];
$nav = match ($role) {
    'tutor' => [['t', 'house', 'Accueil', 'Accueil'], ['t_student', 'user', 'Élève', 'Élève'], ['t_student', 'target', 'Objectifs', 'Objectifs', ['tab' => 'objectifs'], 'desk'], ['t_student', 'book-open', 'Matières', 'Matières', ['tab' => 'matieres'], 'desk'], ['t_student', 'calendar', 'Séances', 'Séances', ['tab' => 'seances']], ['t_student', 'list-checks', 'Agenda', 'Agenda', ['tab' => 'agenda']], ['t_messages', 'message-circle', 'Messagerie', 'Messagerie']],
    'parent' => [['p', 'house', 'Suivi', 'Suivi'], ['p_child', 'smile', 'Mon enfant', 'Mon enfant'], ['p_planning', 'calendar', 'Agenda', 'Agenda'], ['p_finance', 'file-text', 'Finances', 'Finances'], ['p_messages', 'message-circle', 'Messagerie', 'Messagerie']],
    'student' => [['s', 'house', 'Mon espace', 'Accueil'], ['s_planning', 'calendar', 'Agenda', 'Agenda'], ['s_subjects', 'book-open', 'Mes matières', 'Matières'], ['s_tools', 'lightbulb', 'Mes outils', 'Outils'], ['s_messages', 'message-circle', 'Messagerie', 'Messagerie']],
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
<?php ob_start() ?>
  <nav class="nav nav-main" aria-label="Navigation principale">
    <?php $curTab = (string)($_GET['tab'] ?? '');
    foreach ($nav as $item): [$route, $ic, $label, $short] = $item; $params = $item[4] ?? [];
      $on = $route === 't_student'
          ? (($r === 't_student' || $r === 't_rdv') && (($params['tab'] ?? '') === (in_array($curTab, ['seances', 'matieres', 'objectifs', 'agenda'], true) && $r === 't_student' ? $curTab : '')))
          : $r === $route; ?>
      <a data-tour="<?= e($route . (isset($params['tab']) ? '-' . $params['tab'] : '')) ?>"<?= ($item[5] ?? '') === 'desk' ? ' data-desk-only' : '' ?> href="<?= e(url($route, ($role === 'tutor' ? $sidQ : []) + $params)) ?>" class="<?= $on ? 'on' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><span class="nav-ic"><?= icon($ic) ?><?php if ($unread && in_array($route, ['t_messages', 'p_messages', 's_messages'], true)): ?><span class="nav-badge"><?= $unread ?></span><?php endif ?></span><span class="lbl"><?= e($label) ?></span><span class="lbl-s"><?= e($short) ?></span></a>
    <?php endforeach ?>
  </nav>
<?php $navHtml = ob_get_clean(); echo $navHtml; ?>
  <div class="nav nav-side">
    <details class="acct" data-acct>
      <summary data-tour="account" class="acct-btn<?= in_array($r, ['account', 'help', 't_users', 'q', 'q_recap', 't_finance'], true) ? ' on' : '' ?>" aria-label="Menu de mon compte">
        <span class="acct-av"><?= avatar_html($me, 'xs') ?></span><span class="acct-ic"><?= icon('user') ?></span><span class="acct-lbl">Mon compte</span><?= icon('chevron-right', 'acct-chev') ?>
      </summary>
      <div class="acct-menu" role="menu">
        <div class="acct-head"><?= avatar_html($me, 'md') ?><div><strong><?= e($me['display_name']) ?></strong><small><?= e(ROLE_LABELS[$role] ?? '') ?> · <?= e($me['username']) ?></small></div></div>
        <a role="menuitem" href="<?= e(url('account')) ?>" class="<?= $r === 'account' ? 'on' : '' ?>"><?= icon('settings') ?> Mon compte</a>
        <?php if ($role === 'tutor'): ?><a role="menuitem" href="<?= e(url('t_users')) ?>" class="<?= $r === 't_users' ? 'on' : '' ?>"><?= icon('users') ?> Familles et dossiers</a><a role="menuitem" href="<?= e(url('t_finance')) ?>" class="<?= $r === 't_finance' ? 'on' : '' ?>"><?= icon('file-text') ?> Finances</a><?php else: ?><a role="menuitem" href="<?= e(url('q')) ?>" class="<?= in_array($r, ['q', 'q_recap'], true) ? 'on' : '' ?>"><?= icon('clipboard-list') ?> Mon questionnaire</a><?php endif ?>
        <?php if ($role === 'student'): ?><a role="menuitem" href="<?= e(url('s_goals')) ?>" class="<?= $r === 's_goals' ? 'on' : '' ?>"><?= icon('target') ?> Mes objectifs</a><?php endif ?>
        <a role="menuitem" href="<?= e(url('help')) ?>" class="<?= $r === 'help' ? 'on' : '' ?>"><?= icon('circle-help') ?> Centre d'aide</a>
        <?php $others = linked_accounts(); ?>
        <div class="acct-sep"><?= $others ? 'Changer de compte' : 'Autres comptes' ?></div>
        <?php foreach ($others as $o): ?>
          <form method="post" action="<?= e(url('switch_account')) ?>"><?= csrf_field() ?><input type="hidden" name="uid" value="<?= (int)$o['id'] ?>">
            <button role="menuitem" class="acct-switch"><?= avatar_html($o, 'xs') ?><span><strong><?= e($o['display_name']) ?></strong><small><?= e(ROLE_LABELS[$o['role']] ?? '') ?> · <?= e($o['username']) ?></small></span></button></form>
        <?php endforeach ?>
        <a role="menuitem" href="<?= e(url('login', ['add' => 1])) ?>"><?= icon('plus') ?> Ajouter un compte</a>
        <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button role="menuitem" class="acct-out"><?= icon('log-out') ?> Se déconnecter</button></form>
        <?php if ($others): ?><form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><input type="hidden" name="all" value="1"><button role="menuitem" class="acct-out"><?= icon('log-out') ?> Déconnecter tous les comptes</button></form><?php endif ?>
      </div>
    </details>
  </div>
</div></header>
<?php // Sur mobile, la barre d'onglets est hors de l'en-tête « collant » : sur iPhone, un élément fixe dans un parent sticky se décroche au défilement
echo str_replace(['class="nav nav-main"', 'aria-label="Navigation principale"'], ['class="nav nav-main nav-bottom"', 'aria-label="Navigation"'], $navHtml); ?>
<?php // Visite guidée : proposée après le parcours d'accueil, ou relancée depuis le Centre d'aide (?tour=1)
$tourOn = in_array($role, ['parent', 'student', 'tutor'], true) && (!empty($_SESSION['tour_offer']) || !empty($_GET['tour']));
if ($tourOn): $autoStart = !empty($_GET['tour']); unset($_SESSION['tour_offer']);
  $kidN = isset($s['first_name']) ? kid($s['first_name']) : 'votre enfant';
  $tourSteps = match ($role) {
    'parent' => [
      ['p', 'Suivi', "Votre page d'accueil : la prochaine séance, les derniers bilans, les contrôles à venir et la progression de $kidN."],
      ['p_child', 'Mon enfant', "Ses informations (que vous pouvez compléter), ses matières et ses notes, sa progression et tous les bilans."],
      ['p_planning', 'Planning', 'Toutes les séances, en liste ou en calendrier. Cliquez sur une séance pour voir son détail.'],
      ['p_finance', 'Finances', 'Le tarif, les montants à régler et l\'historique de vos paiements.'],
      ['p_messages', 'Messagerie', "Écrivez à la tutrice ici. Vous pouvez aussi lire les échanges entre $kidN et la tutrice."],
      ['account', 'Mon compte', 'Vos coordonnées, votre questionnaire, le Centre d\'aide (pour revoir cette visite) et la déconnexion.'],
    ],
    'student' => [
      ['s', 'Mon espace', 'Ta page d\'accueil : tes missions, ta prochaine séance et ton humeur de la semaine.'],
      ['s_planning', 'Planning', 'Toutes tes séances. Clique sur une séance pour voir ce qui est prévu.'],
      ['s_subjects', 'Mes matières', 'Tes notes, tes chapitres, des quiz pour t\'entraîner et le bilan de chaque séance.'],
      ['s_tools', 'Mes outils', 'Un chrono de travail, des méthodes pour mieux retenir et des fiches.'],
      ['s_messages', 'Messagerie', 'Écris à ta tutrice quand tu bloques ou si tu as une question.'],
      ['account', 'Mon compte', 'Ton profil, ta photo, le Centre d\'aide (pour revoir cette visite) et la déconnexion.'],
    ],
    default => [
      ['t', 'Tableau de bord', 'Vos tâches du jour, les prochaines séances et l\'activité des élèves.'],
      ['t_student', 'Fiche élève', 'Tout le dossier de l\'élève sélectionné : profil, objectifs, progression, bilans, analyse et questionnaires.'],
      ['t_student-seances', 'Séances', 'Le rythme convenu, le planning et les bilans de séance.'],
      ['t_student-matieres', 'Matières', 'Notes, chapitres, quiz (dont la génération par IA) et fichiers.'],
      ['t_messages', 'Messagerie', 'Les échanges avec les parents et les élèves.'],
      ['account', 'Mon compte', 'Votre profil, vos tarifs, les familles, les finances et le Centre d\'aide.'],
    ],
  };
  $tourData = ['auto' => $autoStart, 'kid' => $role === 'student', 'steps' => array_map(fn($x) => ['target' => $x[0], 'title' => $x[1], 'text' => $x[2]], $tourSteps)]; ?>
<script type="application/json" id="tour-data"><?= json_encode($tourData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif ?>
<main class="wrap">
<?php require __DIR__ . '/_flash.php' ?>
<?= $content ?>
</main>
<footer class="footer no-print"><a href="<?= e(url('privacy')) ?>">Données personnelles</a> · Aucune donnée transmise à des tiers</footer>
</body></html>
