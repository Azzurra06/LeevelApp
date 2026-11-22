<?php $title = 'Fiche - ' . $s['first_name']; $sidp = ['sid' => $s['id']];
$tabs = ['profil' => 'Profil', 'parent' => 'Questionnaire parent', 'eleve' => 'Questionnaire élève', 'analyse' => 'Analyse pédagogique', 'objectifs' => 'Objectifs', 'matieres' => 'Matières', 'seances' => 'Séances', 'bilans' => 'Bilans', 'progression' => 'Progression'];
if (!isset($tabs[$tab])) $tab = 'profil';
$hidden = fn() => csrf_field() . '<input type="hidden" name="sid" value="' . (int)$s['id'] . '">';
$catOpts = fn($cur) => implode('', array_map(fn($a, $l) => '<option value="' . e($a) . '"' . ($cur === $a ? ' selected' : '') . '>' . e($l) . '</option>', array_keys(AXES), AXES)); ?>
<?php if (in_array($tab, ['seances', 'matieres'], true)): ?>
<div class="page-head"><div><h1><?= icon($tab === 'seances' ? 'calendar' : 'book-open') ?> <?= $tab === 'seances' ? 'Séances' : 'Matières' ?></h1><p><?= e($s['first_name']) ?> · <?= e($s['grade']) ?><?= $s['school'] ? ' · ' . e($s['school']) : '' ?></p></div></div>
<?php else: $ficheTab = $tab; require __DIR__ . '/_fiche_head.php'; endif ?>

<?php if ($tab === 'profil'): ?>
<div class="grid-2 align-start">
  <?php $parentU = array_values(array_filter($users, fn($x) => $x['role'] === 'parent'))[0] ?? null; $pane = ($_GET['pane'] ?? '') === 'parent' ? 'parent' : 'student'; ?>
  <div class="card" data-views>
    <div class="row between"><h2><?= icon('user') ?> Profil</h2>
      <div class="seg seg-tight" role="tablist"><button type="button" data-view="student" class="<?= $pane === 'student' ? 'on' : '' ?>"><?= icon('smile') ?> Élève</button><button type="button" data-view="parent" class="<?= $pane === 'parent' ? 'on' : '' ?>"><?= icon('user') ?> Parent</button></div></div>
    <?php $val = fn($v) => ($v ?? '') !== '' ? e($v) : '<span class="muted">Non renseigné</span>';
    $editBtn = '<button type="button" class="edit-btn" data-edit title="Modifier" aria-label="Modifier">' . icon('pencil') . '</button>'; ?>

    <div data-pane="student" <?= $pane === 'student' ? '' : 'hidden' ?>>
      <div class="editable">
        <div class="ed-head"><h3><?= icon('book-open') ?> Scolarité</h3><?= $editBtn ?></div>
        <dl class="fields view"><div><dt>Prénom</dt><dd><?= $val($s['first_name']) ?></dd></div><div><dt>Classe</dt><dd><?= $val(CLASSES[$s['grade']] ?? $s['grade']) ?></dd></div><div class="wide"><dt>Établissement</dt><dd><?= $val($s['school']) ?></dd></div></dl>
        <form method="post" action="<?= e(url('t_profile')) ?>" class="stack edit-form"><?= $hidden() ?><input type="hidden" name="back" value="t_student">
          <div class="form-grid"><label>Prénom<input name="first_name" value="<?= e($s['first_name']) ?>" maxlength="80"></label><label>Classe<?= grade_select('grade', $s['grade']) ?></label></div>
          <label>Établissement<input name="school" value="<?= e($s['school']) ?>" maxlength="120"></label>
          <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div></form>
      </div>
      <div class="editable ed-sep">
        <div class="ed-head"><h3><?= icon('message-circle') ?> Coordonnées de l'élève</h3><?= $editBtn ?></div>
        <dl class="fields view"><div><dt>Téléphone</dt><dd><?= !empty($s['student_phone']) ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $s['student_phone'])) . '">' . e($s['student_phone']) . '</a>' : $val(null) ?></dd></div><div><dt>E-mail</dt><dd><?= !empty($s['student_email']) ? '<a href="mailto:' . e($s['student_email']) . '">' . e($s['student_email']) . '</a>' : $val(null) ?></dd></div></dl>
        <form method="post" action="<?= e(url('t_contacts')) ?>" class="stack edit-form"><?= $hidden() ?><input type="hidden" name="back" value="t_student"><input type="hidden" name="who" value="student">
          <div class="form-grid"><label>Téléphone<input type="tel" name="student_phone" value="<?= e($s['student_phone'] ?? '') ?>" maxlength="30" autocomplete="off"></label>
          <label>E-mail<input type="email" name="student_email" value="<?= e($s['student_email'] ?? '') ?>" maxlength="120" autocomplete="off"></label></div>
          <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div></form>
      </div>
    </div>

    <div data-pane="parent" <?= $pane === 'parent' ? '' : 'hidden' ?>>
      <div class="editable">
        <div class="ed-head"><h3><?= $parentU ? e($parentU['display_name']) : 'Parent' ?></h3><?= $editBtn ?></div>
        <?php if ($parentU): ?><p class="muted"><small>Compte <code><?= e($parentU['username']) ?></code></small></p><?php endif ?>
        <dl class="fields view">
          <div><dt>Téléphone</dt><dd><?= !empty($s['parent_phone']) ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $s['parent_phone'])) . '">' . e($s['parent_phone']) . '</a>' : $val(null) ?></dd></div>
          <div><dt>E-mail</dt><dd><?= !empty($s['parent_email']) ? '<a href="mailto:' . e($s['parent_email']) . '">' . e($s['parent_email']) . '</a>' : $val(null) ?></dd></div>
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
  </div>
  <div class="card"><h2><?= icon('users') ?> Comptes associés</h2><ul class="list"><?php foreach ($users as $x): ?><li><?= icon('user') ?><div><?= e($x['display_name']) ?> · <code><?= e($x['username']) ?></code> · <?= e(ROLE_LABELS[$x['role']]) ?></div></li><?php endforeach ?></ul>
  </div>
  <div class="card"><h2><?= icon('target') ?> Priorités actuelles</h2><?php require __DIR__ . '/_axes.php' ?></div>
  <div class="card"><h2><?= icon('users') ?> Bilan partagé avec la famille</h2>
    <p class="muted"><small>Visible par le parent et l'élève. N'y mettez que des informations partageables.</small></p>
    <form method="post" action="<?= e(url('t_shared')) ?>" class="stack"><?= $hidden() ?><input type="hidden" name="back" value="t_student">
      <label>Message / bilan de la tutrice<textarea name="shared_message" maxlength="3000"><?= e($s['shared_message']) ?></textarea></label>
      <label>Points forts<textarea name="shared_strengths" maxlength="3000" placeholder="<?= e(implode("\n", array_slice($an['strengths'] ?? [], 0, 4))) ?>"><?= e($s['shared_strengths']) ?></textarea></label>
      <label>Points à travailler<textarea name="shared_to_work" maxlength="3000" placeholder="<?= e(implode("\n", array_map(fn($a) => AXES[$a], $prio['axes']))) ?>"><?= e($s['shared_to_work']) ?></textarea></label>
      <button class="btn"><?= icon('save') ?> Publier</button>
      <?php if ($s['shared_updated_at']): ?><small class="muted">Mis à jour le <?= e(fdate($s['shared_updated_at'], 'd/m/Y H:i')) ?></small><?php endif ?></form></div>
</div>

<?php elseif ($tab === 'parent' || $tab === 'eleve'): $code = $tab === 'parent' ? 'parent' : 'student'; $d = $data[$code]; $cfg = qn_config($code); ?>
<div class="card">
  <div class="row between"><h2><?= icon('clipboard-list') ?> <?= e($cfg['title']) ?></h2>
    <span class="badge <?= ($d['sub']['status'] ?? '') === 'completed' ? 'sage' : 'warn' ?>"><?= !$d['sub'] ? 'Non commencé' : ($d['sub']['status'] === 'completed' ? 'Terminé le ' . e(fdate($d['sub']['completed_at'])) : 'En cours') ?></span></div>
  <p><a class="btn ghost sm" href="<?= e(url('q', ['sid' => $s['id'], 'code' => $code])) ?>"><?= icon('pencil') ?> <?= $d['sub'] ? 'Compléter / modifier moi-même' : 'Remplir moi-même' ?></a></p>
  <?php if ($code === 'student'): ?><p class="alert warn"><?= icon('lock') ?> Réponses personnelles de l'élève : confidentielles, jamais affichées au parent.</p><?php endif ?>
  <p class="muted"><small>Réponses d'origine, non modifiables par la tutrice.</small></p>
  <?php foreach ($cfg['steps'] as $st): ?><fieldset><legend><?= e($st['title']) ?></legend><dl class="recap">
    <?php foreach ($st['questions'] as $qq): if (!qn_visible($qq, $d['all'])) continue; ?><dt><?= e($qq['label']) ?></dt><dd><?= e(qn_display($qq, $d['all'][$qq['key']] ?? null)) ?></dd><?php endforeach ?>
  </dl></fieldset><br><?php endforeach ?>
</div>

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
<div class="card"><div class="row between"><h2><?= icon('target') ?> Objectifs</h2><button class="btn" data-toggle="newgoal"><?= icon('plus') ?> Nouvel objectif</button></div>
  <div id="newgoal" hidden><?php $goalForm(null) ?></div>
  <?php if ($reco['goals']): ?><details><summary>Objectifs suggérés selon les priorités</summary><ul class="dots"><?php foreach ($reco['goals'] as $sg): ?><li><strong><?= e($sg['title']) ?></strong> - <?= e($sg['description']) ?></li><?php endforeach ?></ul></details><?php endif ?></div><br>
<?php foreach ($goals as $g): ?>
  <div class="card"><div class="row between"><h3><?= e($g['title']) ?></h3><div class="row"><?php if (($g['horizon'] ?? '') === 'year'): ?><span class="badge sage">Objectif de l'année</span><?php endif ?><?php require __DIR__ . '/_goal_status.php' ?> <span class="badge"><?= e(AXES[$g['category']] ?? $g['category']) ?></span></div></div>
    <div class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></div>
    <p class="muted"><small>Élève : <?= $g['student_done_at'] ? 'signalé terminé le ' . e(fdate($g['student_done_at'])) : 'pas encore signalé' ?><?= $g['student_feeling'] ? ' · ressenti : « ' . e(FEELINGS[$g['student_feeling']]) . ' »' : '' ?></small></p>
    <details><summary>Modifier / valider</summary><br><?php $goalForm($g) ?></details></div><br>
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
      <label>Statut<select name="status"><?php foreach (SESSION_STATUS as $k => $l): ?><option value="<?= $k ?>" <?= $se['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label></div>
    <label>Objectifs de la séance<textarea name="objectives" maxlength="3000"><?= e($se['objectives']) ?></textarea></label>
    <?php if ($se['id']): ?><div class="form-grid"><label>Ajouter une note de séance<textarea name="note" maxlength="3000"></textarea></label><label>Visibilité de la note<select name="note_visibility"><option value="private">Privée</option><option value="shared">Partagée</option></select></label></div><?php endif ?>
    <div class="row"><button class="btn"><?= icon('save') ?> <?= $se['id'] ? 'Enregistrer' : 'Ajouter' ?></button>
      <?php if ($se['id']): ?><button class="btn ghost" name="delete" value="1" data-confirm="Supprimer cette séance ?"><?= icon('trash-2') ?> Supprimer</button><?php endif ?></div>
  </form>
<?php }; ?>
<div class="card"><div class="row between"><h2><?= icon('calendar') ?> Séances · <?= fdur(array_sum(array_map(fn($x) => $x['status'] !== 'cancelled' ? (int)$x['duration_min'] : 0, $sessions))) ?> au total</h2>
  <div class="row"><button class="btn ghost" data-toggle="genplan"><?= icon('sparkles') ?> Générer le plan</button><button class="btn" data-toggle="newsess"><?= icon('plus') ?> Ajouter</button></div></div>
  <div id="newsess" hidden><?php $sessForm(null) ?></div>
  <?php $calView = ($_GET['view'] ?? '') === 'cal'; ?>
  <div class="seg" role="tablist"><a href="<?= e(url('t_student', $sidp + ['tab' => 'seances'])) ?>" class="<?= $calView ? '' : 'on' ?>"><?= icon('list-checks') ?> Liste</a><a href="<?= e(url('t_student', $sidp + ['tab' => 'seances', 'view' => 'cal'])) ?>" class="<?= $calView ? 'on' : '' ?>"><?= icon('calendar') ?> Calendrier</a></div>
  <div id="genplan" hidden><form method="post" action="<?= e(url('t_plan')) ?>" class="row"><?= $hidden() ?><input type="hidden" name="back" value="t_student">
    <label>Lundi de la semaine 1<input type="date" name="start" required value="<?= e((new DateTime('next monday'))->format('Y-m-d')) ?>"></label>
    <label class="check"><input type="checkbox" name="replace" value="1"> Remplacer les séances prévues du plan</label><label class="check"><input type="checkbox" name="with_goals" value="1"> + objectifs suggérés</label><button class="btn">Générer</button></form></div></div><br>
<?php if ($calView): $calBase = url('t_student', $sidp + ['tab' => 'seances']); ?><div class="card"><?php require __DIR__ . '/_calendar.php' ?></div><?php else: ?>
<?php foreach ($sessions as $se): $notes = all('SELECT * FROM session_notes WHERE session_id=? ORDER BY id DESC', [$se['id']]); ?>
  <div class="card" id="sess-<?= (int)$se['id'] ?>"><div class="row between"><h3><?= icon($se['mode'] === 'visio' ? 'video' : 'map-pin') ?> <?= e(fdatetime($se['starts_at'])) ?> · <?= fdur((int)$se['duration_min']) ?> · <?= e(MODES[$se['mode']]) ?></h3>
    <span class="badge <?= ['planned' => '', 'done' => 'sage', 'postponed' => 'warn', 'cancelled' => 'err'][$se['status']] ?>"><?= e(SESSION_STATUS[$se['status']]) ?></span></div>
    <p><strong><?= e(session_label($se)) ?></strong></p><?php if ($se['objectives']): ?><p class="muted"><?= nl2br(e($se['objectives'])) ?></p><?php endif ?>
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
      <details <?= $rep ? '' : 'open' ?>><summary><?= $rep ? 'Modifier le bilan' : 'Rédiger le bilan' ?></summary>
        <form method="post" action="<?= e(url('t_report')) ?>" class="stack"><?= $hidden() ?><input type="hidden" name="session_id" value="<?= (int)$se['id'] ?>">
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
<div class="card"><h2><?= icon('file-text') ?> Bilans de séance du mois</h2>
  <?php if (!$ov['reports']): ?><p class="muted">Aucun.</p><?php endif ?>
  <?php foreach ($ov['reports'] as $rp): ?><div class="report-item"><span class="badge"><?= e(fdatetime($rp['date'])) ?></span>
    <?php if ($rp['worked_on']): ?><p><small><strong>Travaillé :</strong> <?= e($rp['worked_on']) ?></small></p><?php endif ?>
    <?php if ($rp['summary']): ?><p><small><strong>Bilan parent :</strong> <?= e($rp['summary']) ?></small></p><?php endif ?></div><?php endforeach ?>
</div>

<?php elseif ($tab === 'progression'): ?>
<div class="grid-2">
  <div class="card"><h2><?= icon('chart-column') ?> Objectifs</h2>
    <?php foreach ($goals as $g): ?><div class="axis"><span class="lbl"><?= e($g['title']) ?> <?php require __DIR__ . '/_goal_status.php' ?></span><span class="n"><?= (int)$g['progress'] ?>%</span><div class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></div></div><?php endforeach ?>
    <?php if (!$goals): ?><p class="muted">Aucun objectif.</p><?php endif ?></div>
  <div class="card"><h2><?= icon('message-circle') ?> Journal de l'élève</h2><ul class="list">
    <?php foreach ($entries as $h): ?><li><?= icon(entry_icon($h['entry_type'])) ?><div><?= e(entry_label($h)) ?><?= $h['goal'] ? ' · ' . e($h['goal']) : '' ?><?= $h['comment'] ? '<br>« ' . e($h['comment']) . ' »' : '' ?><br><small class="muted"><?= e(fdate($h['created_at'], 'd/m/Y H:i')) ?></small></div></li><?php endforeach ?>
    <?php if (!$entries): ?><li class="muted">Aucune entrée.</li><?php endif ?></ul></div>
</div>
<?php endif ?>
