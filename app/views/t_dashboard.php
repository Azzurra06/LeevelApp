<?php $title = 'Tableau de bord'; ?>
<?php if (!$s): ?>
  <div class="card"><h1>Bienvenue</h1><p>Aucun dossier pour l'instant.</p><a class="btn" href="<?= e(url('t_users')) ?>"><?= icon('plus') ?> Créer le dossier d'un élève</a></div>
<?php return; endif; $sidp = ['sid' => $s['id']]; ?>
<div class="page-head">
  <div><h1><?= e($s['first_name']) ?> <?php if ($s['is_demo']): ?><span class="badge demo">DÉMO</span><?php endif ?></h1><p><?= e($s['grade']) ?><?= $s['school'] ? ' · ' . e($s['school']) : '' ?> · <?= e($s['family_name']) ?></p></div>
</div>
<?php
$todoMissing = sessions_missing_report((int)$s['id']);
$todoHelp = array_filter($help, fn($h) => $h['entry_type'] === 'help_request' && strtotime($h['created_at']) > time() - 7 * 86400);
$todoUnread = unread_count(current_user(), (int)$s['family_id']);
$tests = tests_upcoming((int)$s['id'], 5);
$w = [
  'todo' => function () use ($todoMissing, $todoHelp, $todoUnread, $sidp, $s, $blockersUnseen) { ?>
    <div class="card todo-card tone-orange"><h2><?= icon('list-checks') ?> À faire</h2>
      <?php if (!$todoMissing && !$todoHelp && !$todoUnread && !$blockersUnseen): ?><p class="muted">Rien en attente. 👍</p><?php endif ?>
      <ul class="list">
        <?php if ($blockersUnseen): ?><li class="li-link"><a href="<?= e(url('t_student', $sidp + ['tab' => 'matieres'])) ?>#blocages"><?= icon('circle-help') ?><div><strong><?= $blockersUnseen ?></strong> blocage(s) signalé(s) par <?= e($s['first_name']) ?></div><?= icon('chevron-right', 'go') ?></a></li><?php endif ?>
        <?php foreach (array_slice($todoMissing, 0, 4) as $se): ?><li class="li-link"><a href="<?= e(url('t_student', $sidp + ['tab' => 'seances'])) ?>#sess-<?= (int)$se['id'] ?>"><?= icon('file-text') ?><div>Rédiger le bilan · <strong><?= e(session_label($se)) ?></strong><br><small class="muted"><?= e(fdatetime($se['starts_at'])) ?></small></div><?= icon('chevron-right', 'go') ?></a></li><?php endforeach ?>
        <?php if ($todoUnread): ?><li class="li-link"><a href="<?= e(url('t_messages', $sidp)) ?>"><?= icon('message-circle') ?><div><strong><?= $todoUnread ?></strong> message(s) non lu(s)</div><?= icon('chevron-right', 'go') ?></a></li><?php endif ?>
        <?php if ($todoHelp): ?><li class="li-link"><a href="<?= e(url('t_student', $sidp + ['tab' => 'progression'])) ?>"><?= icon('circle-help') ?><div><strong><?= count($todoHelp) ?></strong> demande(s) d'aide de <?= e($s['first_name']) ?> cette semaine</div><?= icon('chevron-right', 'go') ?></a></li><?php endif ?>
      </ul></div>
  <?php },
  'tests' => function () use ($tests, $s) { $tone = 'tone-gold'; require __DIR__ . '/_tests.php'; },
  'needs' => function () use ($an, $prio, $reco, $sidp) { ?>
    <div class="card tone-green"><h2><?= icon('chart-column') ?> Synthèse des besoins</h2><?php require __DIR__ . '/_axes.php' ?><a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'analyse'])) ?>">Analyse détaillée →</a></div>
  <?php },
  'quest' => function () use ($subs, $sidp) { ?>
    <div class="card tone-teal"><h2><?= icon('clipboard-list') ?> Questionnaires</h2><ul class="list">
      <?php foreach (['parent' => 'Parent', 'student' => 'Élève'] as $c => $l): $sb = $subs[$c]; ?>
        <li class="li-link"><a href="<?= e(url('t_student', $sidp + ['tab' => $c === 'parent' ? 'parent' : 'eleve'])) ?>"><?= icon($sb && $sb['status'] === 'completed' ? 'circle-check' : 'clock') ?><div><strong><?= $l ?></strong> - <?= !$sb ? 'en attente' : ($sb['status'] === 'completed' ? 'complété le ' . fdate($sb['completed_at']) : 'en cours') ?></div><?= icon('chevron-right', 'go') ?></a></li>
      <?php endforeach ?></ul></div>
  <?php },
  'sessions' => function () use ($sessions, $sidp) { ?>
    <div class="card tone-blue"><h2><?= icon('calendar') ?> Prochaines séances</h2>
      <?php if (!$sessions): ?><p class="muted">Aucune séance prévue.</p><?php endif ?>
      <ul class="list"><?php foreach ($sessions as $se): ?><li><?= icon($se['mode'] === 'visio' ? 'video' : 'map-pin') ?><div><strong><?= e(fdatetime($se['starts_at'])) ?></strong> · <?= fdur((int)$se['duration_min']) ?><br><small class="muted"><?= e(session_label($se)) ?></small></div></li><?php endforeach ?></ul>
      <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'seances'])) ?>">Gérer le planning →</a></div>
  <?php },
  'goals' => function () use ($goals, $goalStats, $sidp) { ?>
    <div class="card tone-violet"><h2><?= icon('target') ?> Objectifs en cours</h2>
      <?php if ($goalStats['n']): ?><p class="muted"><?= (int)$goalStats['acq'] ?> / <?= (int)$goalStats['n'] ?> acquis · progression moyenne <?= (int)$goalStats['avgp'] ?>%</p><div class="bar bar-gap"><i data-w="<?= (int)$goalStats['avgp'] ?>"></i></div><?php else: ?><p class="muted">Aucun objectif.</p><?php endif ?>
      <ul class="list airy"><?php foreach ($goals as $g): ?><li><?= icon('flag') ?><div><?= e($g['title']) ?><div class="badges"><?php require __DIR__ . '/_goal_status.php' ?><?= $g['student_done_at'] ? '<span class="badge sage">Signalé terminé</span>' : '' ?></div></div></li><?php endforeach ?></ul>
      <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'objectifs'])) ?>">Gérer les objectifs →</a></div>
  <?php },
  'activity' => function () use ($help) { ?>
    <div class="card tone-pink"><h2><?= icon('message-circle') ?> Retours de l'élève</h2>
      <?php if (!$help): ?><p class="muted">Aucun retour pour l'instant.</p><?php endif ?>
      <ul class="list airy"><?php foreach ($help as $h): ?><li><?= icon(entry_icon($h['entry_type'])) ?><div>
        <?= e(entry_label($h)) ?><?= $h['goal'] ? ' · ' . e($h['goal']) : '' ?>
        <?= $h['comment'] ? '<br>« ' . e($h['comment']) . ' »' : '' ?><br><small class="muted"><?= e(fdate($h['created_at'], 'd/m H:i')) ?></small></div></li><?php endforeach ?></ul></div>
  <?php },
];
// Priorité motivation/autonomie → retours élève et objectifs remontent ; méthodo/évaluations → planning d'abord
$w['recap'] = function () use ($recap, $sidp, $s) { $tone = 'tone-violet'; require __DIR__ . '/_recap_card.php'; };
// Ordre fixe : ce qu'il faut faire, les séances, puis le suivi de l'élève, puis l'analyse
$order = ['todo', 'sessions', 'recap', 'activity', 'tests', 'goals', 'needs', 'quest'];
?>
<?php $sections = [
  ['list-checks', 'À traiter', ['todo', 'activity']],
  ['calendar', 'Séances', ['sessions', 'recap']],
  ['target', 'Suivi', ['goals', 'tests']],
  ['chart-column', 'Analyse', ['needs', 'quest']],
];
foreach ($sections as [$ic, $title, $keys]): ?>
<h2 class="dash-sec"><?= icon($ic) ?> <?= e($title) ?></h2>
<div class="grid-2 two-cols dash-row"><?php foreach ($keys as $k) $w[$k]() ?></div>
<?php endforeach ?>
