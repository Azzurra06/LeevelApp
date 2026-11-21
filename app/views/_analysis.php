<?php // Analyse détaillée (tutrice uniquement). $an, $prio, $reco, $s, $obs ?>
<?php if (!$an): ?>
  <div class="card"><p>Aucune analyse pour l'instant.</p>
  <form method="post" action="<?= e(url('t_analyze')) ?>"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= e($_GET['r'] ?? 't_student') ?>"><button class="btn"><?= icon('refresh-cw') ?> Calculer l'analyse</button></form></div>
<?php else: ?>
<div class="grid-2">
  <div class="card">
    <div class="row between"><h2><?= icon('chart-column') ?> Profil indicatif</h2>
      <form method="post" action="<?= e(url('t_analyze')) ?>"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= e($_GET['r'] ?? 't_student') ?>"><button class="btn ghost sm"><?= icon('refresh-cw') ?> Recalculer</button></form></div>
    <p class="muted"><small>Calculé le <?= e(fdate($an['computed_at'], 'd/m/Y à H:i')) ?></small></p>
    <?php require __DIR__ . '/_axes.php' ?>
  </div>
  <div class="card">
    <h2><?= icon('target') ?> Priorités d'accompagnement</h2>
    <p class="muted"><small>Suggestion automatique : <?= e(implode(', ', array_map(fn($a) => AXES[$a], $an['auto_priorities'])) ?: '-') ?>. Cochez 2 ou 3 axes, dans l'ordre d'importance.</small></p>
    <form method="post" action="<?= e(url('t_priorities')) ?>" class="stack"><?= csrf_field() ?>
      <input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= e($_GET['r'] ?? 't_student') ?>">
      <?php for ($i = 0; $i < 3; $i++): $cur = $prio['axes'][$i] ?? ''; ?>
        <div class="form-grid">
          <label>Priorité <?= $i + 1 ?><select name="axes[]"><option value="">-</option>
            <?php foreach (AXES as $a => $l): ?><option value="<?= e($a) ?>" <?= $cur === $a ? 'selected' : '' ?>><?= e($l) ?> (<?= $an['scores'][$a]['score'] ?? 'n.d.' ?>)</option><?php endforeach ?></select></label>
          <label>Note<input name="note[]" value="<?= e($prio['notes'][$cur] ?? '') ?>" maxlength="500"></label>
        </div>
      <?php endfor ?>
      <div class="row"><button class="btn"><?= icon('save') ?> Enregistrer</button>
        <?php if ($prio['source'] === 'manual'): ?><button class="btn ghost" name="reset" value="1"><?= icon('refresh-cw') ?> Revenir à l'automatique</button><?php endif ?></div>
    </form>
  </div>
</div><br>
<div class="card"><h2><?= icon('file-text') ?> Synthèse</h2><p><?= e($reco['summary']) ?></p>
  <?php if ($reco['profiles']): ?><p><?php foreach ($reco['profiles'] as $p): ?><span class="badge warn"><?= e($p) ?></span> <?php endforeach ?></p><?php endif ?></div><br>
<div class="grid">
  <div class="card"><h3><?= icon('heart') ?> Points forts</h3><ul class="dots" data-clamp><?php foreach ($an['strengths'] ?: ['À découvrir en séance'] as $x): ?><li><?= e($x) ?></li><?php endforeach ?></ul></div>
  <div class="card"><h3><?= icon('compass') ?> Difficultés à explorer</h3><ul class="dots" data-clamp><?php foreach ($an['explore'] as $k => $x): ?><li><strong><?= e($k) ?></strong> - <?= e($x) ?></li><?php endforeach ?><?php if (!$an['explore']): ?><li>-</li><?php endif ?></ul></div>
  <div class="card"><h3><?= icon('triangle-alert') ?> Informations manquantes</h3>
    <?php if (!$an['missing'] && !$an['skipped']): ?><p class="muted">Rien à signaler.</p><?php endif ?>
    <ul class="dots" data-clamp><?php foreach ($an['missing'] as $m): ?><li><?= e($m['label']) ?> <small class="muted">(<?= $m['src'] === 'parent' ? 'parent' : 'élève' ?>)</small></li><?php endforeach ?>
    <?php foreach ($an['skipped'] as $m): ?><li><?= e($m) ?> <small class="muted">(non répondu volontairement)</small></li><?php endforeach ?></ul></div>
</div><br>
<div class="card"><h2><?= icon('git-compare') ?> Convergences et divergences parent / élève</h2>
  <?php if (!$an['divergences']): ?><p class="muted">Pas assez de réponses croisées.</p><?php endif ?>
  <div class="stack" data-clamp="320"><?php foreach ($an['divergences'] as $d): ?>
    <div><div class="row"><strong><?= e($d['label']) ?></strong> <span class="badge <?= $d['type'] === 'divergence' ? 'warn' : 'sage' ?>"><?= $d['type'] === 'divergence' ? 'Divergence' : 'Convergence' ?></span> <small class="muted"><?= e($d['note']) ?></small></div>
      <div class="cmp"><div><small class="muted">Parent</small><br><?= e($d['parent']) ?></div><div><small class="muted">Élève</small><br><?= e($d['student']) ?></div></div></div>
  <?php endforeach ?></div>
</div><br>
<div class="grid">
  <div class="card"><h3><?= icon('lightbulb') ?> Méthodes recommandées</h3><ul class="dots" data-clamp><?php foreach (array_unique($reco['methods']) as $x): ?><li><?= e($x) ?></li><?php endforeach ?></ul></div>
  <div class="card"><h3><?= icon('pencil') ?> Types d'exercices</h3><ul class="dots" data-clamp><?php foreach (array_unique($reco['exercises']) as $x): ?><li><?= e($x) ?></li><?php endforeach ?></ul></div>
  <div class="card"><h3><?= icon('book-open') ?> Supports de progression</h3><ul class="dots" data-clamp><?php foreach (array_unique($reco['supports']) as $x): ?><li><?= e($x) ?></li><?php endforeach ?></ul></div>
</div><br>
<div class="card"><h2><?= icon('calendar') ?> Plan d'action initial - 4 semaines</h2>
  <div class="grid"><?php foreach ($reco['plan'] as $w => $p): ?><div class="card flat"><h3>Semaine <?= $w ?> · <?= e($p['title']) ?></h3><ul class="dots"><?php foreach ($p['items'] as $it): ?><li><?= e($it) ?></li><?php endforeach ?></ul></div><?php endforeach ?></div>
</div><br>
<details class="card"><summary>Détail des contributions par axe (réponses d'origine)</summary><br>
  <?php foreach (AXES as $a => $l): $sc = $an['scores'][$a]; ?>
    <h3><?= e($l) ?> - <?= $sc['score'] ?? 'n.d.' ?> <small class="muted">(parent <?= $sc['parent'] ?? '-' ?> · élève <?= $sc['student'] ?? '-' ?><?= $sc['adjust'] ? ' · ajustement tutrice ' . ($sc['adjust'] > 0 ? '+' : '') . $sc['adjust'] : '' ?>)</small></h3>
    <div class="table-wrap"><table><tr><th>Élément</th><th>Source</th><th>Réponse</th><th>Besoin</th><th>Poids</th></tr>
    <?php foreach ($sc['contribs'] as $c): ?><tr><td><?= e($c['label']) ?></td><td><?= e(['parent' => 'Parent', 'student' => 'Élève', 'tutor' => 'Tutrice'][$c['src']]) ?></td><td><?= e($c['answer']) ?></td><td><?= $c['need'] === null ? '' : (int)round($c['need'] * 100) . '%' ?></td><td><?= e($c['weight'] ?? '') ?></td></tr><?php endforeach ?>
    </table></div><br>
  <?php endforeach ?>
</details><br>
<?php endif ?>
<div class="card"><h2><?= icon('eye') ?> Observations de la tutrice</h2>
  <form method="post" action="<?= e(url('t_observation')) ?>" class="stack"><?= csrf_field() ?>
    <input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= e($_GET['r'] ?? 't_student') ?>">
    <label>Observation<textarea name="content" required maxlength="3000"></textarea></label>
    <div class="form-grid">
      <label>Axe concerné<select name="axis"><option value="">- Aucun -</option><?php foreach (AXES as $a => $l): ?><option value="<?= e($a) ?>"><?= e($l) ?></option><?php endforeach ?></select></label>
      <label>Ajustement du score<select name="adjustment"><?php foreach ([-20, -10, 0, 10, 20] as $v): ?><option value="<?= $v ?>" <?= $v === 0 ? 'selected' : '' ?>><?= $v > 0 ? "+$v (besoin plus élevé)" : ($v < 0 ? "$v (besoin plus faible)" : '0') ?></option><?php endforeach ?></select></label>
      <label>Visibilité<select name="visibility"><option value="private">Privée (tutrice)</option><option value="shared">Partagée avec la famille</option></select></label>
    </div>
    <button class="btn"><?= icon('plus') ?> Ajouter l'observation</button>
  </form>
  <?php if ($obs): ?><p class="axis-sep obs-sep">Observations enregistrées (<?= count($obs) ?>)</p><?php endif ?>
  <ul class="list obs-list"><?php foreach ($obs as $o): ?>
    <li><?= icon($o['visibility'] === 'shared' ? 'users' : 'lock') ?><div><?= nl2br(e($o['content'])) ?><br><small class="muted"><?= e(fdate($o['created_at'])) ?><?= $o['axis'] ? ' · ' . e(AXES[$o['axis']]) . ($o['adjustment'] ? ' (' . ($o['adjustment'] > 0 ? '+' : '') . $o['adjustment'] . ')' : '') : '' ?> · <?= $o['visibility'] === 'shared' ? 'partagée' : 'privée' ?></small></div>
      <form method="post" action="<?= e(url('t_observation')) ?>" data-confirm="Supprimer cette observation ?"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="<?= e($_GET['r'] ?? 't_student') ?>"><input type="hidden" name="delete_id" value="<?= (int)$o['id'] ?>"><button class="btn ghost sm" title="Supprimer"><?= icon('trash-2') ?></button></form></li>
  <?php endforeach ?></ul>
</div>
