<?php // Banque d'objectifs SMART. $s, $goals (objectifs existants), $bankKid (tutoiement)
$have = array_map(fn($g) => mb_strtolower($g['title']), $goals);
$kid = !empty($bankKid); ?>
<div class="card goal-bank" id="banque" data-sec-scope>
  <h2><?= icon('sparkles') ?> Banque d'objectifs SMART</h2>
  <p class="muted bank-intro"><small><?= $kid ? 'Des objectifs précis et mesurables, à réussir en une semaine (du lundi au dimanche). Choisis-en un ou deux : ta tutrice les suivra avec toi.' : 'Objectifs hebdomadaires précis et mesurables. Ajoutés « En cours » pour la semaine ' . e(week_label()) . ' ; modifiables ensuite.' ?></small></p>
  <nav class="sec-tabs bank-tabs" aria-label="Catégories" data-sec-tabs>
    <?php $first = true; foreach (GOAL_BANK as $ck => [$ic, $lbl]): ?><button type="button" class="<?= $first ? 'on' : '' ?>" data-sec-tab="<?= $ck ?>"><?= icon($ic) ?> <?= e($lbl) ?></button><?php $first = false; endforeach ?>
  </nav>
  <?php $first = true; foreach (GOAL_BANK as $ck => [$ic, $lbl, $axis, $items]): ?>
  <div class="sec-pane" data-sec-pane="<?= $ck ?>" id="bank-<?= $ck ?>" <?= $first ? '' : 'hidden' ?>>
    <ul class="bank-list"><?php foreach ($items as $i => [$t, $d, $days]): $added = in_array(mb_strtolower($t), $have, true); ?>
      <li class="bank-item<?= $added ? ' added' : '' ?>"><div class="bank-main"><strong><?= e($t) ?></strong><small class="muted"><?= e($d) ?></small>
          <span class="bank-meta"><?= icon('calendar') ?> 1 semaine · <?= e(AXES[$axis] ?? '') ?></span></div>
        <?php if ($added): ?><span class="badge sage"><?= icon('check') ?> Dans la liste</span>
        <?php else: ?><form method="post" action="<?= e(url('goal_bank')) ?>"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="key" value="<?= e($ck . ':' . $i) ?>"><input type="hidden" name="cat" value="<?= e($ck) ?>"><button class="btn ghost sm"><?= icon('plus') ?> Ajouter</button></form><?php endif ?></li>
    <?php endforeach ?></ul>
  </div>
  <?php $first = false; endforeach ?>
</div>
