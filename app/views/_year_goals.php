<?php // Objectifs de l'année : présentation simple. $yearGoals
$isKid = (current_user()['role'] ?? '') === 'student'; ?>
<div class="card">
  <h2><?= icon('target') ?> <?= $isKid ? 'Mes objectifs de l\'année' : 'Objectifs de l\'année' ?></h2>
  <?php if (!$yearGoals): ?><p class="muted">Ils seront fixés avec la tutrice dans les premières semaines.</p><?php endif ?>
  <ul class="year-goals"><?php foreach ($yearGoals as $g): $st = $g['status'] === 'acquired' ? 'done' : (in_array($g['status'], ['in_progress', 'rework'], true) ? 'going' : 'todo'); ?>
    <li class="<?= $st ?>">
      <span class="yg-dot" aria-hidden="true"><?= $st === 'done' ? icon('check') : '' ?></span>
      <div><strong><?= e($g['title']) ?></strong><?php if ($g['description']): ?><small class="muted"><?= e($g['description']) ?></small><?php endif ?></div>
      <span class="badge <?= $st === 'done' ? 'sage' : ($st === 'going' ? 'warn' : '') ?>"><?= e(YEAR_STATUS[$g['status']] ?? 'Pas commencé') ?></span>
    </li>
  <?php endforeach ?></ul>
</div>
