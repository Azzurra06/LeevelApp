<?php // Compétences transversales en lecture (élève, parent). $s ; $kid (bool) pour le tutoiement
$kid ??= false;
$skillsList = function_exists('skills_of') ? skills_of((int)$s['id']) : [];
if (!$skillsList) return; ?>
<div class="card sk-summary">
  <h2><?= icon('sparkles') ?> <?= $kid ? 'Mes compétences' : 'Compétences travaillées' ?></h2>
  <p class="muted"><small><?= $kid ? 'Ce qu\'on travaille ensemble en plus des matières : ta méthode, ton organisation, ta confiance…' : 'Ce que la tutrice travaille en plus des matières scolaires.' ?></small></p>
  <div class="sk-sum-list">
  <?php foreach ($skillsList as $sk): $inPlace = array_values(array_filter($sk['practices'], fn($p) => $p['done'])); $todo = array_values(array_filter($sk['practices'], fn($p) => !$p['done']));
    $shared = array_values(array_filter($sk['obs'], fn($o) => $o['visibility'] === 'shared')); ?>
    <div class="sk-sum">
      <div class="sk-sum-head"><strong><span class="sk-emo"><?= $sk['emoji'] ?></span> <?= e($sk['name']) ?></strong><div class="sk-level-row"><?= skill_level_html($sk, $kid) ?></div></div>
      <?php if ($sk['note']): ?><p class="sk-note"><?= nl2br(e($sk['note'])) ?></p><?php endif ?>
      <?php if ($inPlace || $todo): ?><div class="ra-chips"><?php foreach ($inPlace as $p): ?><span class="ra-chip sk-on"><?= icon('check') ?> <?= e($p['label']) ?></span><?php endforeach ?><?php foreach ($todo as $p): ?><span class="ra-chip sk-off"><?= e($p['label']) ?></span><?php endforeach ?></div><?php endif ?>
      <?php if ($shared): ?><p class="muted sk-obs-last"><small><?= icon('message-circle') ?> « <?= e($shared[0]['content']) ?> »</small></p><?php endif ?>
    </div>
  <?php endforeach ?>
  </div>
  <p class="muted"><small><?= icon('check') ?> = outil déjà en place.</small></p>
</div>
