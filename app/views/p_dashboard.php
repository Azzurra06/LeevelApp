<?php $title = 'Suivi';
$upcoming = array_values(array_filter($sessions, fn($x) => strtotime($x['starts_at']) >= strtotime('today') && in_array($x['status'], ['planned', 'postponed'], true)));
$next = $upcoming[0] ?? null; ?>
<div class="page-head"><div><h1>Bonjour <?= e($u['display_name']) ?></h1><p>Le suivi de <?= e($s['first_name']) ?>, simplement.</p></div></div>
<?php require __DIR__ . '/_onboarding.php' ?>
<div class="grid-2 align-start">
    <div class="card">
      <h2><?= icon('file-text') ?> Derniers bilans de séance</h2>
      <?php if (!$reports): ?><p class="muted">Après chaque séance, la tutrice vous enverra un court bilan ici.</p><?php endif ?>
      <?php foreach ($reports as $rp): ?>
        <div class="report-item"><span class="badge"><?= e(fdatetime($rp['starts_at'])) ?></span>
          <p data-clamp="120"><?= nl2br(e($rp['parent_summary'])) ?></p>
          <?php if ($rp['next_focus']): ?><p class="muted"><small><strong>Prochaine séance :</strong> <?= e($rp['next_focus']) ?></small></p><?php endif ?></div>
      <?php endforeach ?>
    </div>
    <div class="card">
      <h2><?= icon('calendar') ?> Prochaine séance</h2>
      <?php if ($next): ?>
        <p class="hello"><strong><?= e(fdatetime($next['starts_at'])) ?></strong></p>
        <p><?= icon($next['mode'] === 'visio' ? 'video' : 'map-pin') ?> <?= e(MODES[$next['mode']]) ?> · <?= fdur((int)$next['duration_min']) ?> · <span class="muted"><?= e(session_label($next)) ?></span></p>
      <?php else: ?><p class="muted">Aucune séance à venir.</p><?php endif ?>
      <a class="card-link" href="<?= e(url('p_planning')) ?>">Voir tout le planning →</a>
    </div>
</div><br>
<div class="grid-2 align-start">
    <?php require __DIR__ . '/_tests.php' ?>
    <?php require __DIR__ . '/_year_goals.php' ?>
</div>
<?php if ($monthly || $s['shared_strengths'] || $s['shared_to_work'] || $s['shared_message'] || $sharedObs): ?><br>
<div class="grid-2 align-start">
    <?php if ($monthly): ?>
    <div class="card accent">
      <h2><?= icon('chart-column') ?> Bilan du mois · <?= e(month_label($monthly['month'])) ?></h2>
      <p data-clamp="160"><?= nl2br(e($monthly['summary'])) ?></p>
    </div>
    <?php endif ?>
    <?php if ($s['shared_strengths'] || $s['shared_to_work'] || $s['shared_message'] || $sharedObs): ?>
    <div class="card">
      <h2><?= icon('message-circle') ?> Le mot de la tutrice</h2>
      <?php if ($s['shared_message']): ?><p><?= nl2br(e($s['shared_message'])) ?></p><?php endif ?>
      <?php foreach ($sharedObs as $o): ?><p><small class="muted"><?= e(fdate($o['created_at'])) ?></small> · <?= nl2br(e($o['content'])) ?></p><?php endforeach ?>
      <?php if ($s['shared_strengths']): ?><h3 class="sub-h"><?= icon('heart') ?> Points forts</h3><p><?= nl2br(e($s['shared_strengths'])) ?></p><?php endif ?>
      <?php if ($s['shared_to_work']): ?><h3 class="sub-h"><?= icon('compass') ?> À travailler</h3><p><?= nl2br(e($s['shared_to_work'])) ?></p><?php endif ?>
    </div>
    <?php endif ?>
</div>
<?php endif ?>
