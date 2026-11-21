<?php $title = 'Synthèse - ' . $s['first_name']; ?>
<div class="row between no-print"><a class="btn ghost sm" href="<?= e(url('t_rdv', ['sid' => $s['id']])) ?>"><?= icon('chevron-left') ?> Retour</a><button class="btn ghost sm" data-print><?= icon('download') ?> Imprimer / PDF</button></div><br>
<div class="card accent"><h1>L'accompagnement de <?= e($s['first_name']) ?></h1><p><?= e($reco['summary']) ?></p></div><br>
<div class="card"><h2><?= icon('target') ?> Nos priorités</h2>
  <?php foreach ($prio['axes'] as $i => $a): ?><p><span class="badge warn"><?= $i + 1 ?></span> <strong><?= e(AXES[$a]) ?></strong><?= !empty($prio['notes'][$a]) ? ' - ' . e($prio['notes'][$a]) : '' ?></p><?php endforeach ?>
  <?php if (!$prio['axes']): ?><p class="muted">À définir.</p><?php endif ?></div><br>
<div class="grid">
  <div class="card"><h3><?= icon('heart') ?> Points forts</h3><ul class="dots"><?php foreach (array_slice($an['strengths'] ?? [], 0, 6) ?: ['À découvrir ensemble'] as $x): ?><li><?= e($x) ?></li><?php endforeach ?></ul></div>
  <div class="card"><h3><?= icon('lightbulb') ?> Ce que nous allons mettre en place</h3><ul class="dots"><?php foreach (array_slice(array_unique(array_merge($reco['methods'], $reco['supports'])), 0, 8) as $x): ?><li><?= e($x) ?></li><?php endforeach ?></ul></div>
</div><br>
<div class="card"><h2><?= icon('calendar') ?> Le premier mois</h2>
  <div class="grid"><?php foreach ($reco['plan'] as $w => $p): ?><div class="card flat"><h3>Semaine <?= $w ?></h3><p><strong><?= e($p['title']) ?></strong></p><ul class="dots"><?php foreach ($p['items'] as $it): ?><li><?= e($it) ?></li><?php endforeach ?></ul></div><?php endforeach ?></div><br>
  <?php require __DIR__ . '/_planning.php' ?></div><br>
<?php if ($goals): ?><div class="card"><h2><?= icon('flag') ?> Premiers objectifs</h2><ul class="list"><?php foreach ($goals as $g): ?><li><?= icon('target') ?><div><strong><?= e($g['title']) ?></strong><br><small class="muted"><?= e($g['description']) ?></small></div></li><?php endforeach ?></ul></div><?php endif ?>
<p class="muted"><small>Les indicateurs sont des repères pour orienter l'accompagnement, pas des diagnostics. Ils évolueront avec les séances.</small></p>
