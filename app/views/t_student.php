<?php $title = 'Fiche - ' . $s['first_name']; $sidp = ['sid' => $s['id']];
$tabs = ['profil' => 'Profil', 'parent' => 'Questionnaire parent', 'eleve' => 'Questionnaire élève', 'analyse' => 'Analyse pédagogique', 'objectifs' => 'Objectifs', 'matieres' => 'Matières', 'seances' => 'Séances', 'bilans' => 'Bilans', 'progression' => 'Progression'];
if (!isset($tabs[$tab])) $tab = 'profil';
$hidden = fn() => csrf_field() . '<input type="hidden" name="sid" value="' . (int)$s['id'] . '">';
$catOpts = fn($cur) => implode('', array_map(fn($a, $l) => '<option value="' . e($a) . '"' . ($cur === $a ? ' selected' : '') . '>' . e($l) . '</option>', array_keys(AXES), AXES)); ?>
<?php if (in_array($tab, ['seances', 'matieres'], true)): ?>
<div class="page-head"><div><h1><?= icon($tab === 'seances' ? 'calendar' : 'book-open') ?> <?= $tab === 'seances' ? 'Séances' : 'Matières' ?></h1><p><?= e($s['first_name']) ?> · <?= e($s['grade']) ?><?= $s['school'] ? ' · ' . e($s['school']) : '' ?></p></div></div>
<?php else: $ficheTab = $tab; require __DIR__ . '/_fiche_head.php'; endif ?>

<?php if ($tab === 'profil'): ?>
<?php
$parentU = array_values(array_filter($users, fn($x) => $x['role'] === 'parent'))[0] ?? null;
$studentU = array_values(array_filter($users, fn($x) => $x['role'] === 'student'))[0] ?? null;
$val = fn($v) => ($v ?? '') !== '' ? e($v) : '<span class="muted">Non renseigné</span>';
$editBtn = '<button type="button" class="btn ghost sm" data-edit>' . icon('pencil') . ' Modifier</button>';
$tel = fn($v) => !empty($v) ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $v)) . '">' . e($v) . '</a>' : $val(null);
$mail = fn($v) => !empty($v) ? '<a href="mailto:' . e($v) . '">' . e($v) . '</a>' : $val(null);
$age = !empty($s['birth_date']) ? (new DateTime($s['birth_date']))->diff(new DateTime('today'))->y : null;
$nd = json_decode((string)($s['needs_json'] ?? ''), true) ?: ['items' => [], 'acc' => []];
$ndL = ['dys' => 'Trouble « dys »', 'tdah' => 'TDAH / attention', 'hpi' => 'Haut potentiel', 'anxiety' => 'Anxiété scolaire', 'other' => 'Autre'];
$acL = ['pap' => 'PAP', 'pps' => 'PPS', 'pai' => 'PAI', 'tiers_temps' => 'Tiers-temps'];
$hasNeeds = $nd['items'] || $nd['acc'] || !empty($s['accommodations']) || !empty($s['parent_notes']);
$nextSe = one('SELECT starts_at, mode FROM sessions WHERE student_id=? AND status="planned" AND starts_at >= NOW() ORDER BY starts_at LIMIT 1', [$s['id']]);
$doneN = (int)(one('SELECT COUNT(*) n FROM sessions WHERE student_id=? AND (status="done" OR (status="planned" AND starts_at < NOW()))', [$s['id']])['n'] ?? 0);
$sched = schedule_available() ? schedule_of($s) : null;
$hasShared = $s['shared_message'] || $s['shared_strengths'] || $s['shared_to_work']; ?>

<div class="card"><h2><?= icon('chart-column') ?> En bref</h2><div class="glance month-stats">
  <?php $lastRep = one('SELECT s.starts_at, r.sent_at FROM sessions s LEFT JOIN session_reports r ON r.session_id=s.id WHERE s.student_id=? AND s.status IN ("done","planned") AND s.starts_at < NOW() ORDER BY s.starts_at DESC LIMIT 1', [$s['id']]); ?>
  <div><small>Dernière séance</small><strong><?= $lastRep ? e(fdate($lastRep['starts_at'])) : '—' ?></strong><?php if ($lastRep): ?><a href="<?= e(url('t_student', $sidp + ['tab' => 'seances'])) ?>"><?= $lastRep['sent_at'] ? 'Bilan envoyé' : 'Bilan à envoyer' ?></a><?php endif ?></div>
  <div><small>Rythme</small><strong><?= $sched ? fdur(schedule_minutes_per_week($sched)) . ' / sem.' : '—' ?></strong><a href="<?= e(url('t_student', $sidp + ['tab' => 'seances'])) ?>#rythme"><?= $sched ? 'Voir' : 'À définir' ?></a></div>
  <div><small>Prochaine séance</small><strong><?= $nextSe ? e(fdatetime($nextSe['starts_at'])) : '—' ?></strong><?php if ($nextSe): ?><span class="muted"><?= e(MODES[$nextSe['mode']]) ?></span><?php endif ?></div>
  <div><small>Séances faites</small><strong><?= $doneN ?></strong><a href="<?= e(url('t_student', $sidp + ['tab' => 'seances'])) ?>">Planning</a></div>
</div>
</div>

<div class="grid-2 two-cols">
 <div class="col-stack">
  <div class="card tone-violet">
    <h2><?= icon('smile') ?> <?= e($s['first_name'] . (!empty($s['last_name']) ? ' ' . $s['last_name'] : '')) ?></h2>
    <div class="editable">
      <div class="ed-head"><h3>Identité et scolarité</h3><?= $editBtn ?></div>
      <dl class="fields view">
        <div><dt>Prénom</dt><dd><?= $val($s['first_name']) ?></dd></div>
        <div><dt>Nom</dt><dd><?= $val($s['last_name'] ?? null) ?></dd></div>
        <div><dt>Date de naissance</dt><dd><?= !empty($s['birth_date']) ? e(fdate($s['birth_date'])) . ' <span class="muted">(' . $age . ' ans)</span>' : $val(null) ?></dd></div>
        <div><dt>Classe</dt><dd><?= $val(CLASSES[$s['grade']] ?? $s['grade']) ?></dd></div>
        <div class="wide"><dt>Établissement</dt><dd><?= $val($s['school']) ?></dd></div>
      </dl>
      <form method="post" action="<?= e(url('t_profile')) ?>" class="stack edit-form"><?= $hidden() ?><input type="hidden" name="back" value="t_student">
        <div class="form-grid"><label>Prénom<input name="first_name" value="<?= e($s['first_name']) ?>" maxlength="80" required></label><label>Nom<input name="last_name" value="<?= e($s['last_name'] ?? '') ?>" maxlength="80"></label>
        <label>Date de naissance<input type="date" name="birth_date" value="<?= e($s['birth_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></label><label>Classe<?= grade_select('grade', $s['grade']) ?></label></div>
        <label>Établissement<input name="school" value="<?= e($s['school']) ?>" maxlength="120"></label>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div></form>
    </div>
    <div class="editable ed-sep">
      <div class="ed-head"><h3>Coordonnées de l'élève</h3><?= $editBtn ?></div>
      <dl class="fields view"><div><dt>Téléphone</dt><dd><?= $tel($s['student_phone'] ?? null) ?></dd></div><div><dt>E-mail</dt><dd><?= $mail($s['student_email'] ?? null) ?></dd></div>
        <div class="wide"><dt>Compte leevel.app</dt><dd><?= $studentU ? '<code>' . e($studentU['username']) . '</code>' : '<span class="muted">Pas de compte élève</span> · <a href="' . e(url('t_users')) . '">Créer</a>' ?></dd></div></dl>
      <form method="post" action="<?= e(url('t_contacts')) ?>" class="stack edit-form"><?= $hidden() ?><input type="hidden" name="back" value="t_student"><input type="hidden" name="who" value="student">
        <div class="form-grid"><label>Téléphone<input type="tel" name="student_phone" value="<?= e($s['student_phone'] ?? '') ?>" maxlength="30" autocomplete="off"></label>
        <label>E-mail<input type="email" name="student_email" value="<?= e($s['student_email'] ?? '') ?>" maxlength="120" autocomplete="off"></label></div>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div></form>
    </div>
  </div>

  <div class="card tone-pink"><h2><?= icon('heart') ?> Besoins particuliers</h2>
    <?php if (!$hasNeeds): ?><p class="muted">Rien de signalé par le parent pour l'instant (il peut le renseigner dans « Mon enfant »).</p><?php else: ?>
    <?php if ($nd['items'] || $nd['acc']): ?><div class="ra-chips child-chips"><?php foreach ($nd['items'] as $k): ?><span class="ra-chip"><?= e($ndL[$k] ?? $k) ?></span><?php endforeach ?><?php foreach ($nd['acc'] as $k): ?><span class="ra-chip prio"><?= e($acL[$k] ?? $k) ?></span><?php endforeach ?></div><?php endif ?>
    <?php if (!empty($s['accommodations'])): ?><p class="child-note"><strong>Aménagements :</strong> <?= e($s['accommodations']) ?></p><?php endif ?>
    <?php if (!empty($s['parent_notes'])): ?><p class="child-note"><strong>À savoir :</strong> <?= nl2br(e($s['parent_notes'])) ?></p><?php endif ?>
    <p class="muted"><small>Renseigné par le parent.</small></p>
    <?php endif ?>
  </div>

  <?php $mem = (array)($data['student']['all']['s_memory'] ?? []);
  $memInfo = ['visual' => ['👀', 'Visuelle', 'Schémas, couleurs, cartes mentales, fiches illustrées.'], 'auditory' => ['👂', 'Auditive', 'Expliquer à voix haute, s\'enregistrer, réciter.'], 'kinesthetic' => ['✋', 'En faisant', 'Écrire, refaire des exercices, manipuler, bouger.']]; ?>
  <div class="card tone-violet"><h2><?= icon('brain') ?> Comment <?= e($s['first_name']) ?> retient</h2>
    <div class="mem-grid"><?php foreach ($memInfo as $k => [$emo, $lb, $tip]): $on = in_array($k, $mem, true); ?>
      <div class="mem<?= $on ? ' on' : '' ?>"><span class="mem-emo"><?= $emo ?></span><strong><?= $lb ?></strong><small><?= e($tip) ?></small><?php if ($on): ?><span class="badge">Sa préférence</span><?php endif ?></div>
    <?php endforeach ?></div>
    <p class="muted"><small><?= !$mem ? 'Pas encore renseigné dans le questionnaire élève.' : (in_array('not_sure', $mem, true) ? 'L\'élève ne sait pas encore : à observer en séance.' : 'D\'après le questionnaire élève (modifiable par l\'élève dans son compte).') ?></small></p>
  </div>

  <div class="card tone-blue editable">
    <div class="ed-head"><h2><?= icon('users') ?> Famille</h2><?= $editBtn ?></div>
    <p class="fam-name view"><strong><?= $parentU ? e($parentU['display_name']) : 'Parent' ?></strong><?php if ($parentU): ?> · <code><?= e($parentU['username']) ?></code><?php else: ?> · <span class="muted">pas de compte parent</span><?php endif ?></p>
    <dl class="fields view">
      <div><dt>Téléphone</dt><dd><?= $tel($s['parent_phone'] ?? null) ?></dd></div>
      <div><dt>E-mail</dt><dd><?= $mail($s['parent_email'] ?? null) ?></dd></div>
      <div class="wide"><dt>Adresse</dt><dd><?= $val($s['parent_address'] ?? null) ?></dd></div>
      <div class="wide"><dt>Remarques</dt><dd><?= $val($s['contact_notes'] ?? null) ?></dd></div>
    </dl>
    <form method="post" action="<?= e(url('t_contacts')) ?>" class="stack edit-form"><?= $hidden() ?><input type="hidden" name="back" value="t_student"><input type="hidden" name="who" value="parent">
      <div class="form-grid"><label>Téléphone<input type="tel" name="parent_phone" value="<?= e($s['parent_phone'] ?? '') ?>" maxlength="30" autocomplete="off"></label>
      <label>E-mail<input type="email" name="parent_email" value="<?= e($s['parent_email'] ?? '') ?>" maxlength="120" autocomplete="off"></label></div>
      <label>Adresse (pour les séances en présentiel)<input name="parent_address" value="<?= e($s['parent_address'] ?? '') ?>" maxlength="255" autocomplete="off"></label>
      <label>Remarques (préférences de contact, disponibilités…)<input name="contact_notes" value="<?= e($s['contact_notes'] ?? '') ?>" maxlength="500"></label>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div></form>
  </div>
 </div>

 <div class="col-stack">
  <div class="card tone-orange"><h2><?= icon('target') ?> Priorités actuelles</h2><?php require __DIR__ . '/_axes.php' ?>
    <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'analyse'])) ?>">Voir l'analyse pédagogique →</a></div>

  <div class="card tone-green editable<?= $hasShared ? '' : ' editing' ?>">
    <div class="ed-head"><h2><?= icon('message-circle') ?> Bilan partagé avec la famille</h2><?= $hasShared ? $editBtn : '' ?></div>
    <p class="muted"><small><?= icon('eye') ?> Visible par le parent et l'élève sur leur page d'accueil.</small></p>
    <div class="view">
      <?php if ($s['shared_message']): ?><p><?= nl2br(e($s['shared_message'])) ?></p><?php endif ?>
      <?php if ($s['shared_strengths']): ?><h3 class="mini-h">Points forts</h3><p><?= nl2br(e($s['shared_strengths'])) ?></p><?php endif ?>
      <?php if ($s['shared_to_work']): ?><h3 class="mini-h">Points à travailler</h3><p><?= nl2br(e($s['shared_to_work'])) ?></p><?php endif ?>
      <?php if ($s['shared_updated_at']): ?><p class="muted"><small>Publié le <?= e(fdate($s['shared_updated_at'], 'd/m/Y H:i')) ?></small></p><?php endif ?>
    </div>
    <form method="post" action="<?= e(url('t_shared')) ?>" class="stack edit-form"><?= $hidden() ?><input type="hidden" name="back" value="t_student">
      <label>Message de la tutrice<textarea name="shared_message" maxlength="3000" rows="3"><?= e($s['shared_message']) ?></textarea></label>
      <label>Points forts<textarea name="shared_strengths" maxlength="3000" rows="3" placeholder="<?= e(implode("\n", array_slice($an['strengths'] ?? [], 0, 4))) ?>"><?= e($s['shared_strengths']) ?></textarea></label>
      <label>Points à travailler<textarea name="shared_to_work" maxlength="3000" rows="3" placeholder="<?= e(implode("\n", array_map(fn($a) => AXES[$a], $prio['axes']))) ?>"><?= e($s['shared_to_work']) ?></textarea></label>
      <div class="row"><button class="btn sm"><?= icon('send') ?> Publier</button><?php if ($hasShared): ?><button type="button" class="btn ghost sm" data-edit>Annuler</button><?php endif ?></div></form>
  </div>
 </div>
</div>

<?php elseif ($tab === 'parent' || $tab === 'eleve'): $code = $tab === 'parent' ? 'parent' : 'student'; $d = $data[$code]; $cfg = qn_config($code);
$stat = fn($c) => !$data[$c]['sub'] ? ['Non commencé', 'warn'] : ($data[$c]['sub']['status'] === 'completed' ? ['Terminé le ' . fdate($data[$c]['sub']['completed_at']), 'sage'] : ['En cours', 'warn']); ?>
<div class="qn-switch">
  <div class="seg"><?php foreach (['parent' => ['parent', 'user', 'Parent'], 'eleve' => ['student', 'smile', 'Élève']] as $tk => [$c, $ic, $lb]): [$st, $tone] = $stat($c); ?><a href="<?= e(url('t_student', $sidp + ['tab' => $tk])) ?>" class="<?= $tab === $tk ? 'on' : '' ?>"><?= icon($ic) ?> <?= $lb ?> <span class="badge <?= $tone ?>"><?= e($st) ?></span></a><?php endforeach ?></div>
  <a class="btn ghost sm" href="<?= e(url('q', ['sid' => $s['id'], 'code' => $code])) ?>"><?= icon('pencil') ?> <?= $d['sub'] ? 'Compléter / modifier moi-même' : 'Remplir moi-même' ?></a>
</div>
<?php if ($code === 'student'): ?><p class="alert warn"><?= icon('lock') ?> Réponses personnelles de l'élève : confidentielles, jamais affichées au parent.</p><?php endif ?>
<?php if (!$d['sub']): ?><div class="card"><div class="empty-ill sm"><span>📝</span><p class="muted">Ce questionnaire n'a pas encore été rempli. Vous pouvez le faire passer pendant le premier rendez-vous (onglet « 1er RDV »).</p></div></div>
<?php else: $tones = ['tone-violet', 'tone-blue', 'tone-pink', 'tone-orange', 'tone-green', 'tone-teal', 'tone-gold']; foreach ($cfg['steps'] as $n => $st):
  $qs = array_values(array_filter($st['questions'], fn($qq) => qn_visible($qq, $d['all'])));
  $ans = []; $empty = [];
  foreach ($qs as $qq) { $v = qn_display($qq, $d['all'][$qq['key']] ?? null); if ($v === '' || $v === '-' || $v === '—') $empty[] = $qq; else $ans[] = [$qq, $v]; }
  if (!$qs) continue; ?>
<div class="card qn-step">
  <div class="row between"><h2><span class="qn-num"><?= $n + 1 ?></span> <?= e($st['title']) ?></h2><span class="muted"><small><?= count($ans) ?> / <?= count($qs) ?> répondues</small></span></div>
  <?php if ($ans): ?><dl class="qa-grid"><?php foreach ($ans as [$qq, $v]): ?><div><dt><?= e($qq['label']) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach ?></dl><?php endif ?>
  <?php if ($empty): ?><details class="qa-empty"><summary><?= count($empty) ?> question<?= count($empty) > 1 ? 's' : '' ?> sans réponse</summary><ul><?php foreach ($empty as $qq): ?><li><?= e($qq['label']) ?></li><?php endforeach ?></ul></details><?php endif ?>
</div>
<?php endforeach; endif ?>

<?php elseif ($tab === 'analyse'): ?>
<?php require __DIR__ . '/_analysis.php' ?>

<?php elseif ($tab === 'objectifs'):
$goalForm = function (?array $g) use ($hidden, $catOpts) { $g ??= ['id' => 0, 'title' => '', 'description' => '', 'category' => 'methodo', 'target_date' => '', 'status' => 'todo', 'progress' => 0, 'tutor_comment' => '', 'is_mission' => 0]; ?>
  <form method="post" action="<?= e(url('t_goal')) ?>" class="stack"><?= $hidden() ?><input type="hidden" name="back" value="t_student"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
    <div class="form-grid"><label>Titre<input name="title" required maxlength="200" value="<?= e($g['title']) ?>"></label>
      <label>Catégorie<select name="category"><?= $catOpts($g['category']) ?></select></label>
      <label>Date cible<input type="date" name="target_date" value="<?= e($g['target_date']) ?>"></label>
      <label>Statut<select name="status"><?php foreach (GOAL_STATUS as $k => $l): ?><option value="<?= $k ?>" <?= $g['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label>
      <label>Progression (%)<input type="number" name="progress" min="0" max="100" step="5" value="<?= (int)$g['progress'] ?>"></label></div>
    <label>Description<textarea name="description" maxlength="3000"><?= e($g['description']) ?></textarea></label>
    <label>Commentaire de la tutrice<textarea name="tutor_comment" maxlength="3000"><?= e($g['tutor_comment']) ?></textarea></label>
    <label class="check"><input type="checkbox" name="is_year" value="1" <?= ($g['horizon'] ?? 'month') === 'year' ? 'checked' : '' ?>> Objectif de l'année (affiché simplement au parent et à l'élève)</label>
    <label class="check"><input type="checkbox" name="is_mission" value="1" <?= $g['is_mission'] ? 'checked' : '' ?>> Afficher comme « mission » dans l'espace élève (3 max affichées)</label>
    <div class="row"><button class="btn"><?= icon('save') ?> <?= $g['id'] ? 'Enregistrer' : 'Ajouter' ?></button>
      <?php if ($g['id']): ?><button class="btn ghost" name="delete" value="1" data-confirm="Supprimer cet objectif ?"><?= icon('trash-2') ?> Supprimer</button><?php endif ?></div>
  </form>
<?php }; ?>
<?php $byStatus = ['in_progress' => [], 'rework' => [], 'todo' => [], 'acquired' => []];
foreach ($goals as $g) $byStatus[$g['status']][] = $g;
$groupInfo = ['in_progress' => ['En cours', 'tone-blue'], 'rework' => ['À retravailler', 'tone-orange'], 'todo' => ['À commencer', 'tone-violet'], 'acquired' => ['Acquis', 'tone-green']];
$existing = array_map(fn($g) => mb_strtolower($g['title']), $goals);
$sugg = array_values(array_filter($reco['goals'] ?? [], fn($sg) => !in_array(mb_strtolower($sg['title']), $existing, true))); ?>
<div class="row between goal-bar"><h2 class="sec-h"><?= icon('target') ?> Objectifs de <?= e($s['first_name']) ?></h2><button class="btn sm" data-toggle="newgoal"><?= icon('plus') ?> Nouvel objectif</button></div>
<div id="newgoal" class="card new-panel" hidden><h3>Nouvel objectif</h3><?php $goalForm(null) ?></div>
<?php if ($sugg): ?>
<div class="card tone-gold"><h2><?= icon('sparkles') ?> Suggestions selon les priorités</h2>
  <ul class="sugg-list"><?php foreach ($sugg as $sg): ?><li><div><strong><?= e($sg['title']) ?></strong><small class="muted"><?= e($sg['description']) ?></small></div>
    <form method="post" action="<?= e(url('t_goal')) ?>"><?= $hidden() ?><input type="hidden" name="back" value="t_student"><input type="hidden" name="id" value="0"><input type="hidden" name="title" value="<?= e($sg['title']) ?>"><input type="hidden" name="description" value="<?= e($sg['description']) ?>"><input type="hidden" name="category" value="<?= e($sg['category']) ?>"><input type="hidden" name="status" value="todo"><input type="hidden" name="progress" value="0">
      <button class="btn ghost sm"><?= icon('plus') ?> Ajouter</button></form></li><?php endforeach ?></ul>
</div>
<?php endif ?>
<?php if (!$goals): ?><div class="card"><div class="empty-ill sm"><span>🎯</span><p class="muted">Aucun objectif pour l'instant : ajoutez-en un ou partez des suggestions.</p></div></div><?php endif ?>
<?php foreach ($byStatus as $k => $list): if (!$list) continue; [$gl, $gt] = $groupInfo[$k]; ?>
<h3 class="goal-group"><?= e($gl) ?> <span class="muted">· <?= count($list) ?></span></h3>
<div class="goal-list">
<?php foreach ($list as $g): ?>
  <div class="card goal-card editable <?= $gt ?>" id="goal-<?= (int)$g['id'] ?>">
    <div class="view goal-row">
      <div class="goal-main">
        <h3><?= e($g['title']) ?></h3>
        <div class="goal-tags"><span class="badge"><?= e(AXES[$g['category']] ?? $g['category']) ?></span><?php if (($g['horizon'] ?? '') === 'year'): ?><span class="badge sage">Objectif de l'année</span><?php endif ?><?php if ($g['is_mission']): ?><span class="badge">🚀 Mission</span><?php endif ?><?php if ($g['target_date']): ?><span class="badge"><?= icon('calendar') ?> <?= e(fdate($g['target_date'])) ?></span><?php endif ?></div>
        <?php if ($g['description']): ?><p class="muted goal-desc"><?= e($g['description']) ?></p><?php endif ?>
        <p class="goal-student"><?= icon('smile') ?> <?= $g['student_done_at'] ? 'Signalé terminé par ' . e($s['first_name']) . ' le ' . e(fdate($g['student_done_at'])) : '<span class="muted">Pas encore signalé par ' . e($s['first_name']) . '</span>' ?><?= $g['student_feeling'] ? ' · « ' . e(FEELINGS[$g['student_feeling']]) . ' »' : '' ?></p>
        <?php if ($g['tutor_comment']): ?><p class="goal-comment"><?= icon('message-circle') ?> <?= e($g['tutor_comment']) ?></p><?php endif ?>
      </div>
      <div class="goal-prog"><div class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></div><strong><?= (int)$g['progress'] ?>%</strong></div>
      <button type="button" class="btn ghost sm" data-edit><?= icon('pencil') ?> Modifier</button>
    </div>
    <div class="edit-form"><div class="goal-top"><h3>Modifier l'objectif</h3><button type="button" class="btn ghost sm" data-edit>Annuler</button></div><?php $goalForm($g) ?></div>
  </div>
<?php endforeach ?>
</div>
<?php endforeach ?>

<?php elseif ($tab === 'matieres'): require __DIR__ . '/_t_subjects.php'; ?>

<?php elseif ($tab === 'seances'):
$sessForm = function (?array $se) use ($hidden) { $se ??= ['id' => 0, 'title' => '', 'starts_at' => date('Y-m-d 17:00:00'), 'duration_min' => 120, 'mode' => 'presentiel', 'status' => 'planned', 'objectives' => '']; ?>
  <form method="post" action="<?= e(url('t_session')) ?>" class="stack"><?= $hidden() ?><input type="hidden" name="back" value="t_student"><input type="hidden" name="id" value="<?= (int)$se['id'] ?>">
    <div class="form-grid"><label>Titre<input name="title" maxlength="150" value="<?= e(session_base_title($se['title'])) ?>" placeholder="Le numéro S1, S2… est ajouté automatiquement"></label>
      <label>Date<input type="date" name="date" required value="<?= e(substr($se['starts_at'], 0, 10)) ?>"></label>
      <label>Heure<input type="time" name="time" required value="<?= e(substr($se['starts_at'], 11, 5)) ?>"></label>
      <label>Durée (min)<input type="number" name="duration_min" min="15" max="600" step="15" value="<?= (int)$se['duration_min'] ?>"></label>
      <label>Format<select name="mode"><?php foreach (MODES as $k => $l): ?><option value="<?= $k ?>" <?= $se['mode'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label>
      <label>Statut<select name="status" data-status-select><?php foreach (SESSION_STATUS as $k => $l): ?><option value="<?= $k ?>" <?= $se['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label></div>
    <?php $isOff = in_array($se['status'], ['postponed', 'cancelled'], true); ?>
    <label data-show-for="postponed cancelled"<?= $isOff ? '' : ' hidden' ?>>Raison<input name="status_reason" maxlength="500" value="<?= e($se['status_reason'] ?? '') ?>" placeholder="ex. élève malade, voyage scolaire, indisponibilité de la tutrice…"></label>
    <?php if ($se['id']): ?>
    <div class="resched" data-show-for="postponed"<?= $se['status'] === 'postponed' ? '' : ' hidden' ?> data-resched data-total="<?= (int)$se['duration_min'] ?>">
      <span class="ql">Reporter à</span>
      <p class="field-help">Une ou plusieurs dates : vous pouvez découper la séance (par exemple 30 min un jour, 30 min un autre). Chaque créneau devient une séance du planning.</p>
      <div class="resched-rows" data-resched-rows>
        <div class="resched-row" data-resched-row>
          <label>Date<input type="date" name="rs_date[]"></label>
          <label>Heure<input type="time" name="rs_time[]" value="<?= e(substr($se['starts_at'], 11, 5)) ?>"></label>
          <label>Durée (min)<input type="number" name="rs_dur[]" min="15" max="600" step="15" value="<?= (int)$se['duration_min'] ?>"></label>
          <label>Format<select name="rs_mode[]"><?php foreach (MODES as $k => $l): ?><option value="<?= $k ?>" <?= $se['mode'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label>
          <button type="button" class="edit-btn" data-resched-del title="Retirer ce créneau" aria-label="Retirer ce créneau"><?= icon('trash-2') ?></button>
        </div>
      </div>
      <div class="row between"><button type="button" class="btn ghost sm" data-resched-add><?= icon('plus') ?> Découper : ajouter un créneau</button><span class="muted resched-live" data-resched-total></span></div>
    </div>
    <?php endif ?>
    <label>Objectifs de la séance<textarea name="objectives" maxlength="3000"><?= e($se['objectives']) ?></textarea></label>
    <?php if ($se['id']): ?><div class="form-grid"><label>Ajouter une note de séance<textarea name="note" maxlength="3000"></textarea></label><label>Visibilité de la note<select name="note_visibility"><option value="private">Privée</option><option value="shared">Partagée</option></select></label></div><?php endif ?>
    <div class="row"><button class="btn"><?= icon('save') ?> <?= $se['id'] ? 'Enregistrer' : 'Ajouter la séance' ?></button><?php if (!$se['id']): ?><button type="button" class="btn ghost" data-toggle="newsess">Annuler</button><?php endif ?>
      <?php if ($se['id']): ?><button class="btn ghost" name="delete" value="1" data-confirm="Supprimer cette séance ?"><?= icon('trash-2') ?> Supprimer</button><?php endif ?></div>
  </form>
<?php }; ?>
<?php $scheduleEdit = true; if (schedule_available()) require __DIR__ . '/_schedule_card.php'; ?>
<div class="card"><div class="row between"><h2><?= icon('calendar') ?> Séances · <?= fdur(array_sum(array_map(fn($x) => $x['status'] !== 'cancelled' ? (int)$x['duration_min'] : 0, $sessions))) ?> au total</h2>
  <div class="row"><button class="btn" data-toggle="newsess"><?= icon('plus') ?> Ajouter une séance</button></div></div>
  <?php $calView = ($_GET['view'] ?? '') === 'cal'; ?>
  <div class="seg" role="tablist"><a href="<?= e(url('t_student', $sidp + ['tab' => 'seances'])) ?>" class="<?= $calView ? '' : 'on' ?>"><?= icon('list-checks') ?> Liste</a><a href="<?= e(url('t_student', $sidp + ['tab' => 'seances', 'view' => 'cal'])) ?>" class="<?= $calView ? 'on' : '' ?>"><?= icon('calendar') ?> Calendrier</a></div>
  <div id="newsess" class="new-panel" hidden>
    <div class="row between new-panel-head"><h3><?= icon('plus') ?> Nouvelle séance</h3><button type="button" class="edit-btn" data-toggle="newsess" aria-label="Fermer"><?= icon('x') ?></button></div>
    <p class="field-help">Pour une séance ponctuelle en dehors du rythme habituel (rattrapage, séance en plus avant un contrôle…).</p>
    <?php $sessForm(null) ?>
  </div>
</div><br>
<?php if ($calView): $calBase = url('t_student', $sidp + ['tab' => 'seances']); ?><div class="card"><?php require __DIR__ . '/_calendar.php' ?></div><?php else: ?>
<?php foreach ($sessions as $se): $notes = all('SELECT * FROM session_notes WHERE session_id=? ORDER BY id DESC', [$se['id']]); ?>
  <div class="card" id="sess-<?= (int)$se['id'] ?>"><div class="row between"><p class="sess-meta"><?= e(fdatetime($se['starts_at'])) ?> · <?= fdur((int)$se['duration_min']) ?> · <?= e(MODES[$se['mode']]) ?></p>
    <span class="badge <?= ['planned' => '', 'done' => 'sage', 'postponed' => 'warn', 'cancelled' => 'err'][$se['status']] ?>"><?= e(SESSION_STATUS[$se['status']]) ?></span></div>
    <h3 class="sess-title"><?= e(session_label($se)) ?></h3>
    <?php $from = !empty($se['rescheduled_from']) ? one('SELECT starts_at FROM sessions WHERE id=?', [$se['rescheduled_from']]) : null;
      $kids = in_array($se['status'], ['postponed', 'cancelled'], true) && array_key_exists('rescheduled_from', $se) ? all('SELECT id, starts_at, duration_min, mode FROM sessions WHERE rescheduled_from=? ORDER BY starts_at', [$se['id']]) : [];
      $into = $se['status'] === 'postponed' && array_key_exists('merged_report_from', $se) ? all('SELECT id, starts_at, merged_report_min FROM sessions WHERE merged_report_from=? ORDER BY starts_at', [$se['id']]) : [];
      $mergedFrom = !empty($se['merged_report_from']) ? one('SELECT starts_at FROM sessions WHERE id=?', [$se['merged_report_from']]) : null; ?>
    <?php if (!empty($se['status_reason']) || $kids || $from || $into || $mergedFrom): ?><div class="sess-status-info">
      <?php if (!empty($se['status_reason'])): ?><p><strong>Raison :</strong> <?= e($se['status_reason']) ?></p><?php endif ?>
      <?php if ($kids): ?><p><strong>Reportée au :</strong> <?= implode(' + ', array_map(fn($k) => '<a href="#sess-' . (int)$k['id'] . '">' . e(fdatetime($k['starts_at'])) . ' (' . fdur((int)$k['duration_min']) . ')</a>', $kids)) ?></p><?php endif ?>
      <?php if ($into): ?><p><strong>Rattrapée pendant :</strong> <?= implode(' + ', array_map(fn($k) => '<a href="#sess-' . (int)$k['id'] . '">la séance du ' . e(fdatetime($k['starts_at'])) . '</a> (+' . fdur((int)$k['merged_report_min']) . ')', $into)) ?></p><?php endif ?>
      <?php if ($from): ?><p><?= icon('refresh-cw') ?> Report de la séance du <?= e(fdatetime($from['starts_at'])) ?></p><?php endif ?>
      <?php if ($mergedFrom): ?><p><?= icon('refresh-cw') ?> Prolongée de <?= fdur((int)$se['merged_report_min']) ?> : report de la séance du <?= e(fdatetime($mergedFrom['starts_at'])) ?></p><?php endif ?>
    </div><?php endif ?>
    <?php if ($se['objectives']): ?><p class="muted"><?= nl2br(e($se['objectives'])) ?></p><?php endif ?>
    <?php foreach ($notes as $n): ?><p><small><?= icon($n['visibility'] === 'shared' ? 'users' : 'lock') ?> <?= nl2br(e($n['content'])) ?></small></p><?php endforeach ?>
    <?php if (strtotime($se['starts_at']) < time() && $se['status'] !== 'cancelled'): $rep = session_report((int)$se['id']); $fb = session_feedback_of((int)$se['id']); ?>
    <div class="report-box">
      <div class="row between"><h4><?= icon('file-text') ?> Bilan de séance</h4>
        <?php if ($rep && $rep['sent_at']): ?><span class="badge sage">Envoyé au parent le <?= e(fdate($rep['sent_at'])) ?></span><?php elseif ($rep): ?><span class="badge">Brouillon</span><?php else: ?><span class="badge warn">À faire</span><?php endif ?></div>
      <?php if ($fb): ?><div class="fb-box"><strong><?= icon('smile') ?> Retour de <?= e($s['first_name']) ?></strong> <?= $fb['mood'] ? '<span class="badge">' . e(MOODS[$fb['mood']]) . '</span>' : '' ?>
        <?php if (!empty($fb['note'])): ?><p><small>« <?= e($fb['note']) ?> »</small></p><?php endif ?>
        <?php if ($fb['understood']): ?><p><small><strong>Compris / réussi :</strong> <?= e($fb['understood']) ?></small></p><?php endif ?>
        <?php if ($fb['to_deepen']): ?><p><small><strong>À approfondir :</strong> <?= e($fb['to_deepen']) ?></small></p><?php endif ?>
        <?php if ($fb['needs_next']): ?><p><small><strong>Besoin pour la prochaine fois :</strong> <?= e($fb['needs_next']) ?></small></p><?php endif ?></div>
      <?php else: ?><p class="muted"><small><?= e($s['first_name']) ?> n'a pas encore donné son retour.</small></p><?php endif ?>
      <?= report_subject_chips($rep) ?>
      <details <?= $rep ? '' : 'open' ?>><summary><?= $rep ? 'Modifier le bilan' : 'Rédiger le bilan' ?></summary>
        <form method="post" action="<?= e(url('t_report')) ?>" class="stack"><?= $hidden() ?><input type="hidden" name="session_id" value="<?= (int)$se['id'] ?>">
          <?php $chosen = report_subjects($rep); $choices = report_subject_choices((int)$s['id']); $others = array_diff($chosen, $choices, REPORT_TOPICS); ?>
          <div class="subj-pick"><span class="ql">Matières abordées</span>
            <div class="pills sm"><?php foreach ($choices as $x): ?><label class="pill"><input type="checkbox" name="subjects[]" value="<?= e($x) ?>" <?= in_array($x, $chosen, true) ? 'checked' : '' ?>><span><?= e($x) ?></span></label><?php endforeach ?></div>
            <span class="ql">Et aussi</span>
            <div class="pills sm"><?php foreach (REPORT_TOPICS as $x): ?><label class="pill topic"><input type="checkbox" name="subjects[]" value="<?= e($x) ?>" <?= in_array($x, $chosen, true) ? 'checked' : '' ?>><span><?= e($x) ?></span></label><?php endforeach ?></div>
            <input name="subjects_other" maxlength="200" value="<?= e(implode(', ', $others)) ?>" placeholder="Autre (séparer par des virgules)" aria-label="Autre matière ou thème">
          </div>
          <label>Ce qu'on a travaillé <span class="q-hint"><?= icon('eye') ?> visible par l'élève</span><textarea name="worked_on" maxlength="3000" rows="2"><?= e($rep['worked_on'] ?? '') ?></textarea></label>
          <label><?= icon('lock') ?> Note pour moi (privée)<textarea name="tutor_note" maxlength="3000" rows="2"><?= e($rep['tutor_note'] ?? '') ?></textarea></label>
          <label><?= icon('users') ?> Bilan pour le parent<textarea name="parent_summary" maxlength="3000" rows="3" placeholder="Quelques phrases simples : ce qui a été fait, comment ça s'est passé, sur quoi s'appuyer à la maison."><?= e($rep['parent_summary'] ?? '') ?></textarea></label>
          <label>Pour la prochaine séance<input name="next_focus" maxlength="500" value="<?= e($rep['next_focus'] ?? '') ?>" placeholder="ex. finir le chapitre 4, préparer le contrôle de jeudi"></label>
          <div class="row"><button class="btn ghost sm"><?= icon('save') ?> Enregistrer</button><button class="btn sm" name="send" value="1"><?= icon('send') ?> <?= $rep && $rep['sent_at'] ? 'Renvoyer au parent' : 'Enregistrer et envoyer au parent' ?></button></div>
        </form>
      </details>
    </div>
    <?php endif ?>
    <details><summary>Modifier la séance</summary><br><?php $sessForm($se) ?></details></div><br>
<?php endforeach ?>
<?php endif ?>

<?php elseif ($tab === 'bilans'):
$months = array_values(array_unique(array_map(fn($x) => substr($x['starts_at'], 0, 7), $sessions)));
if (!in_array(date('Y-m'), $months, true)) $months[] = date('Y-m');
rsort($months);
$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$ov = month_overview((int)$s['id'], $month);
$saved = one('SELECT * FROM monthly_reports WHERE student_id=? AND month=?', [$s['id'], $month]);
$draft = $saved['summary'] ?? month_draft($s, $ov, $month); ?>
<div class="card month-card">
  <div class="row between month-head"><h2><?= icon('chart-column') ?> Bilan du mois</h2>
    <form method="get" action="<?= e(url()) ?>" class="row"><input type="hidden" name="r" value="t_student"><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="tab" value="bilans">
      <select name="month" data-autosubmit aria-label="Mois"><?php foreach ($months as $m): ?><option value="<?= e($m) ?>" <?= $m === $month ? 'selected' : '' ?>><?= e(month_label($m)) ?></option><?php endforeach ?></select></form></div>
  <div class="month-stats">
    <div class="stat"><span class="muted">Séances réalisées</span><strong><?= $ov['count'] ?></strong></div>
    <div class="stat"><span class="muted">Heures</span><strong><?= fdur($ov['minutes']) ?></strong></div>
    <div class="stat"><span class="muted">Ressenti de <?= e($s['first_name']) ?></span><strong class="stat-moods"><span>😄 <?= $ov['moods']['good'] ?></span><span>😐 <?= $ov['moods']['ok'] ?></span><span>😣 <?= $ov['moods']['hard'] ?></span></strong></div>
    <div class="stat"><span class="muted">Objectifs atteints</span><strong><?= count($ov['acquired']) ?></strong></div>
  </div>
  <?php if (!$ov['reports']): ?><p class="alert warn month-alert">Aucun bilan de séance ce mois-ci : le brouillon sera plus complet si vous rédigez les bilans après chaque séance (onglet « Séances »).</p><?php endif ?>
  <form method="post" action="<?= e(url('t_monthly')) ?>" class="stack month-form"><?= $hidden() ?><input type="hidden" name="month" value="<?= e($month) ?>">
    <label>Bilan pour la famille<span class="field-help">Proposé automatiquement à partir des bilans de séance : relisez et complétez avant de publier.</span><textarea name="summary" rows="8" maxlength="6000"><?= e($draft) ?></textarea></label>
    <div class="row"><button class="btn ghost sm"><?= icon('save') ?> Enregistrer le brouillon</button><button class="btn sm" name="publish" value="1"><?= icon('send') ?> <?= !empty($saved['published_at']) ? 'Republier' : 'Publier pour la famille' ?></button>
      <?php if (!empty($saved['published_at'])): ?><span class="badge sage">Publié le <?= e(fdate($saved['published_at'])) ?></span><?php endif ?></div>
  </form>
</div><br>
<div class="card tone-blue"><h2><?= icon('file-text') ?> Bilans de séance du mois</h2>
  <?php if (!$ov['reports']): ?><p class="muted">Aucun.</p><?php endif ?>
  <?php foreach ($ov['reports'] as $rp): ?><div class="report-item"><span class="badge"><?= e(fdatetime($rp['date'])) ?></span>
    <?php if (!empty($rp['subjects'])): ?><p><small><strong>Matières :</strong> <?= e(implode(', ', $rp['subjects'])) ?></small></p><?php endif ?>
    <?php if ($rp['worked_on']): ?><p><small><strong>Travaillé :</strong> <?= e($rp['worked_on']) ?></small></p><?php endif ?>
    <?php if ($rp['summary']): ?><p><small><strong>Bilan parent :</strong> <?= e($rp['summary']) ?></small></p><?php endif ?></div><?php endforeach ?>
</div>

<?php elseif ($tab === 'progression'):
$doneSe = all('SELECT id, starts_at, duration_min FROM sessions WHERE student_id=? AND (status="done" OR (status="planned" AND starts_at < NOW())) ORDER BY starts_at DESC', [$s['id']]);
$minutes = array_sum(array_map(fn($x) => (int)$x['duration_min'], $doneSe));
$acq = count(array_filter($goals, fn($g) => $g['status'] === 'acquired'));
$avg = $goals ? (int)round(array_sum(array_map(fn($g) => (int)$g['progress'], $goals)) / count($goals)) : 0;
try { $fbs = all('SELECT f.mood, f.note, s.starts_at FROM session_feedback f JOIN sessions s ON s.id=f.session_id WHERE s.student_id=? ORDER BY s.starts_at DESC LIMIT 8', [$s['id']]); } catch (PDOException) { $fbs = []; }
try { $grades = all('SELECT ss.name, g.value, g.out_of, g.label, g.graded_on FROM subject_grades g JOIN student_subjects ss ON ss.id=g.subject_id WHERE ss.student_id=? ORDER BY ss.position, ss.name, g.graded_on', [$s['id']]); } catch (PDOException) { $grades = []; }
$bySubj = []; foreach ($grades as $gr) $bySubj[$gr['name']][] = $gr;
$moodEmo = ['good' => '😄', 'ok' => '😐', 'hard' => '😣']; ?>
<div class="card"><h2><?= icon('chart-column') ?> En bref</h2><div class="month-stats">
  <div class="stat"><span class="muted">Séances réalisées</span><strong><?= count($doneSe) ?></strong><small class="muted"><?= fdur($minutes) ?> au total</small></div>
  <div class="stat"><span class="muted">Progression moyenne</span><strong><?= $avg ?>%</strong><small class="muted">sur <?= count($goals) ?> objectif<?= count($goals) > 1 ? 's' : '' ?></small></div>
  <div class="stat"><span class="muted">Objectifs acquis</span><strong><?= $acq ?> / <?= count($goals) ?></strong></div>
  <div class="stat"><span class="muted">Dernier ressenti</span><strong><?= $fbs ? ($moodEmo[$fbs[0]['mood']] ?? '🙂') . ' ' . e(MOODS[$fbs[0]['mood']] ?? '') : '—' ?></strong><?php if ($fbs): ?><small class="muted">séance du <?= e(fdate($fbs[0]['starts_at'])) ?></small><?php endif ?></div>
</div></div>
<div class="grid-2 two-cols">
 <div class="col-stack">
  <div class="card tone-violet"><h2><?= icon('target') ?> Avancement des objectifs</h2>
    <?php foreach ($goals as $g): ?><div class="axis"><span class="lbl"><?= e($g['title']) ?> <?php require __DIR__ . '/_goal_status.php' ?></span><span class="n"><?= (int)$g['progress'] ?>%</span><div class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></div></div><?php endforeach ?>
    <?php if (!$goals): ?><p class="muted">Aucun objectif.</p><?php endif ?>
    <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'objectifs'])) ?>">Gérer les objectifs →</a></div>
  <div class="card tone-gold"><h2><?= icon('chart-column') ?> Notes par matière</h2>
    <?php if (!$bySubj): ?><p class="muted">Aucune note saisie. Les notes s'ajoutent dans « Matières ».</p><?php endif ?>
    <?php foreach ($bySubj as $name => $list): $vals = array_map(fn($x) => 20 * (float)$x['value'] / max(1, (float)$x['out_of']), $list); $avgS = array_sum($vals) / count($vals); $trend = count($vals) > 1 ? end($vals) - $vals[0] : 0; ?>
      <div class="grade-row"><strong><?= e($name) ?></strong>
        <span class="grade-dots"><?php foreach (array_slice($list, -6) as $x): ?><span class="ra-chip" title="<?= e(($x['label'] ?: 'Note') . ' · ' . fdate($x['graded_on'])) ?>"><?= rtrim(rtrim(number_format((float)$x['value'], 2, ',', ''), '0'), ',') ?>/<?= (int)$x['out_of'] ?></span><?php endforeach ?></span>
        <span class="grade-avg"><?= number_format($avgS, 1, ',', '') ?>/20 <?= $trend > 0.5 ? '<span class="up">↗</span>' : ($trend < -0.5 ? '<span class="down">↘</span>' : '') ?></span></div>
    <?php endforeach ?>
    <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'matieres'])) ?>">Voir les matières →</a></div>
 </div>
 <div class="col-stack">
  <div class="card tone-pink"><h2><?= icon('smile') ?> Ressentis après les séances</h2>
    <?php if (!$fbs): ?><p class="muted"><?= e($s['first_name']) ?> n'a pas encore donné de retour de séance.</p><?php endif ?>
    <ul class="list"><?php foreach ($fbs as $f): ?><li><span class="fb-emo"><?= $moodEmo[$f['mood']] ?? '🙂' ?></span><div><strong><?= e(MOODS[$f['mood']] ?? 'Retour') ?></strong> <small class="muted">· séance du <?= e(fdate($f['starts_at'])) ?></small><?= !empty($f['note']) ? '<br>« ' . e($f['note']) . ' »' : '' ?></div></li><?php endforeach ?></ul></div>
  <div class="card tone-teal"><h2><?= icon('message-circle') ?> Journal de l'élève</h2><ul class="list">
    <?php foreach ($entries as $h): ?><li><?= icon(entry_icon($h['entry_type'])) ?><div><?= e(entry_label($h)) ?><?= $h['goal'] ? ' · ' . e($h['goal']) : '' ?><?= $h['comment'] ? '<br>« ' . e($h['comment']) . ' »' : '' ?><br><small class="muted"><?= e(fdate($h['created_at'], 'd/m/Y H:i')) ?></small></div></li><?php endforeach ?>
    <?php if (!$entries): ?><li class="muted">Aucune entrée.</li><?php endif ?></ul></div>
 </div>
</div>
<?php endif ?>
