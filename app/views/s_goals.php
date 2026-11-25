<?php $title = 'Mes objectifs';
$by = ['in_progress' => [], 'rework' => [], 'todo' => [], 'acquired' => []]; foreach ($goals as $g) $by[$g['status']][] = $g;
$lbl = ['in_progress' => 'En cours', 'rework' => 'À retravailler', 'todo' => 'À commencer', 'acquired' => 'Réussis 🎉']; ?>
<div class="page-head"><div><h1><?= icon('target') ?> Mes objectifs</h1><p>Ce que tu veux réussir, étape par étape, avec ta tutrice.</p></div></div>
<div class="card">
  <?php if (!$goals): ?><div class="empty-ill sm"><span>🎯</span><p class="muted">Pas encore d'objectif. Choisis-en un dans la banque ci-dessous, ou fixez-le ensemble à la prochaine séance.</p></div><?php endif ?>
  <?php foreach ($by as $k => $list): if (!$list) continue; ?>
    <h3 class="study-gh"><?= e($lbl[$k]) ?> <span class="muted">· <?= count($list) ?></span></h3>
    <ul class="sg-list"><?php foreach ($list as $g): ?>
      <li class="sg-item<?= $k === 'acquired' ? ' done' : '' ?>"><div class="sg-main"><strong><?= e($g['title']) ?></strong>
          <?php if ($g['description']): ?><small class="muted"><?= e($g['description']) ?></small><?php endif ?>
          <span class="bank-meta"><?= e(AXES[$g['category']] ?? '') ?><?= $g['target_date'] ? ' · ' . ($g['target_date'] === week_sunday() ? 'cette semaine, jusqu\'à dimanche' : 'jusqu\'au ' . e(fdate($g['target_date']))) : '' ?></span>
          <?php if ($g['tutor_comment']): ?><p class="goal-comment"><?= icon('message-circle') ?> <?= e($g['tutor_comment']) ?></p><?php endif ?></div>
        <div class="sg-side"><div class="goal-prog"><div class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></div><strong><?= (int)$g['progress'] ?>%</strong></div>
          <?php if ((int)$g['created_by'] === (int)$u['id'] && $g['status'] === 'todo'): ?><form method="post" action="<?= e(url('goal_bank')) ?>" data-confirm="Retirer cet objectif ?"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="goal_id" value="<?= (int)$g['id'] ?>"><button class="more-btn">Retirer</button></form><?php endif ?></div></li>
    <?php endforeach ?></ul>
  <?php endforeach ?>
</div><br>
<?php $bankKid = true; require __DIR__ . '/_goal_bank.php'; ?>
