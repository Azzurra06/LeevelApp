<?php $title = 'Mon enfant';
$needsList = ['dys' => 'Trouble « dys » (dyslexie, dyspraxie, dyscalculie…)', 'tdah' => 'TDAH / trouble de l\'attention', 'hpi' => 'Haut potentiel (HPI)', 'anxiety' => 'Anxiété scolaire', 'other' => 'Autre'];
$accList = ['pap' => 'PAP', 'pps' => 'PPS', 'pai' => 'PAI', 'tiers_temps' => 'Tiers-temps'];
$needs = json_decode((string)($s['needs_json'] ?? ''), true) ?: ['items' => [], 'acc' => []];
$chUser = $s['user_id'] ? one('SELECT username FROM users WHERE id=?', [$s['user_id']]) : null;
$val = fn($v) => ($v ?? '') !== '' ? e($v) : '<span class="muted">Non renseigné</span>';
$editBtn = '<button type="button" class="btn ghost sm" data-edit>' . icon('pencil') . ' Modifier</button>';
$age = !empty($s['birth_date']) ? (new DateTime($s['birth_date']))->diff(new DateTime('today'))->y : null;
$h = fn(string $action) => csrf_field() . '<input type="hidden" name="sid" value="' . (int)$s['id'] . '"><input type="hidden" name="action" value="' . $action . '">';
$perms = ['perm_edit_contacts' => 'Modifier lui-même ses coordonnées', 'perm_edit_photo' => 'Changer sa photo de profil', 'perm_help_requests' => 'Signaler qu\'il bloque et demander de l\'aide']; ?>
<?php $tab ??= 'profil'; $sidq = ['sid' => $s['id']];
$childTabs = ['profil' => 'Profil', 'matieres' => 'Matières', 'progression' => 'Progression', 'bilans' => 'Bilans']; ?>
<div class="page-head"><div><h1><?= e($s['first_name']) ?><?= !empty($s['last_name']) ? ' ' . e($s['last_name']) : '' ?></h1><p><?= e(CLASSES[$s['grade']] ?? $s['grade']) ?><?= $s['school'] ? ' · ' . e($s['school']) : '' ?></p></div></div>
<nav class="tabs" aria-label="Mon enfant"><?php foreach ($childTabs as $k => $l): ?><a href="<?= e(url('p_child', $sidq + ['tab' => $k])) ?>" class="<?= $tab === $k ? 'on' : '' ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach ?></nav>
<?php if ($tab !== 'profil') { require __DIR__ . '/_p_child_' . $tab . '.php'; return; } ?>
<p class="muted child-intro">Les informations de <?= e($s['first_name']) ?>, visibles par la tutrice. Vous pouvez les compléter à tout moment.</p>

<div class="grid-2">
  <div class="card editable tone-violet">
    <div class="ed-head"><h2><?= icon('user') ?> Identité et scolarité</h2><?= $editBtn ?></div>
    <dl class="fields view">
      <div><dt>Prénom</dt><dd><?= $val($s['first_name']) ?></dd></div>
      <div><dt>Nom</dt><dd><?= $val($s['last_name'] ?? null) ?></dd></div>
      <div><dt>Date de naissance</dt><dd><?= !empty($s['birth_date']) ? e(fdate($s['birth_date'])) . ' <span class="muted">(' . $age . ' ans)</span>' : $val(null) ?></dd></div>
      <div><dt>Classe</dt><dd><?= $val(CLASSES[$s['grade']] ?? $s['grade']) ?></dd></div>
      <div class="wide"><dt>Établissement</dt><dd><?= $val($s['school']) ?></dd></div>
    </dl>
    <form method="post" class="stack edit-form"><?= $h('identity') ?>
      <div class="form-grid">
        <label>Prénom<input name="first_name" required maxlength="80" value="<?= e($s['first_name']) ?>"></label>
        <label>Nom<input name="last_name" maxlength="80" value="<?= e($s['last_name'] ?? '') ?>"></label>
        <label>Date de naissance<input type="date" name="birth_date" value="<?= e($s['birth_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></label>
        <label>Classe<?= grade_select('grade', $s['grade']) ?></label>
      </div>
      <label>Établissement<input name="school" maxlength="120" value="<?= e($s['school'] ?? '') ?>" placeholder="ex. Collège Jean Moulin"></label>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
    </form>
  </div>

  <div class="card editable tone-blue">
    <div class="ed-head"><h2><?= icon('message-circle') ?> Coordonnées de <?= e($s['first_name']) ?></h2><?= $editBtn ?></div>
    <dl class="fields view">
      <div><dt>Téléphone</dt><dd><?= $val($s['student_phone'] ?? null) ?></dd></div>
      <div><dt>E-mail</dt><dd><?= $val($s['student_email'] ?? null) ?></dd></div>
      <div class="wide"><dt>Compte leevel.app</dt><dd><?= $chUser ? '<code>' . e($chUser['username']) . '</code>' : '<span class="muted">Pas encore créé : demandez-le à la tutrice</span>' ?></dd></div>
    </dl>
    <form method="post" class="stack edit-form"><?= $h('contacts') ?>
      <div class="form-grid">
        <label>Téléphone<input type="tel" name="phone" maxlength="30" value="<?= e($s['student_phone'] ?? '') ?>"></label>
        <label>E-mail<input type="email" name="email" maxlength="120" value="<?= e($s['student_email'] ?? '') ?>"></label>
      </div>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
    </form>
  </div>

  <div class="card editable tone-pink">
    <div class="ed-head"><h2><?= icon('heart') ?> Besoins particuliers</h2><?= $editBtn ?></div>
    <div class="view">
      <?php if (!$needs['items'] && !$needs['acc'] && empty($s['accommodations']) && empty($s['parent_notes'])): ?><p class="muted">Rien de signalé. Facultatif : ces informations aident la tutrice à adapter les séances.</p><?php endif ?>
      <?php if ($needs['items'] || $needs['acc']): ?><div class="ra-chips child-chips"><?php foreach ($needs['items'] as $k): ?><span class="ra-chip"><?= e($needsList[$k] ?? $k) ?></span><?php endforeach ?><?php foreach ($needs['acc'] as $k): ?><span class="ra-chip prio"><?= e($accList[$k] ?? $k) ?></span><?php endforeach ?></div><?php endif ?>
      <?php if (!empty($s['accommodations'])): ?><p class="child-note"><strong>Aménagements :</strong> <?= e($s['accommodations']) ?></p><?php endif ?>
      <?php if (!empty($s['parent_notes'])): ?><p class="child-note"><strong>À savoir :</strong> <?= nl2br(e($s['parent_notes'])) ?></p><?php endif ?>
    </div>
    <form method="post" class="stack edit-form"><?= $h('needs') ?>
      <div><span class="ql">Difficultés ou particularités</span><div class="pills"><?php foreach ($needsList as $k => $l): ?><label class="pill"><input type="checkbox" name="needs[]" value="<?= $k ?>" <?= in_array($k, $needs['items'], true) ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div></div>
      <div><span class="ql">Dispositifs en place à l'école</span><div class="pills"><?php foreach ($accList as $k => $l): ?><label class="pill"><input type="checkbox" name="acc[]" value="<?= $k ?>" <?= in_array($k, $needs['acc'], true) ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div></div>
      <label>Aménagements utiles<input name="accommodations" maxlength="500" value="<?= e($s['accommodations'] ?? '') ?>" placeholder="ex. police agrandie, consignes lues à voix haute, pauses fréquentes"></label>
      <label>Autre chose à savoir ?<textarea name="parent_notes" maxlength="2000" rows="3" placeholder="ex. traitement, fatigue en fin de journée, activités extrascolaires…"><?= e($s['parent_notes'] ?? '') ?></textarea></label>
      <p class="field-help">Ces informations restent confidentielles entre vous et la tutrice.</p>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
    </form>
  </div>

  <div class="card editable tone-green">
    <div class="ed-head"><h2><?= icon('shield') ?> Autorisations</h2><?= $editBtn ?></div>
    <p class="muted view"><small>Ce que <?= e($s['first_name']) ?> peut faire seul dans son espace.</small></p>
    <ul class="list view"><?php foreach ($perms as $k => $l): $on = (int)($s[$k] ?? 1); ?><li><?= icon($on ? 'circle-check' : 'x') ?><div><?= e($l) ?> <span class="badge <?= $on ? 'ok' : '' ?>"><?= $on ? 'Autorisé' : 'Non autorisé' ?></span></div></li><?php endforeach ?></ul>
    <form method="post" class="stack edit-form"><?= $h('perms') ?>
      <?php foreach ($perms as $k => $l): ?><label class="check"><input type="checkbox" name="<?= $k ?>" value="1" <?= (int)($s[$k] ?? 1) ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach ?>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
    </form>
  </div>
</div>
