<?php // Parent : matières suivies (lecture seule). $subjects, $tests, $s ?>
<div class="pc-layout">
<div class="pc-main">
<?php if (!$subjects): ?><div class="card"><div class="empty-ill sm"><span>📚</span><p class="muted">Les matières suivies apparaîtront ici dès que la tutrice les aura ajoutées.</p></div></div><?php endif ?>
<?php foreach ($subjects as $sub): $acq = count(array_filter($sub['chapters'], fn($c) => $c['status'] === 'acquired')); $nc = count($sub['chapters']); ?>
<div class="card pc-subj">
  <div class="row between"><h2><?= icon('book-open') ?> <?= e($sub['name']) ?></h2>
    <div class="pc-avg"><?php if ($sub['avg'] !== null): ?><strong><?= fmt_grade($sub['avg']) ?>/20</strong> <span class="<?= $sub['trend'] > 0 ? 'up' : ($sub['trend'] < 0 ? 'down' : '') ?>"><?= $sub['trend'] > 0 ? '↗' : ($sub['trend'] < 0 ? '↘' : '') ?></span><small class="muted">moyenne</small><?php else: ?><small class="muted">Pas encore de note</small><?php endif ?></div></div>
  <div class="pc-cols">
    <div><span class="ql">Notes</span>
      <?php if (!$sub['grades']): ?><p class="muted"><small>Aucune note saisie.</small></p><?php else: ?>
      <div class="ra-chips"><?php foreach (array_slice($sub['grades'], -8) as $g): ?><span class="ra-chip" title="<?= e(($g['label'] ?: 'Note') . ' · ' . fdate($g['graded_on'])) ?>"><?= fmt_grade((float)$g['value']) ?>/<?= fmt_grade((float)$g['out_of']) ?></span><?php endforeach ?></div>
      <?php if (count($sub['grades']) > 1): ?><div class="pc-spark"><?= grades_sparkline($sub['grades']) ?></div><?php endif ?><?php endif ?>
    </div>
    <div><span class="ql">Chapitres <?= $nc ? '· ' . $acq . ' / ' . $nc . ' acquis' : '' ?></span>
      <?php if (!$nc): ?><p class="muted"><small>Aucun chapitre pour l'instant.</small></p><?php endif ?>
      <ul class="pc-chap"><?php foreach ($sub['chapters'] as $c): ?><li><span><?= e($c['title']) ?></span><span class="badge <?= $c['status'] === 'acquired' ? 'sage' : ($c['status'] === 'reinforce' ? 'warn' : '') ?>"><?= e(CHAPTER_STATUS[$c['status']] ?? $c['status']) ?></span></li><?php endforeach ?></ul>
    </div>
  </div>
</div>
<?php endforeach ?>
</div>
<aside class="pc-aside">
<?php $tone = 'pc-side'; require __DIR__ . '/_tests.php'; $tone = ''; ?>
</aside>
</div>
