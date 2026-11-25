<?php $title = 'Mes objectifs'; $gs = goals_split($goals);
$row = function (array $g, bool $canDone) use ($u, $s) { ?>
  <li class="sg-row<?= $g['status'] === 'acquired' ? ' done' : '' ?>"><span class="gl-dot <?= GOAL_DOT[$g['status']] ?>"></span>
    <div class="gl-main"><strong><?= e($g['title']) ?></strong>
      <?php if ($g['description']): ?><small class="muted"><?= e($g['description']) ?></small><?php endif ?>
      <?php if ($g['tutor_comment']): ?><small class="sg-com"><?= icon('message-circle') ?> <?= e($g['tutor_comment']) ?></small><?php endif ?></div>
    <div class="sg-side">
      <?php if ($canDone): $mk = mission_status_key($g); ?>
        <form method="post" action="<?= e(url('s_goal')) ?>" class="m-pick"><?= csrf_field() ?><input type="hidden" name="goal_id" value="<?= (int)$g['id'] ?>"><input type="hidden" name="back" value="s_goals">
          <span class="m-lbl">Statut</span><span class="m-seg"><?php foreach (MISSION_STATUS as $k => $l): ?><button name="mstatus" value="<?= $k ?>" class="<?= $mk === $k ? 'on st-' . $k : '' ?>"><?= e($l) ?></button><?php endforeach ?></span>
          <span class="m-lbl">Difficulté</span><span class="m-seg"><?php foreach (DIFFICULTY as $k => $l): ?><button name="difficulty" value="<?= $k ?>" class="<?= ($g['difficulty'] ?? '') === $k ? 'on df-' . $k : '' ?>"><?= e($l) ?></button><?php endforeach ?></span>
        </form>
      <?php else: ?><span class="gl-prog"><span class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></span><b><?= (int)$g['progress'] ?>%</b></span><?php endif ?>
      <?php if ((int)$g['created_by'] === (int)$u['id'] && $g['status'] === 'todo'): ?><form method="post" action="<?= e(url('goal_bank')) ?>" data-confirm="Retirer cette mission ?"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="goal_id" value="<?= (int)$g['id'] ?>"><button class="more-btn">Retirer</button></form><?php endif ?>
    </div></li>
<?php }; ?>
<div class="page-head"><div><h1><?= icon('target') ?> Mes objectifs</h1><p>Tes missions de la semaine et tes grands objectifs de l'année.</p></div></div>
<div class="obj-page" id="objp" data-sec-scope>
<nav class="sec-tabs obj-tabs" aria-label="Mes objectifs" data-sec-tabs>
  <button type="button" class="on" data-sec-tab="missions"><?= icon('rocket') ?> Mes missions de la semaine <small><?= count($gs['missions']) ?></small></button>
  <button type="button" data-sec-tab="annee"><?= icon('target') ?> Mes objectifs de l'année <small><?= count($gs['year']) ?></small></button>
</nav>
<section class="sec-pane" data-sec-pane="missions" id="objp-missions">
  <p class="muted obj-week"><?= icon('calendar') ?> Semaine <?= e(week_label()) ?></p>
  <div class="card obj-card">
    <?php if (!$gs['missions']): ?><p class="muted">Pas encore de mission cette semaine : choisis-en une dans la banque ci-dessous.</p><?php endif ?>
    <ul class="gl-list"><?php foreach ($gs['missions'] as $g) $row($g, true) ?></ul>
  </div>
  <?php $bankKid = true; require __DIR__ . '/_goal_bank.php'; ?>
  <?php if ($gs['history']): ?><details class="card obj-fold"><summary><?= icon('archive') ?> Mes missions des semaines passées <span class="muted">· <?= count($gs['history']) ?></span></summary>
    <ul class="gl-list"><?php foreach ($gs['history'] as $g) $row($g, false) ?></ul></details><?php endif ?>
</section>
<section class="sec-pane" data-sec-pane="annee" id="objp-annee" hidden>
  <div class="card obj-card">
    <?php if (!$gs['year']): ?><p class="muted">Tu les fixeras avec ta tutrice : par exemple gagner en autonomie ou augmenter tes notes.</p><?php endif ?>
    <ul class="gl-list"><?php foreach ($gs['year'] as $g) $row($g, false) ?></ul>
  </div>
</section>
</div>
