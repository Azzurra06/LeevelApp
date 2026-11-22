<?php // Fiche élève > Matières > Compétences transversales (tutrice). $s, $skills, $an
$hk = fn(array $extra = []) => csrf_field() . '<input type="hidden" name="sid" value="' . (int)$s['id'] . '">' . implode('', array_map(fn($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string)$v) . '">', array_keys($extra), $extra));
$kact = e(url('t_skills'));
$haveKeys = array_filter(array_column($skills, 'skill_key'));
$lvlSelect = fn(string $name, ?int $cur, string $empty) => '<select name="' . $name . '"><option value="">' . e($empty) . '</option>' . implode('', array_map(fn($k, $l) => '<option value="' . $k . '"' . ($cur === $k ? ' selected' : '') . '>' . $k . ' · ' . e($l) . '</option>', array_keys(SKILL_LEVELS), SKILL_LEVELS)) . '</select>'; ?>
<?php if (!skills_available()): ?><p class="alert warn">La base de données doit être mise à jour (migration 026) pour activer les compétences.</p><?php return; endif ?>
<div class="card" id="competences">
  <div class="row between"><h2><?= icon('sparkles') ?> Compétences travaillées</h2><span class="muted"><small>Méthodologie, organisation, confiance en soi… Visibles par l'élève et le parent.</small></span></div>
  <form method="post" action="<?= $kact ?>" class="row subj-add"><?= $hk(['action' => 'skill_add']) ?>
    <select name="skill_key" aria-label="Compétence"><option value="">Choisir une compétence…</option><?php foreach (SKILLS as $k => [$l, $ax, $emo]): if (in_array($k, $haveKeys, true)) continue; ?><option value="<?= $k ?>"><?= $emo ?> <?= e($l) ?></option><?php endforeach ?></select>
    <input name="custom" maxlength="80" placeholder="ou une autre compétence" aria-label="Autre compétence">
    <button class="btn sm"><?= icon('plus') ?> Ajouter</button>
  </form>
  <p class="muted"><small>Le niveau de départ est estimé d'après l'analyse des questionnaires ; vous pouvez l'ajuster.</small></p>
</div>
<?php if (!$skills): ?><div class="card"><div class="empty-ill sm"><span>🧭</span><p class="muted">Ajoutez une compétence à travailler (organisation, méthodologie, confiance en soi…) pour suivre sa progression, les outils mis en place et les défis.</p></div></div>
<?php else: ?>
<nav class="subj-tabs" aria-label="Compétences" data-subj-tabs>
  <?php foreach ($skills as $i => $sk): ?><button type="button" class="subj-tab<?= $i === 0 ? ' on' : '' ?>" data-subj-tab="skill-<?= (int)$sk['id'] ?>"><?= $sk['emoji'] ?> <?= e($sk['name']) ?><?php if ($sk['level']): ?> <small><?= (int)$sk['level'] ?>/5</small><?php endif ?></button><?php endforeach ?>
</nav>
<div class="subj-panes">
<?php foreach ($skills as $i => $sk): $sugg = skill_practice_suggestions($sk); $done = count(array_filter($sk['practices'], fn($p) => $p['done'])); ?>
  <div class="card subj-pane" id="skill-<?= (int)$sk['id'] ?>" data-subj-pane <?= $i === 0 ? '' : 'hidden' ?>>
    <div class="row between"><h2><span class="sk-emo"><?= $sk['emoji'] ?></span> <?= e($sk['name']) ?></h2>
      <form method="post" action="<?= $kact ?>" data-confirm="Retirer cette compétence et ses outils ?"><?= $hk(['action' => 'skill_del', 'skill_id' => $sk['id']]) ?><button class="edit-btn" title="Retirer la compétence" aria-label="Retirer la compétence"><?= icon('trash-2') ?></button></form></div>

    <div class="sk-grid">
      <section class="sk-block editable">
        <div class="ed-head"><h3>Niveau</h3><button type="button" class="btn ghost sm" data-edit><?= icon('pencil') ?> Évaluer</button></div>
        <div class="view"><div class="sk-level-row"><?= skill_level_html($sk) ?></div>
          <?php if ($sk['start_level']): ?><p class="muted"><small>Niveau de départ : <?= (int)$sk['start_level'] ?>/5 · <?= e(SKILL_LEVELS[(int)$sk['start_level']]) ?></small></p><?php endif ?>
          <?php if ($sk['note']): ?><p class="sk-note"><?= nl2br(e($sk['note'])) ?></p><?php endif ?></div>
        <form method="post" action="<?= $kact ?>" class="stack edit-form"><?= $hk(['action' => 'skill_level', 'skill_id' => $sk['id']]) ?>
          <div class="form-grid"><label>Niveau de départ<?= $lvlSelect('start_level', $sk['start_level'] !== null ? (int)$sk['start_level'] : null, 'Non évalué') ?></label>
            <label>Niveau actuel<?= $lvlSelect('level', $sk['level'] !== null ? (int)$sk['level'] : null, 'Non évalué') ?></label></div>
          <label>Commentaire <small class="muted">visible par la famille</small><textarea name="note" rows="2" maxlength="2000" placeholder="ex. Utilise maintenant son agenda sans rappel."><?= e($sk['note'] ?? '') ?></textarea></label>
          <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
        </form>
      </section>

      <section class="sk-block">
        <h3>Outils et méthodes <span class="muted">· <?= $done ?>/<?= count($sk['practices']) ?> en place</span></h3>
        <?php if (!$sk['practices']): ?><p class="muted"><small>Aucun outil pour l'instant.</small></p><?php endif ?>
        <ul class="sk-practices"><?php foreach ($sk['practices'] as $p): ?>
          <li class="<?= $p['done'] ? 'done' : '' ?>">
            <form method="post" action="<?= $kact ?>"><?= $hk(['action' => 'practice_toggle', 'skill_id' => $sk['id'], 'practice_id' => $p['id']]) ?><button class="sk-check" title="<?= $p['done'] ? 'Marquer à mettre en place' : 'Marquer en place' ?>"><?= $p['done'] ? icon('circle-check') : '<span class="sk-box"></span>' ?></button></form>
            <span><?= e($p['label']) ?><?php if ($p['done']): ?> <small class="muted">· en place</small><?php endif ?></span>
            <form method="post" action="<?= $kact ?>"><?= $hk(['action' => 'practice_del', 'skill_id' => $sk['id'], 'practice_id' => $p['id']]) ?><button class="edit-btn" title="Retirer" aria-label="Retirer"><?= icon('x') ?></button></form>
          </li><?php endforeach ?></ul>
        <form method="post" action="<?= $kact ?>" class="copy-row sk-add"><?= $hk(['action' => 'practice_add', 'skill_id' => $sk['id']]) ?><input name="label" required maxlength="160" placeholder="ex. Planning hebdomadaire" aria-label="Nouvel outil"><button class="btn sm"><?= icon('plus') ?></button></form>
        <?php if ($sugg): ?><div class="muted sk-sugg"><small>Suggestions :</small> <?php foreach (array_slice($sugg, 0, 6) as $x): ?><form method="post" action="<?= $kact ?>" class="inline"><?= $hk(['action' => 'practice_add', 'skill_id' => $sk['id'], 'label' => $x]) ?><button class="pill-btn sm-legend"><?= icon('plus') ?> <?= e($x) ?></button></form><?php endforeach ?></div><?php endif ?>
      </section>

      <section class="sk-block">
        <h3>Objectifs et défis</h3>
        <?php if (!$sk['goals']): ?><p class="muted"><small>Aucun objectif lié pour l'instant.</small></p><?php endif ?>
        <ul class="list sk-goals"><?php foreach (array_slice($sk['goals'], 0, 6) as $g): ?><li><?= icon($g['is_mission'] ? 'rocket' : 'target') ?><div><?= e($g['title']) ?> <span class="badges"><?php require __DIR__ . '/_goal_status.php' ?><?php if ($g['is_mission']): ?><span class="badge">Défi</span><?php endif ?></span></div></li><?php endforeach ?></ul>
        <details class="sk-defi"><summary><?= icon('plus') ?> Proposer un défi à <?= e($s['first_name']) ?></summary>
          <form method="post" action="<?= $kact ?>" class="stack"><?= $hk(['action' => 'skill_defi', 'skill_id' => $sk['id']]) ?>
            <label>Défi<input name="title" required maxlength="200" placeholder="ex. Préparer son sac la veille toute la semaine"></label>
            <label>Précisions <small class="muted">facultatif</small><input name="description" maxlength="1000"></label>
            <div><button class="btn sm"><?= icon('rocket') ?> Ajouter le défi</button></div>
            <p class="muted"><small>Le défi apparaît dans « Mes missions » de l'élève.</small></p></form></details>
      </section>

      <section class="sk-block">
        <h3>Observations</h3>
        <?php if (!$sk['obs']): ?><p class="muted"><small>Aucune observation sur cette compétence.</small></p><?php endif ?>
        <ul class="list obs-list"><?php foreach (array_slice($sk['obs'], 0, 5) as $o): ?><li><?= icon($o['visibility'] === 'shared' ? 'users' : 'lock') ?><div><?= nl2br(e($o['content'])) ?><br><small class="muted"><?= e(fdate($o['created_at'])) ?> · <?= $o['visibility'] === 'shared' ? 'partagée' : 'privée' ?></small></div></li><?php endforeach ?></ul>
        <form method="post" action="<?= $kact ?>" class="stack sk-obs"><?= $hk(['action' => 'skill_obs', 'skill_id' => $sk['id']]) ?>
          <textarea name="content" required rows="2" maxlength="3000" placeholder="Ce que vous avez observé en séance…" aria-label="Observation"></textarea>
          <div class="row"><select name="visibility" aria-label="Visibilité"><option value="private">Privée</option><option value="shared">Partagée avec la famille</option></select><button class="btn sm"><?= icon('plus') ?> Ajouter</button></div></form>
      </section>
    </div>
  </div>
<?php endforeach ?>
</div>
<?php endif ?>
