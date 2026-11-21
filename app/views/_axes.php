<?php // $an, $prio ; axes prioritaires en tête (numérotés), puis les autres
$order = array_unique(array_merge($prio['axes'] ?? [], array_keys(AXES))); $sepShown = false; ?>
<?php if (!$an): ?><p class="muted">Pas encore d'analyse : les questionnaires doivent être remplis.</p><?php else: ?>
<?php if ($prio['axes']): ?><p class="axis-sep">Priorités</p><?php endif ?>
<?php foreach ($order as $a): $sc = $an['scores'][$a]['score'] ?? null; $rank = array_search($a, $prio['axes'], true); ?>
  <?php if ($rank === false && $prio['axes'] && !$sepShown): $sepShown = true; ?><p class="axis-sep">Autres axes</p><?php endif ?>
  <div class="axis <?= $rank !== false ? 'prio' : '' ?>">
    <span class="lbl"><?php if ($rank !== false): ?><span class="rank" title="Priorité <?= $rank + 1 ?>"><?= $rank + 1 ?></span><?php endif ?><?= e(AXES[$a]) ?></span>
    <span class="n"><?= $sc === null ? '<small>n.d.</small>' : $sc ?></span>
    <div class="bar"><i data-w="<?= (int)$sc ?>"></i></div>
  </div>
<?php endforeach ?>
<p class="muted"><small>Score indicatif de besoin d'accompagnement (0 = faible, 100 = élevé). Ce n'est pas un diagnostic. <?= $prio['source'] === 'manual' ? 'Priorités définies par la tutrice.' : 'Priorités calculées automatiquement.' ?></small></p>
<?php endif ?>
