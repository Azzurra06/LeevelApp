<?php // $sessions ; lecture seule
$byWeek = [];
foreach ($sessions as $se) $byWeek[$se['week_no'] ? 'Semaine ' . $se['week_no'] : 'Autres séances'][] = $se; ?>
<?php if (!$sessions): ?><p class="muted">Le planning sera bientôt disponible.</p><?php return; endif ?>
<div data-views>
<div class="seg"><button type="button" class="on" data-view="list"><?= icon('list-checks') ?> Liste</button><button type="button" data-view="cal"><?= icon('calendar') ?> Calendrier</button></div>
<div data-pane="cal" hidden><?php require __DIR__ . '/_calendar.php' ?></div>
<div data-pane="list">
<?php foreach ($byWeek as $wk => $list): $tot = array_sum(array_column($list, 'duration_min')); ?>
  <h3><?= e($wk) ?> <small class="muted">· <?= fdur($tot) ?></small></h3>
  <ul class="list">
  <?php foreach ($list as $se): ?>
    <li><?= icon($se['mode'] === 'visio' ? 'video' : 'map-pin') ?>
      <div><strong><?= e(fdatetime($se['starts_at'])) ?></strong> · <?= fdur((int)$se['duration_min']) ?> · <?= e(MODES[$se['mode']]) ?>
        <?php if ($se['status'] !== 'planned'): ?><span class="badge <?= $se['status'] === 'done' ? 'sage' : 'warn' ?>"><?= e(SESSION_STATUS[$se['status']]) ?></span><?php endif ?>
        <br><small class="muted"><?= e(session_label($se)) ?><?= !empty($se['status_reason']) && in_array($se['status'], ['postponed', 'cancelled'], true) ? ' · ' . e($se['status_reason']) : '' ?><?= !empty($se['rescheduled_from']) ? ' · report' : '' ?><?= !empty($se['merged_report_min']) ? ' · dont ' . fdur((int)$se['merged_report_min']) . ' de rattrapage' : '' ?></small></div></li>
  <?php endforeach ?>
  </ul>
<?php endforeach ?>
</div>
</div>
