<?php $title = 'Suivi';
$upcoming = array_values(array_filter($sessions, fn($x) => strtotime($x['starts_at']) >= strtotime('today') && $x['status'] === 'planned'));
$next = $upcoming[0] ?? null; ?>
<div class="page-head"><div><h1>Bonjour <?= e($u['display_name']) ?></h1><p>Vue d’ensemble des cours de <?= e($s['first_name']) ?> : séances, bilans, contrôles et progression.</p></div></div>
<?php require __DIR__ . '/_onboarding.php' ?>
<div class="grid-2 two-cols">
  <div class="col-stack">
    <div class="card">
      <h2><?= icon('file-text') ?> Derniers bilans de séance</h2>
      <?php if (!$reports): ?><p class="muted">Après chaque séance, la tutrice vous enverra un court bilan ici.</p><?php endif ?>
      <?php foreach ($reports as $rp): ?>
        <div class="report-item"><span class="badge"><?= e(fdatetime($rp['starts_at'])) ?></span>
          <p data-clamp="120"><?= nl2br(e($rp['parent_summary'])) ?></p>
          <?php $fb = session_feedback_of((int)$rp['session_id']); if ($fb): ?><div class="fb-box"><strong><?= ['good' => '😄', 'ok' => '😐', 'hard' => '😣'][$fb['mood']] ?? '🙂' ?> Ressenti de <?= e($s['first_name']) ?><?= $fb['mood'] ? ' : ' . e(mb_strtolower(MOODS[$fb['mood']])) : '' ?></strong>
            <?php if (!empty($fb['note'])): ?><p>« <?= e($fb['note']) ?> »</p><?php endif ?>
            <?php if ($fb['understood']): ?><p><small class="muted">A compris :</small> <?= e($fb['understood']) ?></p><?php endif ?><?php if ($fb['to_deepen']): ?><p><small class="muted">À approfondir :</small> <?= e($fb['to_deepen']) ?></p><?php endif ?></div><?php endif ?>
          <?php if ($rp['next_focus']): ?><p class="muted"><small><strong>Prochaine séance :</strong> <?= e($rp['next_focus']) ?></small></p><?php endif ?></div>
      <?php endforeach ?>
    </div>
    <?php if ($monthly): ?>
    <div class="card accent">
      <h2><?= icon('chart-column') ?> Bilan du mois · <?= e(month_label($monthly['month'])) ?></h2>
      <p data-clamp="160"><?= nl2br(e($monthly['summary'])) ?></p>
    </div>
    <?php endif ?>
    <?php if ($subjects): ?>
    <div class="card">
      <h2><?= icon('book-open') ?> Matières suivies</h2>
      <ul class="list subj-sum"><?php foreach ($subjects as $sub): $acq = count(array_filter($sub['chapters'], fn($c) => $c['status'] === 'acquired')); ?>
        <li><div><strong><?= e($sub['name']) ?></strong><br><small class="muted"><?= count($sub['chapters']) ? $acq . ' / ' . count($sub['chapters']) . ' chapitre(s) acquis' : 'Pas encore de chapitre' ?></small></div>
          <div class="subj-sum-r"><?= grades_sparkline($sub['grades'], 120, 34) ?><span class="grade-pill"><?= fmt_grade($sub['avg']) ?><small>/20</small></span><?php if ($sub['trend']): ?><span class="trend <?= $sub['trend'] > 0 ? 'up' : 'down' ?>"><?= $sub['trend'] > 0 ? '↗' : '↘' ?></span><?php endif ?></div></li>
      <?php endforeach ?></ul>
    </div>
    <?php endif ?>
  </div>
  <div class="col-stack">
    <div class="card">
      <h2><?= icon('calendar') ?> Prochaine séance</h2>
      <?php if ($next): ?>
        <p class="hello"><strong><?= e(fdatetime($next['starts_at'])) ?></strong></p>
        <p><?= icon($next['mode'] === 'visio' ? 'video' : 'map-pin') ?> <?= e(MODES[$next['mode']]) ?> · <?= fdur((int)$next['duration_min']) ?> · <span class="muted"><?= e(session_label($next)) ?></span></p>
      <?php else: ?><p class="muted">Aucune séance à venir.</p><?php endif ?>
      <a class="card-link" href="<?= e(url('p_planning')) ?>">Voir tout le planning →</a>
    </div>
    <?php require __DIR__ . '/_tests.php' ?>
    <?php require __DIR__ . '/_year_goals.php' ?>
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
</div>
