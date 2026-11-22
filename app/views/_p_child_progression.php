<?php // Parent : progression. $goals, $subjects, $doneSe, $fbs, $s
$minutes = array_sum(array_map(fn($x) => (int)$x['duration_min'], $doneSe));
$acq = count(array_filter($goals, fn($g) => $g['status'] === 'acquired'));
$avgG = $goals ? (int)round(array_sum(array_map(fn($g) => (int)$g['progress'], $goals)) / count($goals)) : 0;
$moodEmo = ['good' => '😄', 'ok' => '😐', 'hard' => '😣']; ?>
<div class="card"><h2><?= icon('chart-column') ?> En bref</h2>
  <div class="month-stats">
    <div class="stat"><span class="muted">Séances réalisées</span><strong><?= count($doneSe) ?></strong><small class="muted"><?= fdur($minutes) ?> au total</small></div>
    <div class="stat"><span class="muted">Progression moyenne</span><strong><?= $avgG ?>%</strong><small class="muted">sur <?= count($goals) ?> objectif<?= count($goals) > 1 ? 's' : '' ?></small></div>
    <div class="stat"><span class="muted">Objectifs atteints</span><strong><?= $acq ?> / <?= count($goals) ?></strong></div>
    <div class="stat"><span class="muted">Ressenti récent</span><strong><?= $fbs ? implode(' ', array_map(fn($f) => $moodEmo[$f['mood']] ?? '🙂', array_slice($fbs, 0, 4))) : '—' ?></strong><?php if ($fbs): ?><small class="muted">après les dernières séances</small><?php endif ?></div>
  </div>
</div>
<div class="grid-2 two-cols">
 <div class="col-stack">
  <div class="card"><h2><?= icon('target') ?> Objectifs</h2>
    <?php if (!$goals): ?><p class="muted">Les objectifs seront fixés avec la tutrice dans les premières semaines.</p><?php endif ?>
    <?php foreach ($goals as $g): ?><div class="axis"><span class="lbl"><?= e($g['title']) ?> <?php require __DIR__ . '/_goal_status.php' ?></span><span class="n"><?= (int)$g['progress'] ?>%</span><div class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></div></div><?php endforeach ?>
  </div>
 </div>
 <div class="col-stack">
  <div class="card"><h2><?= icon('chart-column') ?> Moyennes par matière</h2>
    <?php $withAvg = array_filter($subjects, fn($x) => $x['avg'] !== null); if (!$withAvg): ?><p class="muted">Aucune note pour l'instant.</p><?php endif ?>
    <?php foreach ($withAvg as $sub): ?><div class="grade-row"><strong><?= e($sub['name']) ?></strong><span class="grade-dots muted"><small><?= count($sub['grades']) ?> note<?= count($sub['grades']) > 1 ? 's' : '' ?></small></span><span class="grade-avg"><?= fmt_grade($sub['avg']) ?>/20 <?= $sub['trend'] > 0 ? '<span class="up">↗</span>' : ($sub['trend'] < 0 ? '<span class="down">↘</span>' : '') ?></span></div><?php endforeach ?>
    <a class="card-link" href="<?= e(url('p_child', ['sid' => $s['id'], 'tab' => 'matieres'])) ?>">Voir le détail des matières →</a>
  </div>
 </div>
</div>
