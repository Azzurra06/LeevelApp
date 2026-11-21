<?php // Vue calendrier mensuelle. $sessions ; $calBase (URL) : si défini, chaque séance renvoie vers sa fiche (tutrice)
$calBase ??= null;
$byDay = [];
foreach ($sessions as $se) $byDay[substr($se['starts_at'], 0, 10)][] = $se;
$months = [];
if ($sessions) {
    $first = new DateTime(substr($sessions[0]['starts_at'], 0, 7) . '-01');
    $last = new DateTime(substr(end($sessions)['starts_at'], 0, 7) . '-01');
    for ($m = clone $first, $i = 0; $m <= $last && $i < 6; $m->modify('+1 month'), $i++) $months[] = clone $m;
} else {
    $months[] = new DateTime(date('Y-m-01'));
}
$monthNames = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$today = date('Y-m-d');
$cls = ['planned' => '', 'done' => 'done', 'postponed' => 'postponed', 'cancelled' => 'cancelled']; ?>
<div class="cal-wrap">
<?php foreach ($months as $m): $y = (int)$m->format('Y'); $mo = (int)$m->format('n');
    $start = (clone $m)->modify('-' . ((int)$m->format('N') - 1) . ' days');
    $end = (clone $m)->modify('last day of this month'); $end->modify('+' . (7 - (int)$end->format('N')) . ' days'); ?>
  <div class="cal">
    <h3 class="cal-title"><?= e(ucfirst($monthNames[$mo])) ?> <?= $y ?></h3>
    <div class="cal-grid">
      <?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $d): ?><div class="cal-dow"><?= $d ?></div><?php endforeach ?>
      <?php for ($d = clone $start; $d <= $end; $d->modify('+1 day')): $key = $d->format('Y-m-d'); $out = (int)$d->format('n') !== $mo; ?>
        <div class="cal-day<?= $out ? ' out' : '' ?><?= $key === $today ? ' today' : '' ?>">
          <span class="cal-num"><?= (int)$d->format('j') ?></span>
          <?php if (!$out) foreach ($byDay[$key] ?? [] as $se):
            $label = session_label($se); $num = strtok($label, ' ');
            $tag = $calBase !== null ? 'a' : 'span'; $href = $calBase !== null ? ' href="' . e($calBase) . '#sess-' . (int)$se['id'] . '"' : ''; ?>
            <<?= $tag ?><?= $href ?> class="cal-ev <?= $se['mode'] ?> <?= $cls[$se['status']] ?>" title="<?= e($label . ' · ' . date('H\hi', strtotime($se['starts_at'])) . ' · ' . fdur((int)$se['duration_min']) . ' · ' . MODES[$se['mode']] . ' · ' . SESSION_STATUS[$se['status']]) ?>">
              <?= icon($se['mode'] === 'visio' ? 'video' : 'map-pin') ?><b><?= date('H\hi', strtotime($se['starts_at'])) ?></b><span class="cal-lbl"> <?= e(str_starts_with($num, 'S') ? $num : '') ?></span>
            </<?= $tag ?>>
          <?php endforeach ?>
        </div>
      <?php endfor ?>
    </div>
  </div>
<?php endforeach ?>
  <p class="cal-legend"><span class="cal-ev">Prévue</span> <span class="cal-ev done">Réalisée</span> <span class="cal-ev postponed">Reportée</span> <span class="cal-ev cancelled">Annulée</span> · <?= icon('map-pin') ?> Présentiel · <?= icon('video') ?> Visio</p>
</div>
