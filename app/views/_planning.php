<?php // $sessions ; lecture seule (parent, élève). Chaque séance se déplie pour voir le détail.
$byWeek = [];
foreach ($sessions as $se) $byWeek[$se['week_no'] ? 'Semaine ' . $se['week_no'] : 'Autres séances'][] = $se;
$viewerRole = current_user()['role'] ?? '';
$hm = fn(string $dt) => date('H\hi', strtotime($dt));
$endOf = fn(array $se) => date('H\hi', strtotime($se['starts_at']) + (int)$se['duration_min'] * 60);
$statusTone = ['planned' => '', 'done' => 'sage', 'postponed' => 'warn', 'cancelled' => 'err']; ?>
<?php if (!$sessions): ?><p class="muted">Le planning sera bientôt disponible.</p><?php return; endif ?>
<div data-views>
<div class="seg"><button type="button" class="on" data-view="list"><?= icon('list-checks') ?> Liste</button><button type="button" data-view="cal"><?= icon('calendar') ?> Calendrier</button></div>
<div data-pane="cal" hidden><?php require __DIR__ . '/_calendar.php' ?></div>
<div data-pane="list" class="plan-list">
<?php foreach ($byWeek as $wk => $list): $tot = array_sum(array_map(fn($x) => $x['status'] !== 'cancelled' && $x['status'] !== 'postponed' ? (int)$x['duration_min'] : 0, $list)); ?>
  <section class="plan-week">
    <h3 class="plan-week-h"><?= e($wk) ?> <span class="muted">· <?= fdur($tot) ?></span></h3>
    <?php foreach ($list as $se):
      $rep = in_array($se['status'], ['done', 'planned'], true) && strtotime($se['starts_at']) < time() ? session_report((int)$se['id']) : null;
      $from = !empty($se['rescheduled_from']) ? one('SELECT starts_at FROM sessions WHERE id=?', [$se['rescheduled_from']]) : null;
      $mergedFrom = !empty($se['merged_report_from']) ? one('SELECT starts_at FROM sessions WHERE id=?', [$se['merged_report_from']]) : null;
      $kids = $se['status'] === 'postponed' && array_key_exists('rescheduled_from', $se) ? all('SELECT starts_at, duration_min FROM sessions WHERE rescheduled_from=? OR merged_report_from=? ORDER BY starts_at', [$se['id'], $se['id']]) : []; ?>
    <details class="plan-item<?= in_array($se['status'], ['postponed', 'cancelled'], true) ? ' off' : '' ?>">
      <summary>
        <span class="plan-date"><b><?= date('j', strtotime($se['starts_at'])) ?></b><small><?= e(['', 'janv', 'févr', 'mars', 'avr', 'mai', 'juin', 'juil', 'août', 'sept', 'oct', 'nov', 'déc'][(int)date('n', strtotime($se['starts_at']))]) ?></small></span>
        <span class="plan-main">
          <span class="plan-meta"><?= e(fdatetime($se['starts_at'])) ?> – <?= e($endOf($se)) ?> · <?= e(MODES[$se['mode']]) ?></span>
          <strong class="plan-title"><?= e(session_label($se)) ?></strong>
        </span>
        <?php if ($se['status'] !== 'planned'): ?><span class="badge <?= $statusTone[$se['status']] ?>"><?= e(SESSION_STATUS[$se['status']]) ?></span><?php endif ?>
        <?= icon('chevron-right', 'plan-chev') ?>
      </summary>
      <div class="plan-detail">
        <dl class="fields">
          <div><dt>Horaire</dt><dd><?= e($hm($se['starts_at'])) ?> – <?= e($endOf($se)) ?> (<?= fdur((int)$se['duration_min']) ?>)</dd></div>
          <div><dt>Modalité</dt><dd><?= icon($se['mode'] === 'visio' ? 'video' : 'map-pin') ?> <?= e(MODES[$se['mode']]) ?></dd></div>
          <?php if (!empty($se['status_reason']) && in_array($se['status'], ['postponed', 'cancelled'], true)): ?><div class="wide"><dt>Raison</dt><dd><?= e($se['status_reason']) ?></dd></div><?php endif ?>
          <?php if ($kids): ?><div class="wide"><dt>Rattrapage</dt><dd><?= implode(' + ', array_map(fn($k) => e(fdatetime($k['starts_at'])), $kids)) ?></dd></div><?php endif ?>
          <?php if ($from): ?><div class="wide"><dt>Report</dt><dd>Report de la séance du <?= e(fdatetime($from['starts_at'])) ?></dd></div><?php endif ?>
          <?php if ($mergedFrom): ?><div class="wide"><dt>Rattrapage inclus</dt><dd><?= fdur((int)$se['merged_report_min']) ?> reportées de la séance du <?= e(fdatetime($mergedFrom['starts_at'])) ?></dd></div><?php endif ?>
          <?php if ($rep && report_subjects($rep)): ?><div class="wide"><dt>Matières abordées</dt><dd><?= e(implode(', ', report_subjects($rep))) ?></dd></div><?php endif ?>
          <?php if ($se['objectives']): ?><div class="wide"><dt>Au programme</dt><dd><?= nl2br(e($se['objectives'])) ?></dd></div><?php endif ?>
          <?php if ($rep && $viewerRole === 'parent' && $rep['sent_at'] && $rep['parent_summary']): ?><div class="wide"><dt>Bilan de la tutrice</dt><dd><?= nl2br(e($rep['parent_summary'])) ?></dd></div><?php endif ?>
          <?php if ($rep && $viewerRole === 'student' && $rep['worked_on']): ?><div class="wide"><dt>Le mot de ta tutrice</dt><dd><?= nl2br(e($rep['worked_on'])) ?></dd></div><?php endif ?>
          <?php if ($rep && $rep['next_focus']): ?><div class="wide"><dt>Pour la prochaine fois</dt><dd><?= e($rep['next_focus']) ?></dd></div><?php endif ?>
        </dl>
      </div>
    </details>
    <?php endforeach ?>
  </section>
<?php endforeach ?>
</div>
</div>
