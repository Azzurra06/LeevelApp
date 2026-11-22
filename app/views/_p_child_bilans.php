<?php // Parent : bilans mensuels publiés et bilans de séance envoyés. $monthlies, $reports, $s ?>
<div class="grid-2 two-cols">
 <div class="col-stack">
  <div class="card"><h2><?= icon('chart-column') ?> Bilans du mois</h2>
    <?php if (!$monthlies): ?><p class="muted">La tutrice publiera ici un bilan à la fin de chaque mois.</p><?php endif ?>
    <?php foreach ($monthlies as $i => $m): ?>
      <details class="pc-month" <?= $i === 0 ? 'open' : '' ?>><summary><strong><?= e(month_label($m['month'])) ?></strong> <small class="muted">· publié le <?= e(fdate($m['published_at'])) ?></small></summary>
        <p><?= nl2br(e((string)$m['summary'])) ?></p></details>
    <?php endforeach ?>
  </div>
 </div>
 <div class="col-stack">
  <div class="card"><h2><?= icon('file-text') ?> Bilans de séance</h2>
    <?php if (!$reports): ?><p class="muted">Après chaque séance, la tutrice vous enverra un court bilan.</p><?php endif ?>
    <?php foreach ($reports as $rp): ?>
      <div class="report-item"><span class="badge"><?= e(fdatetime($rp['starts_at'])) ?></span>
        <?= report_subject_chips($rp) ?>
        <p><?= nl2br(e((string)$rp['parent_summary'])) ?></p>
        <?php if ($rp['next_focus']): ?><p class="muted"><small><strong>Prochaine séance :</strong> <?= e($rp['next_focus']) ?></small></p><?php endif ?></div>
    <?php endforeach ?>
  </div>
 </div>
</div>
