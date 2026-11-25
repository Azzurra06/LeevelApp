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
  'quest' => function () use ($subs, $sidp, $s) { ?>
    <div class="card tone-teal quest-card"><h2><?= icon('clipboard-list') ?> Questionnaires</h2>
      <div class="quest-cols"><?php foreach (['parent' => ['Parent', 'user'], 'student' => ['Élève', 'smile']] as $c => [$l, $ic]): $sb = $subs[$c]; $ok = $sb && $sb['status'] === 'completed'; ?>
        <a class="quest-box<?= $ok ? ' ok' : '' ?>" href="<?= e(url('t_student', $sidp + ['tab' => $c === 'parent' ? 'parent' : 'eleve'])) ?>">
          <span class="quest-ic"><?= icon($ic) ?></span>
          <span class="quest-t"><strong>Questionnaire <?= mb_strtolower($l) ?></strong><small><?= !$sb ? 'Pas encore commencé' : ($ok ? 'Complété le ' . e(fdate($sb['completed_at'])) : 'En cours de remplissage') ?></small></span>
          <span class="badge <?= $ok ? 'ok' : ($sb ? 'warn' : '') ?>"><?= $ok ? 'Complété' : ($sb ? 'En cours' : 'En attente') ?></span><?= icon('chevron-right', 'go') ?></a>
      <?php endforeach ?></div></div>
  <?php },
  'reco' => function () use ($reco, $prio, $an, $sidp) { $meth = array_slice($reco['methods'] ?? [], 0, 4); $sup = array_slice($reco['supports'] ?? [], 0, 2); ?>
    <div class="card tone-violet reco-card"><h2><?= icon('lightbulb') ?> Comment l'accompagner</h2>
      <?php if (!$an || !$meth): ?><p class="muted">Les pistes apparaîtront une fois les questionnaires complétés.</p>
      <?php else: ?>
        <p class="reco-prio"><span class="muted">Priorités :</span> <?= e(implode(' · ', array_map(fn($a) => AXES[$a] ?? $a, array_slice($prio['axes'] ?? [], 0, 2)))) ?></p>
        <ul class="reco-list"><?php foreach ($meth as $m): ?><li><?= icon('check') ?><span><?= e($m) ?></span></li><?php endforeach ?></ul>
        <?php if ($sup): ?><p class="reco-sup"><span class="muted">Supports :</span> <?= e(implode(' · ', $sup)) ?></p><?php endif ?>
      <?php endif ?>
      <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'analyse'])) ?>">Toutes les pistes →</a></div>
  <?php },
  'missions' => function () use ($allGoals, $sidp) { $gs = goals_split($allGoals); $list = $gs['missions']; $done = count(array_filter($list, fn($g) => $g['status'] === 'acquired')); ?>
    <div class="card tone-blue"><h2><?= icon('rocket') ?> Missions de la semaine</h2>
      <p class="muted dash-sub"><?= e(week_label()) ?><?= $list ? ' · ' . $done . ' / ' . count($list) . ' réalisée' . ($done > 1 ? 's' : '') : '' ?></p>
      <?php if (!$list): ?><p class="muted">Aucune mission cette semaine.</p><?php endif ?>
      <ul class="dash-goals"><?php foreach (array_slice($list, 0, 5) as $g): ?><li><span class="gl-dot <?= GOAL_DOT[$g['status']] ?>"></span><span class="dg-t"><?= e($g['title']) ?></span><span class="m-chips"><?= mission_chips($g) ?></span></li><?php endforeach ?></ul>
      <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'objectifs'])) ?>#objp-missions"><?= $list ? 'Gérer les missions' : 'Choisir dans la banque' ?> →</a></div>
  <?php },
  'year' => function () use ($allGoals, $sidp) { $list = goals_split($allGoals)['year']; ?>
    <div class="card tone-violet"><h2><?= icon('target') ?> Objectifs de l'année</h2>
      <?php if (!$list): ?><p class="muted">Aucun objectif de l'année : gagner en autonomie, augmenter sa moyenne…</p><?php endif ?>
      <ul class="dash-goals year"><?php foreach (array_slice($list, 0, 5) as $g): ?><li><span class="gl-dot <?= GOAL_DOT[$g['status']] ?>"></span><span class="dg-t"><?= e($g['title']) ?></span><span class="gl-prog"><span class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></span><b><?= (int)$g['progress'] ?>%</b></span></li><?php endforeach ?></ul>
      <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'objectifs'])) ?>#objp-annee">Gérer les objectifs de l'année →</a></div>
  <?php },
  'agenda' => function () use ($study, $weekSessions, $tests, $sidp) {
    $mon = week_monday(); $sun = week_sunday(); $today = date('Y-m-d');
    $late = array_filter($study, fn($t) => !$t['done_at'] && $t['due_date'] < $today);
    $week = array_filter($study, fn($t) => $t['due_date'] >= max($mon, $today) && $t['due_date'] <= $sun);
    $items = [];
    foreach ($weekSessions as $se) if (substr($se['starts_at'], 0, 10) >= $today) $items[] = [substr($se['starts_at'], 0, 10), 'sess', date('H\hi', strtotime($se['starts_at'])) . ' · Séance', session_label($se), false];
    foreach ($tests as $te) if ($te['test_date'] <= $sun) $items[] = [$te['test_date'], 'test', 'Contrôle', $te['subject'] ?: ($te['title'] ?? ''), false];
    foreach ($week as $t) $items[] = [$t['due_date'], $t['kind'] === 'revision' ? 'rev' : 'dev', STUDY_KINDS[$t['kind']], ($t['subject'] ? $t['subject'] . ' · ' : '') . $t['title'], (bool)$t['done_at']];
    usort($items, fn($a, $b) => strcmp($a[0], $b[0]));
    $dn = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.']; ?>
    <div class="card tone-blue"><h2><?= icon('list-checks') ?> Agenda de la semaine</h2>
      <p class="muted dash-sub"><?= e(week_label()) ?></p>
      <?php if ($late): ?><p class="dash-late"><?= icon('triangle-alert') ?> <?= count($late) ?> devoir<?= count($late) > 1 ? 's' : '' ?> en retard</p><?php endif ?>
      <?php if (!$items): ?><p class="muted">Rien d'autre de prévu d'ici dimanche.</p><?php endif ?>
      <ul class="dash-ag"><?php foreach (array_slice($items, 0, 7) as [$d, $c, $k, $txt, $isDone]): ?><li class="<?= $isDone ? 'done' : '' ?>"><span class="da-d"><?= $d === $today ? "Aujourd'hui" : $dn[(int)date('w', strtotime($d))] . ' ' . date('d/m', strtotime($d)) ?></span><span class="da-k <?= $c ?>"><?= e($k) ?></span><span class="da-t"><?= e($txt) ?></span></li><?php endforeach ?></ul>
      <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'agenda'])) ?>">Ouvrir l'agenda →</a></div>
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
  ['target', 'Objectifs', ['missions', 'year']],
  ['list-checks', 'Agenda', ['tests', 'agenda']],
  ['chart-column', 'Analyse', ['needs', 'reco']],
];
foreach ($sections as [$ic, $title, $keys]): ?>
<h2 class="dash-sec"><?= e($title) ?></h2>
<div class="grid-2 two-cols dash-row"><?php foreach ($keys as $k) $w[$k]() ?></div>
<?php endforeach ?>
<div class="dash-row dash-full"><?php $w['quest']() ?></div>
