<?php // Fiche élève > Matières (tutrice). $s, $subjects, $suggestions, $files, $blockers
$lvNames = array_map(fn($x) => $x[0], (require APP_ROOT . '/app/questionnaires/_levels.php')['subjects']);
$have = array_column($subjects, 'name');
$h = fn(array $extra = []) => csrf_field() . '<input type="hidden" name="sid" value="' . (int)$s['id'] . '">' . implode('', array_map(fn($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string)$v) . '">', array_keys($extra), $extra));
$act = fn() => e(url('t_subjects'));
$unseen = array_filter($blockers, fn($b) => !$b['seen_at']); ?>
<?php if (!subjects_available()): ?><p class="alert warn">La base de données doit être mise à jour (migration 009) pour activer les matières.</p><?php return; endif ?>
<?php $kindSkills = function_exists('skills_available') && skills_available(); ?>
<div class="kind-switch" role="tablist" data-kind-tabs>
  <button type="button" class="on" data-kind-tab="subjects"><?= icon('book-open') ?> Matières scolaires <small><?= count($subjects) ?></small></button>
  <button type="button" data-kind-tab="skills"><?= icon('sparkles') ?> Compétences transversales <small><?= count($skills ?? []) ?></small></button>
</div>
<div class="kind-pane" data-kind-pane="subjects">

<div class="card">
  <div class="row between"><h2><?= icon('book-open') ?> Matières travaillées</h2><span class="muted"><small>Visibles par l'élève dans « Mes matières » et par le parent (moyennes).</small></span></div>
  <form method="post" action="<?= $act() ?>" class="row subj-add"><?= $h(['action' => 'subject_add']) ?>
    <select name="name" aria-label="Matière"><option value="">Choisir une matière…</option><?php foreach ($lvNames as $n): if (in_array($n, $have, true)) continue; ?><option><?= e($n) ?></option><?php endforeach ?></select>
    <input name="custom" maxlength="80" placeholder="ou une autre matière" aria-label="Autre matière">
    <button class="btn sm"><?= icon('plus') ?> Ajouter</button>
  </form>
  <?php if ($suggestions): ?><p class="muted suggest"><small>Suggérées d'après les questionnaires :</small>
    <?php foreach ($suggestions as $n): ?><form method="post" action="<?= $act() ?>" class="inline"><?= $h(['action' => 'subject_add', 'name' => $n]) ?><button class="pill-btn sm-legend"><?= icon('plus') ?> <?= e($n) ?></button></form><?php endforeach ?></p><?php endif ?>
</div><br>

<?php if ($subjects): ?>
<nav class="subj-tabs" aria-label="Matières" data-subj-tabs>
  <?php foreach ($subjects as $i => $sub): ?><button type="button" class="subj-tab<?= $i === 0 ? ' on' : '' ?>" data-subj-tab="subj-<?= (int)$sub['id'] ?>"><?= e($sub['name']) ?><?php if ($sub['avg'] !== null): ?> <small><?= fmt_grade($sub['avg']) ?></small><?php endif ?></button><?php endforeach ?>
</nav>
<?php else: ?><div class="card"><div class="empty-ill sm"><span>📚</span><p class="muted">Ajoutez une première matière ci-dessus pour suivre ses notes, ses chapitres et ses quiz.</p></div></div><?php endif ?>
<div class="subj-panes">
<?php foreach ($subjects as $i => $sub): ?>
  <div class="card subj-pane" id="subj-<?= (int)$sub['id'] ?>" data-subj-pane <?= $i === 0 ? '' : 'hidden' ?>>
    <div class="row between"><h2><?= icon('book-open') ?> <?= e($sub['name']) ?></h2>
      <form method="post" action="<?= $act() ?>" data-confirm="Retirer cette matière, ses notes et ses chapitres ?"><?= $h(['action' => 'subject_del', 'subject_id' => $sub['id']]) ?><button class="edit-btn" title="Retirer la matière" aria-label="Retirer la matière"><?= icon('trash-2') ?></button></form></div>
    <div class="avg-row"><div class="avg-big"><strong><?= fmt_grade($sub['avg']) ?></strong><small>/20 · <?= count($sub['grades']) ?> note<?= count($sub['grades']) > 1 ? 's' : '' ?></small></div>
      <?= start_avg_html($sub) ?><?php if ($sub['trend']): ?><span class="trend <?= $sub['trend'] > 0 ? 'up' : 'down' ?>"><?= $sub['trend'] > 0 ? '↗ En progrès' : '↘ En baisse' ?></span><?php endif ?></div>
    <form method="post" action="<?= $act() ?>" class="start-form"><?= $h(['action' => 'subject_start', 'subject_id' => $sub['id']]) ?>
      <label>Moyenne de départ<input name="start_avg" inputmode="decimal" value="<?= ($sub['start_avg'] ?? null) !== null ? e(fmt_grade((float)$sub['start_avg'])) : '' ?>" placeholder="ex. 9,5"></label><span class="muted">/20</span>
      <button class="btn ghost sm"><?= icon('save') ?> Enregistrer</button></form>
    <?= grades_sparkline($sub['grades']) ?>

    <details class="t-sub" id="notes-<?= (int)$sub['id'] ?>"><summary>Notes (<?= count($sub['grades']) ?>)</summary>
      <ul class="list grade-list"><?php foreach (array_reverse($sub['grades']) as $g): ?>
        <li><span class="grade-pill"><?= fmt_grade((float)$g['value']) ?><small>/<?= fmt_grade((float)$g['out_of']) ?></small></span><div><?= e($g['label'] ?: 'Note') ?><br><small class="muted"><?= e(fdate($g['graded_on'])) ?><?= (float)$g['coef'] != 1 ? ' · coef ' . fmt_grade((float)$g['coef']) : '' ?><?= $g['author_role'] === 'student' ? ' · ajoutée par l\'élève' : ($g['author_role'] === 'parent' ? ' · ajoutée par le parent' : '') ?></small></div>
          <form method="post" action="<?= $act() ?>" class="inv-actions" data-confirm="Supprimer cette note ?"><?= $h(['action' => 'grade_del', 'grade_id' => $g['id'], 'subject_id' => $sub['id']]) ?><button class="edit-btn" aria-label="Supprimer"><?= icon('trash-2') ?></button></form></li>
      <?php endforeach ?></ul>
      <form method="post" action="<?= $act() ?>" class="stack"><?= $h(['action' => 'grade_add', 'subject_id' => $sub['id']]) ?>
        <div class="form-grid"><label>Note<input name="value" inputmode="decimal" required></label><label>Sur<input name="out_of" inputmode="decimal" value="20"></label><label>Coef<input name="coef" inputmode="decimal" value="1"></label><label>Date<input type="date" name="graded_on" value="<?= date('Y-m-d') ?>"></label></div>
        <label>Intitulé (facultatif)<input name="label" maxlength="120"></label>
        <button class="btn sm"><?= icon('plus') ?> Ajouter la note</button></form>
    </details>

    <details class="t-sub" open><summary>Chapitres (<?= count($sub['chapters']) ?>)</summary>
      <?php foreach ($sub['chapters'] as $c): $qs = chapter_questions((int)$c['id']); $att = one('SELECT score, total, created_at FROM chapter_attempts WHERE chapter_id=? ORDER BY id DESC LIMIT 1', [$c['id']]); ?>
        <div class="chap-edit<?= $c['is_current'] ? ' current' : '' ?>">
          <form method="post" action="<?= $act() ?>" class="chap-form"><?= $h(['action' => 'chapter_save', 'chapter_id' => $c['id']]) ?>
            <input name="title" value="<?= e($c['title']) ?>" maxlength="160" aria-label="Titre du chapitre">
            <select name="status" aria-label="Niveau"><?php foreach (CHAPTER_STATUS as $k => $l): ?><option value="<?= $k ?>" <?= $c['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select>
            <button class="btn ghost sm" title="Enregistrer"><?= icon('save') ?></button>
          </form>
          <details class="quiz-admin" id="quiz-<?= (int)$c['id'] ?>"><summary><?= icon('brain') ?> Quiz · <?= count($qs) ?> question<?= count($qs) > 1 ? 's' : '' ?><?= $att ? ' · dernier essai ' . (int)$att['score'] . '/' . (int)$att['total'] : '' ?></summary>
            <?php if (ai_available()): ?>
            <form method="post" action="<?= $act() ?>" class="ai-gen" data-ai-form><?= $h(['action' => 'question_ai', 'chapter_id' => $c['id']]) ?>
              <div class="ai-gen-row">
                <label>Questions<select name="count"><?php foreach ([3, 5, 8, 10] as $n): ?><option value="<?= $n ?>" <?= $n === 5 ? 'selected' : '' ?>><?= $n ?></option><?php endforeach ?></select></label>
                <label class="ai-focus">Consigne (facultatif)<input name="focus" maxlength="300" placeholder="ex. insister sur les fractions décimales"></label>
              </div>
              <button class="btn sm ai-btn"><?= icon('sparkles') ?> <span>Générer avec l'IA</span></button>
              <small class="muted">Adapté au chapitre et à la classe (<?= e(CLASSES[$s['grade']] ?? $s['grade']) ?>). Vous pourrez relire, modifier ou supprimer chaque question.</small>
            </form>
            <?php else: ?><p class="muted ai-off"><small><?= icon('sparkles') ?> Génération par IA : ajoutez une clé API dans <a href="<?= e(url('account')) ?>">Mon compte</a>.</small></p><?php endif ?>

            <ol class="qa-list"><?php foreach ($qs as $q): ?>
              <li class="qa-item">
                <div class="qa-view"><div><strong><?= e($q['question']) ?></strong><?php if (($q['source'] ?? '') === 'ai'): ?> <span class="badge sage ai-badge">IA</span><?php endif ?>
                  <ul class="qa-opts-view"><?php foreach ($q['options'] as $i => $o): ?><li class="<?= $i === (int)$q['answer'] ? 'ok' : '' ?>"><?= $i === (int)$q['answer'] ? icon('check') : '' ?><?= e($o) ?></li><?php endforeach ?></ul>
                  <?php if ($q['explanation']): ?><small class="muted"><?= e($q['explanation']) ?></small><?php endif ?></div>
                  <div class="qa-actions">
                    <button type="button" class="edit-btn" data-qa-edit title="Modifier" aria-label="Modifier la question"><?= icon('pencil') ?></button>
                    <form method="post" action="<?= $act() ?>" data-confirm="Supprimer cette question ?"><?= $h(['action' => 'question_del', 'question_id' => $q['id'], 'chapter_id' => $c['id']]) ?><button class="edit-btn" aria-label="Supprimer la question"><?= icon('trash-2') ?></button></form>
                  </div>
                </div>
                <form method="post" action="<?= $act() ?>" class="stack qa-form qa-edit" hidden><?= $h(['action' => 'question_update', 'question_id' => $q['id']]) ?>
                  <label>Question<input name="question" maxlength="500" required value="<?= e($q['question']) ?>"></label>
                  <div class="qa-opts"><?php for ($i = 0; $i < 4; $i++): ?><label class="qa-opt"><input type="radio" name="answer" value="<?= $i ?>" <?= $i === (int)$q['answer'] ? 'checked' : '' ?> aria-label="Bonne réponse"><input name="opt[]" maxlength="200" value="<?= e($q['options'][$i] ?? '') ?>" placeholder="Réponse <?= $i + 1 ?><?= $i < 2 ? '' : ' (facultatif)' ?>" <?= $i < 2 ? 'required' : '' ?>></label><?php endfor ?></div>
                  <small class="muted">Cochez la bonne réponse.</small>
                  <label>Explication si erreur (facultatif)<input name="explanation" maxlength="500" value="<?= e($q['explanation'] ?? '') ?>"></label>
                  <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-qa-edit>Annuler</button></div>
                </form>
              </li><?php endforeach ?></ol>

            <details class="qa-add"><summary><?= icon('plus') ?> Ajouter une question à la main</summary>
            <form method="post" action="<?= $act() ?>" class="stack qa-form"><?= $h(['action' => 'question_add', 'chapter_id' => $c['id']]) ?>
              <label>Question<input name="question" maxlength="500" required></label>
              <div class="qa-opts"><?php for ($i = 0; $i < 4; $i++): ?><label class="qa-opt"><input type="radio" name="answer" value="<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?> aria-label="Bonne réponse"><input name="opt[]" maxlength="200" placeholder="Réponse <?= $i + 1 ?><?= $i < 2 ? '' : ' (facultatif)' ?>" <?= $i < 2 ? 'required' : '' ?>></label><?php endfor ?></div>
              <small class="muted">Cochez la bonne réponse.</small>
              <label>Explication si erreur (facultatif)<input name="explanation" maxlength="500"></label>
              <button class="btn sm"><?= icon('plus') ?> Ajouter la question</button></form>
            </details>
          </details>
          <form method="post" action="<?= $act() ?>" class="chap-del" data-confirm="Supprimer ce chapitre et son quiz ?"><?= $h(['action' => 'chapter_del', 'chapter_id' => $c['id']]) ?><button class="more-btn">Supprimer le chapitre</button></form>
        </div>
      <?php endforeach ?>
      <form method="post" action="<?= $act() ?>" class="row chap-new"><?= $h(['action' => 'chapter_add', 'subject_id' => $sub['id']]) ?>
        <input name="title" maxlength="160" placeholder="Nouveau chapitre" required aria-label="Nouveau chapitre">
        <select name="status" aria-label="Niveau"><?php foreach (CHAPTER_STATUS as $k => $l): ?><option value="<?= $k ?>" <?= $k === 'in_progress' ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select>
        <button class="btn sm"><?= icon('plus') ?></button></form>
    </details>
  </div>
<?php endforeach ?>
</div>
</div>
<div class="kind-pane" data-kind-pane="skills" hidden>
<?php require __DIR__ . '/_t_skills.php' ?>
</div>

<h2 class="dash-sec">Suivi de l'élève</h2>
<div class="grid-2 two-cols dash-row">
  <div class="card<?= $unseen ? ' tone-orange' : '' ?>" id="blocages">
    <h2><?= icon('circle-help') ?> Blocages signalés<?= $unseen ? ' <span class="nav-badge">' . count($unseen) . '</span>' : '' ?></h2>
    <?php if (!$blockers): ?><p class="muted">Aucun blocage signalé par <?= e($s['first_name']) ?>.</p><?php endif ?>
    <ul class="list blocker-list"><?php foreach ($blockers as $b): ?>
      <li><?= icon($b['seen_at'] ? 'circle-check' : 'circle-help') ?><div><?= e($b['body']) ?><br><small class="muted"><?= e(fdate($b['created_at'], 'd/m H:i')) ?><?= $b['subject'] ? ' · ' . e($b['subject']) : '' ?><?= $b['chapter'] ? ' · ' . e($b['chapter']) : '' ?></small></div>
        <?php if (!$b['seen_at']): ?><form method="post" action="<?= $act() ?>" class="inv-actions"><?= $h(['action' => 'blocker_seen', 'blocker_id' => $b['id']]) ?><button class="btn ghost sm"><?= icon('check') ?> Vu</button></form><?php endif ?></li>
    <?php endforeach ?></ul>
  </div>

  <div class="card" id="fichiers">
    <h2><?= icon('download') ?> Fichiers déposés</h2>
    <form method="post" action="<?= $act() ?>" enctype="multipart/form-data" class="upload-row"><?= $h(['action' => 'file_upload']) ?>
      <select name="subject_id" aria-label="Matière"><option value="">Sans matière</option><?php foreach ($subjects as $sub): ?><option value="<?= (int)$sub['id'] ?>"><?= e($sub['name']) ?></option><?php endforeach ?></select>
      <label class="btn soft sm file-btn"><?= icon('plus') ?> Ajouter un fichier<input type="file" name="file" accept="image/*,application/pdf" required data-autosubmit-file></label>
    </form>
    <?php if (!$files): ?><p class="muted">Aucun fichier.</p><?php endif ?>
    <div class="file-grid"><?php foreach ($files as $f): $isImg = str_starts_with($f['mime'], 'image/') && $f['mime'] !== 'image/heic'; ?>
      <div class="file-item">
        <a href="<?= e(url('file', ['id' => $f['id']])) ?>" target="_blank" rel="noopener" class="file-thumb"><?php if ($isImg): ?><img src="<?= e(url('file', ['id' => $f['id']])) ?>" alt="" loading="lazy"><?php else: ?><span><?= icon('file-text') ?></span><?php endif ?></a>
        <div class="file-meta"><strong title="<?= e($f['original']) ?>"><?= e($f['note'] ?: $f['original']) ?></strong><small class="muted"><?= e(fdate($f['created_at'])) ?><?= $f['subject'] ? ' · ' . e($f['subject']) : '' ?> · <?= e(ROLE_LABELS[$f['author_role']] ?? '') ?></small></div>
        <form method="post" action="<?= $act() ?>" data-confirm="Supprimer ce fichier ?"><?= $h(['action' => 'file_del', 'file_id' => $f['id']]) ?><button class="edit-btn" aria-label="Supprimer"><?= icon('trash-2') ?></button></form>
      </div>
    <?php endforeach ?></div>
  </div>
</div>
