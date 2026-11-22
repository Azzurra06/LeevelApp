<?php // Analyse détaillée (tutrice uniquement). $an, $prio, $reco, $s, $obs ?>
<?php if (!$an): ?>
  <div class="card"><p>Aucune analyse pour l'instant.</p>
  <form method="post" action="<?= e(url('t_analyze')) ?>"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= e($_GET['r'] ?? 't_student') ?>"><button class="btn"><?= icon('refresh-cw') ?> Calculer l'analyse</button></form></div>
<?php else: ?>
<?php $back = e($_GET['r'] ?? 't_student');
$divs = array_values(array_filter($an['divergences'], fn($d) => $d['type'] === 'divergence'));
$convs = array_values(array_filter($an['divergences'], fn($d) => $d['type'] !== 'divergence'));
$prioAxes = $prio['axes']; ?>

<h2 class="an-sec"><span class="qn-num">1</span> L'essentiel</h2>
<div class="card an-key">
  <p class="an-summary"><?= e($reco['summary']) ?></p>
  <div class="an-key-row">
    <div><span class="ql">Priorités retenues</span><div class="ra-chips"><?php foreach ($prioAxes as $k => $a): ?><span class="ra-chip prio"><?= $k + 1 ?>. <?= e(AXES[$a]) ?></span><?php endforeach ?><?php if (!$prioAxes): ?><span class="muted">À choisir</span><?php endif ?></div></div>
    <?php if ($reco['profiles']): ?><div><span class="ql">Profil repéré</span><div class="ra-chips"><?php foreach ($reco['profiles'] as $p): ?><span class="ra-chip"><?= e($p) ?></span><?php endforeach ?></div></div><?php endif ?>
  </div>
  <p class="muted an-foot"><small>Calculé le <?= e(fdate($an['computed_at'], 'd/m/Y à H:i')) ?> à partir des questionnaires. Indicatif : à confirmer par l'observation en séance.</small></p>
</div>
<div class="grid-2">
  <div class="card tone-violet">
    <div class="row between"><h2><?= icon('chart-column') ?> Besoins par axe</h2>
      <form method="post" action="<?= e(url('t_analyze')) ?>"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= $back ?>"><button class="btn ghost sm"><?= icon('refresh-cw') ?> Recalculer</button></form></div>
    <?php require __DIR__ . '/_axes.php' ?>
  </div>
  <div class="card tone-orange editable">
    <div class="ed-head"><h2><?= icon('target') ?> Priorités d'accompagnement</h2><button type="button" class="btn ghost sm" data-edit><?= icon('pencil') ?> Modifier</button></div>
    <div class="view">
      <ol class="an-prio"><?php foreach ($prioAxes as $a): ?><li><strong><?= e(AXES[$a]) ?></strong><?php if (!empty($prio['notes'][$a])): ?><small class="muted"><?= e($prio['notes'][$a]) ?></small><?php endif ?></li><?php endforeach ?></ol>
      <?php if (!$prioAxes): ?><p class="muted">Aucune priorité choisie.</p><?php endif ?>
      <p class="muted"><small><?= $prio['source'] === 'manual' ? 'Choisies par vous.' : 'Suggestion automatique : vous pouvez l\'ajuster.' ?></small></p>
    </div>
    <form method="post" action="<?= e(url('t_priorities')) ?>" class="stack edit-form"><?= csrf_field() ?>
      <input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= $back ?>">
      <p class="muted"><small>Suggestion : <?= e(implode(', ', array_map(fn($a) => AXES[$a], $an['auto_priorities'])) ?: '-') ?>. Choisissez 2 ou 3 axes, dans l'ordre d'importance.</small></p>
      <?php for ($i = 0; $i < 3; $i++): $cur = $prioAxes[$i] ?? ''; ?>
        <div class="form-grid">
          <label>Priorité <?= $i + 1 ?><select name="axes[]"><option value="">-</option>
            <?php foreach (AXES as $a => $l): ?><option value="<?= e($a) ?>" <?= $cur === $a ? 'selected' : '' ?>><?= e($l) ?> (<?= $an['scores'][$a]['score'] ?? 'n.d.' ?>)</option><?php endforeach ?></select></label>
          <label>Note<input name="note[]" value="<?= e($prio['notes'][$cur] ?? '') ?>" maxlength="500"></label>
        </div>
      <?php endfor ?>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button>
        <?php if ($prio['source'] === 'manual'): ?><button class="btn ghost sm" name="reset" value="1"><?= icon('refresh-cw') ?> Revenir à l'automatique</button><?php endif ?></div>
    </form>
  </div>
</div>

<h2 class="an-sec"><span class="qn-num">2</span> Ce que disent les réponses</h2>
<div class="grid an-three">
  <div class="card tone-green"><h3><?= icon('heart') ?> Points d'appui</h3><ul class="an-list"><?php foreach ($an['strengths'] ?: ['À découvrir en séance'] as $x): ?><li><?= e($x) ?></li><?php endforeach ?></ul></div>
  <div class="card tone-pink"><h3><?= icon('compass') ?> À explorer</h3><?php $exp = array_filter($an['explore'], fn($k) => !str_starts_with((string)$k, 'Écart de perception'), ARRAY_FILTER_USE_KEY); $expTop = array_slice($exp, 0, 6, true); $expMore = array_slice($exp, 6, null, true); ?>
    <ul class="an-list"><?php foreach ($expTop as $k => $x): ?><li><strong><?= e($k) ?></strong><small class="muted"><?= e($x) ?></small></li><?php endforeach ?><?php if (!$exp): ?><li class="muted">Rien de particulier.</li><?php endif ?></ul>
    <?php if ($expMore): ?><details class="an-more"><summary>Voir les <?= count($expMore) ?> autres</summary><ul class="an-list"><?php foreach ($expMore as $k => $x): ?><li><strong><?= e($k) ?></strong><small class="muted"><?= e($x) ?></small></li><?php endforeach ?></ul></details><?php endif ?></div>
  <div class="card tone-gold"><h3><?= icon('circle-help') ?> À demander en séance</h3>
    <?php if (!$an['missing'] && !$an['skipped']): ?><p class="muted">Rien à signaler.</p><?php endif ?>
    <ul class="an-list"><?php foreach ($an['missing'] as $m): ?><li><?= e($m['label']) ?> <small class="muted">· <?= $m['src'] === 'parent' ? 'parent' : 'élève' ?></small></li><?php endforeach ?>
    <?php foreach ($an['skipped'] as $m): ?><li><?= e($m) ?> <small class="muted">· non répondu volontairement</small></li><?php endforeach ?></ul></div>
</div>
<div class="card tone-teal"><h2><?= icon('git-compare') ?> Parent et élève : mêmes perceptions ?</h2>
  <?php if (!$an['divergences']): ?><p class="muted">Pas assez de réponses croisées.</p><?php endif ?>
  <?php if ($divs): ?><span class="ql">Points de désaccord (<?= count($divs) ?>) : à aborder ensemble</span>
  <div class="cmp-table"><div class="cmp-h"><span></span><span>Parent</span><span>Élève</span></div>
    <?php foreach ($divs as $d): ?><div class="cmp-r"><span><strong><?= e($d['label']) ?></strong><small class="muted"><?= e($d['note']) ?></small></span><span><?= e($d['parent']) ?></span><span><?= e($d['student']) ?></span></div><?php endforeach ?></div><?php endif ?>
  <?php if ($convs): ?><details class="an-conv"><summary>Points d'accord (<?= count($convs) ?>)</summary>
  <div class="cmp-table"><div class="cmp-h"><span></span><span>Parent</span><span>Élève</span></div>
    <?php foreach ($convs as $d): ?><div class="cmp-r"><span><strong><?= e($d['label']) ?></strong></span><span><?= e($d['parent']) ?></span><span><?= e($d['student']) ?></span></div><?php endforeach ?></div></details><?php endif ?>
</div>

<h2 class="an-sec"><span class="qn-num">3</span> Comment l'accompagner</h2>
<div class="card tone-violet"><h2><?= icon('lightbulb') ?> Pistes de travail</h2>
  <div class="an-tips">
    <div><span class="ql">Méthodes</span><div class="ra-chips"><?php foreach (array_unique($reco['methods']) as $x): ?><span class="ra-chip"><?= e($x) ?></span><?php endforeach ?></div></div>
    <div><span class="ql">Exercices</span><div class="ra-chips"><?php foreach (array_unique($reco['exercises']) as $x): ?><span class="ra-chip"><?= e($x) ?></span><?php endforeach ?></div></div>
    <div><span class="ql">Supports</span><div class="ra-chips"><?php foreach (array_unique($reco['supports']) as $x): ?><span class="ra-chip"><?= e($x) ?></span><?php endforeach ?></div></div>
  </div>
</div>
<div class="card tone-orange"><h2><?= icon('calendar') ?> Plan des 4 premières semaines</h2>
  <ol class="an-plan"><?php foreach ($reco['plan'] as $w => $p): ?><li><span class="an-wk">S<?= $w ?></span><div><strong><?= e($p['title']) ?></strong><ul><?php foreach ($p['items'] as $it): ?><li><?= e($it) ?></li><?php endforeach ?></ul></div></li><?php endforeach ?></ol>
</div>

<details class="card an-detail"><summary>Détail du calcul par axe (réponses d'origine)</summary><br>
  <?php foreach (AXES as $a => $l): $sc = $an['scores'][$a]; ?>
    <h3><?= e($l) ?> - <?= $sc['score'] ?? 'n.d.' ?> <small class="muted">(parent <?= $sc['parent'] ?? '-' ?> · élève <?= $sc['student'] ?? '-' ?><?= $sc['adjust'] ? ' · ajustement tutrice ' . ($sc['adjust'] > 0 ? '+' : '') . $sc['adjust'] : '' ?>)</small></h3>
    <div class="table-wrap"><table><tr><th>Élément</th><th>Source</th><th>Réponse</th><th>Besoin</th><th>Poids</th></tr>
    <?php foreach ($sc['contribs'] as $c): ?><tr><td><?= e($c['label']) ?></td><td><?= e(['parent' => 'Parent', 'student' => 'Élève', 'tutor' => 'Tutrice'][$c['src']]) ?></td><td><?= e($c['answer']) ?></td><td><?= $c['need'] === null ? '' : (int)round($c['need'] * 100) . '%' ?></td><td><?= e($c['weight'] ?? '') ?></td></tr><?php endforeach ?>
    </table></div><br>
  <?php endforeach ?>
</details>
<?php endif ?>
<h2 class="an-sec"><span class="qn-num"><?= $an ? 4 : 1 ?></span> Vos observations</h2>
<div class="card tone-pink"><div class="row between"><h2><?= icon('eye') ?> Observations de la tutrice</h2><button type="button" class="btn sm" data-toggle="newobs"><?= icon('plus') ?> Ajouter</button></div>
  <p class="muted"><small>Ce que vous constatez en séance : elles peuvent ajuster les scores ci-dessus.</small></p>
  <form id="newobs" hidden method="post" action="<?= e(url('t_observation')) ?>" class="stack"><?= csrf_field() ?>
    <input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= e($_GET['r'] ?? 't_student') ?>">
    <label>Observation<textarea name="content" required maxlength="3000"></textarea></label>
    <div class="form-grid">
      <label>Axe concerné<select name="axis"><option value="">- Aucun -</option><?php foreach (AXES as $a => $l): ?><option value="<?= e($a) ?>"><?= e($l) ?></option><?php endforeach ?></select></label>
      <label>Ajustement du score<select name="adjustment"><?php foreach ([-20, -10, 0, 10, 20] as $v): ?><option value="<?= $v ?>" <?= $v === 0 ? 'selected' : '' ?>><?= $v > 0 ? "+$v (besoin plus élevé)" : ($v < 0 ? "$v (besoin plus faible)" : '0') ?></option><?php endforeach ?></select></label>
      <label>Visibilité<select name="visibility"><option value="private">Privée (tutrice)</option><option value="shared">Partagée avec la famille</option></select></label>
    </div>
    <button class="btn"><?= icon('plus') ?> Ajouter l'observation</button>
  </form>
  <?php if (!$obs): ?><p class="muted">Aucune observation pour l'instant.</p><?php endif ?>
  <ul class="list obs-list"><?php foreach ($obs as $o): ?>
    <li><?= icon($o['visibility'] === 'shared' ? 'users' : 'lock') ?><div><?= nl2br(e($o['content'])) ?><br><small class="muted"><?= e(fdate($o['created_at'])) ?><?= $o['axis'] ? ' · ' . e(AXES[$o['axis']]) . ($o['adjustment'] ? ' (' . ($o['adjustment'] > 0 ? '+' : '') . $o['adjustment'] . ')' : '') : '' ?> · <?= $o['visibility'] === 'shared' ? 'partagée' : 'privée' ?></small></div>
      <form method="post" action="<?= e(url('t_observation')) ?>" data-confirm="Supprimer cette observation ?"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= e($_GET['r'] ?? 't_student') ?>"><input type="hidden" name="delete_id" value="<?= (int)$o['id'] ?>"><button class="btn ghost sm" title="Supprimer"><?= icon('trash-2') ?></button></form></li>
  <?php endforeach ?></ul>
</div>
