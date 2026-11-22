<?php $title = 'Mes matières';
$moodEmo = ['good' => '😄', 'ok' => '😐', 'hard' => '😣'];
$subjUrl = fn(array $x) => url('s_subjects', ['subj' => $x['id']]);
$hiddenSubj = fn() => csrf_field() . ($cur ? '<input type="hidden" name="subj" value="' . (int)$cur['id'] . '">' : ''); ?>
<div class="page-head"><div><h1><?= icon('book-open') ?> Mes matières</h1><p>Tes notes, tes chapitres et tout ce qu'on travaille ensemble.</p></div></div>

<?php $fb = $recap['feedback'] ?? null; $rep = $recap['report'] ?? null; ?>
<div class="card tone-blue editable<?= $recap && !$fb ? ' editing' : '' ?>" id="recap">
  <div class="ed-head"><h2><?= icon('file-text') ?> Bilan de la dernière séance</h2><?= $fb ? '<button type="button" class="btn ghost sm" data-edit>' . icon('pencil') . ' Modifier</button>' : '' ?></div>
  <?php if (!$recap): ?>
    <div class="empty-ill sm"><span>📅</span><p class="muted">Après ta première séance, tu trouveras ici le bilan de ta tutrice et tu pourras lui dire comment ça s'est passé.</p></div>
  <?php else: ?>
    <p class="recap-date"><span class="badge"><?= e(fdatetime($recap['starts_at'])) ?></span> <span class="muted"><?= e(session_label($recap)) ?></span></p>
    <div class="recap-note">
      <h3 class="mini-h"><?= icon('message-circle') ?> Le mot de ta tutrice</h3>
      <?php if ($rep && $rep['worked_on']): ?><p><?= nl2br(e($rep['worked_on'])) ?></p>
      <?php else: ?><p class="muted">Ta tutrice n'a pas encore écrit le bilan de cette séance.</p><?php endif ?>
      <?php if ($rep && $rep['next_focus']): ?><p class="recap-next"><strong>Pour la prochaine fois :</strong> <?= e($rep['next_focus']) ?></p><?php endif ?>
    </div>
    <div class="recap-me view">
      <h3 class="mini-h"><?= icon('smile') ?> Mon retour</h3>
      <?php if ($fb): ?>
        <p class="recap-mood"><span><?= $moodEmo[$fb['mood']] ?? '🙂' ?></span> <?= e(MOODS[$fb['mood']] ?? 'Retour envoyé') ?></p>
        <?php $myNote = $fb['note'] ?? $fb['understood'] ?? null; if ($myNote): ?><p class="recap-mynote">« <?= e($myNote) ?> »</p><?php endif ?>
      <?php endif ?>
    </div>
    <form method="post" action="<?= e(url('s_feedback')) ?>" class="stack edit-form recap-form"><?= $hiddenSubj() ?><input type="hidden" name="back" value="s_subjects"><input type="hidden" name="session_id" value="<?= (int)$recap['id'] ?>">
      <span class="ql">Comment ça s'est passé ?</span>
      <div class="mood-row mood-pick"><?php foreach (['good' => 'Bien', 'ok' => 'Moyen', 'hard' => 'Difficile'] as $k => $l): ?><label class="mood"><input type="radio" name="mood" value="<?= $k ?>" required <?= ($fb['mood'] ?? '') === $k ? 'checked' : '' ?>><span><?= $moodEmo[$k] ?></span><small><?= $l ?></small></label><?php endforeach ?></div>
      <label>Un petit mot pour ta tutrice ? <span class="q-hint">facultatif</span><textarea name="note" maxlength="1000" rows="2" placeholder="ex. J'ai bien compris les fractions, mais pas encore les équations"><?= e($fb['note'] ?? '') ?></textarea></label>
      <div class="row"><button class="btn sm"><?= icon('send') ?> Envoyer</button><?php if ($fb): ?><button type="button" class="btn ghost sm" data-edit>Annuler</button><?php endif ?></div>
    </form>
  <?php endif ?>
</div>

<?php if (!$subjects): ?>
  <div class="card"><div class="empty-ill"><span>📚</span><p><strong>Tes matières arrivent bientôt</strong><br><small class="muted">Ta tutrice va ajouter les matières que vous travaillez ensemble. Tu pourras ensuite y noter tes résultats, voir tes chapitres et t'entraîner.</small></p></div></div>
<?php return; endif ?>

<nav class="subj-chips" aria-label="Matières">
  <?php foreach ($subjects as $x): ?>
    <a href="<?= e($subjUrl($x)) ?>" class="subj-chip<?= (int)$x['id'] === (int)$cur['id'] ? ' on' : '' ?>"<?= (int)$x['id'] === (int)$cur['id'] ? ' aria-current="page"' : '' ?>>
      <strong><?= e($x['name']) ?></strong><small><?= $x['avg'] !== null ? fmt_grade($x['avg']) . '/20' : 'pas de note' ?></small></a>
  <?php endforeach ?>
</nav>

<div class="grid-2">
  <div class="card" id="notes">
    <h2><?= icon('chart-column') ?> Mes notes</h2>
    <div class="avg-row">
      <div class="avg-big"><strong><?= fmt_grade($cur['avg']) ?></strong><small>/20 de moyenne</small></div>
      <?php if ($cur['trend']): ?><span class="trend <?= $cur['trend'] > 0 ? 'up' : 'down' ?>"><?= $cur['trend'] > 0 ? '↗ En progrès' : '↘ En baisse' ?></span><?php endif ?>
    </div>
    <?= grades_sparkline($cur['grades']) ?>
    <?php if (!$cur['grades']): ?><p class="muted">Ajoute ta première note : on pourra voir ton évolution.</p><?php endif ?>
    <ul class="list grade-list"><?php foreach (array_slice(array_reverse($cur['grades']), 0, 5) as $g): ?>
      <li><span class="grade-pill"><?= fmt_grade((float)$g['value']) ?><small>/<?= fmt_grade((float)$g['out_of']) ?></small></span>
        <div><?= e($g['label'] ?: 'Note') ?><br><small class="muted"><?= e(fdate($g['graded_on'])) ?><?= (float)$g['coef'] != 1 ? ' · coef ' . fmt_grade((float)$g['coef']) : '' ?></small></div>
        <?php if ((int)$g['created_by'] === (int)$u['id']): ?><form method="post" class="inv-actions" data-confirm="Supprimer cette note ?"><?= $hiddenSubj() ?><input type="hidden" name="action" value="grade_del"><input type="hidden" name="grade_id" value="<?= (int)$g['id'] ?>"><button class="edit-btn" title="Supprimer" aria-label="Supprimer"><?= icon('trash-2') ?></button></form><?php endif ?></li>
    <?php endforeach ?></ul>
    <details class="add-test"><summary><?= icon('plus') ?> Ajouter une note</summary>
      <form method="post" class="stack"><?= $hiddenSubj() ?><input type="hidden" name="action" value="grade_add">
        <div class="form-grid">
          <label>Note<input name="value" inputmode="decimal" required placeholder="ex. 14,5"></label>
          <label>Sur<input name="out_of" inputmode="decimal" value="20"></label>
          <label>Date<input type="date" name="graded_on" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></label>
        </div>
        <label>C'était quoi ? (facultatif)<input name="label" maxlength="120" placeholder="ex. Contrôle chapitre 3"></label>
        <button class="btn sm"><?= icon('plus') ?> Ajouter</button>
      </form></details>
  </div>

  <div class="card" id="chapitres">
    <h2><?= icon('list-checks') ?> Mes chapitres</h2>
    <?php if (!$cur['chapters']): ?><p class="muted">Les chapitres vus en cours s'afficheront ici, avec ton niveau pour chacun.</p><?php endif ?>
    <ul class="chap-list"><?php foreach ($cur['chapters'] as $c): ?>
      <li class="<?= $c['is_current'] ? 'current' : '' ?>"><span class="chap-dot <?= e(CHAPTER_TONE[$c['status']]) ?>"></span>
        <div><strong><?= e($c['title']) ?></strong><?php if ($c['is_current']): ?><small>En ce moment en cours</small><?php endif ?></div>
        <span class="badge <?= e(CHAPTER_TONE[$c['status']]) ?>"><?= e(CHAPTER_STATUS[$c['status']]) ?></span></li>
    <?php endforeach ?></ul>
    <?php if ($cur['chapters']): ?><p class="chap-legend"><?php foreach (CHAPTER_STATUS as $k => $l): ?><span><i class="chap-dot <?= CHAPTER_TONE[$k] ?>"></i><?= e($l) ?></span><?php endforeach ?></p><?php endif ?>
  </div>

  <div class="card" id="quiz">
    <h2><?= icon('brain') ?> Je teste mes connaissances</h2>
    <?php if (!$current): ?><p class="muted">Pas encore de chapitre en cours dans cette matière.</p>
    <?php elseif (!$questions): ?><p><strong><?= e($current['title']) ?></strong></p><p class="muted">Ta tutrice prépare un petit quiz sur ce chapitre. Reviens bientôt !</p>
    <?php else: $res = $quizResult && (int)$quizResult['chapter_id'] === (int)$current['id'] ? $quizResult : null; ?>
      <p class="muted">Chapitre : <strong><?= e($current['title']) ?></strong> · <?= count($questions) ?> question<?= count($questions) > 1 ? 's' : '' ?></p>
      <?php if ($res): $pct = $res['total'] ? $res['score'] / $res['total'] : 0; ?>
        <div class="quiz-score"><span><?= $pct >= .8 ? '🎉' : ($pct >= .5 ? '👍' : '💪') ?></span><div><strong><?= $res['score'] ?> / <?= $res['total'] ?></strong><small><?= $pct >= .8 ? 'Excellent, tu maîtrises !' : ($pct >= .5 ? 'Pas mal ! Revois les erreurs ci-dessous.' : 'On va retravailler ça ensemble, ne lâche rien.') ?></small></div></div>
        <ol class="quiz-review"><?php foreach ($questions as $q): $d = $res['detail'][$q['id']] ?? ['given' => null, 'ok' => false]; ?>
          <li class="<?= $d['ok'] ? 'ok' : 'ko' ?>"><strong><?= e($q['question']) ?></strong>
            <small><?= $d['ok'] ? '✓ Bonne réponse' : '✗ Réponse attendue : ' . e($q['options'][$q['answer']] ?? '') ?></small>
            <?php if (!$d['ok'] && $q['explanation']): ?><small class="muted"><?= e($q['explanation']) ?></small><?php endif ?></li>
        <?php endforeach ?></ol>
        <a class="btn ghost sm" href="<?= e(url('s_subjects', ['subj' => $cur['id'], 'quiz' => 'nouveau'])) ?>#quiz"><?= icon('refresh-cw') ?> Recommencer</a>
      <?php else: ?>
        <?php if ($lastAttempt): ?><p><span class="badge sage">Dernier essai : <?= (int)$lastAttempt['score'] ?> / <?= (int)$lastAttempt['total'] ?></span></p><?php endif ?>
        <form method="post" class="quiz stack"><?= $hiddenSubj() ?><input type="hidden" name="action" value="quiz"><input type="hidden" name="chapter_id" value="<?= (int)$current['id'] ?>">
          <?php foreach ($questions as $i => $q): ?>
            <div class="quiz-q" role="radiogroup" aria-labelledby="qq-<?= (int)$q['id'] ?>"><p class="quiz-ql" id="qq-<?= (int)$q['id'] ?>"><?= $i + 1 ?>. <?= e($q['question']) ?></p>
              <div class="pills"><?php foreach ($q['options'] as $k => $o): ?><label class="pill"><input type="radio" name="q[<?= (int)$q['id'] ?>]" value="<?= $k ?>" required><span><?= e($o) ?></span></label><?php endforeach ?></div>
            </div>
          <?php endforeach ?>
          <button class="btn"><?= icon('check') ?> Valider mes réponses</button>
        </form>
      <?php endif ?>
    <?php endif ?>
  </div>

  <div class="card" id="blocage">
    <h2><?= icon('circle-help') ?> Là où je bloque</h2>
    <form method="post" class="stack"><?= $hiddenSubj() ?><input type="hidden" name="action" value="blocker">
      <?php if ($cur['chapters']): ?><label>Sur quel chapitre ? (facultatif)<select name="chapter_id"><option value="">Choisir…</option><?php foreach ($cur['chapters'] as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $current && (int)$current['id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach ?></select></label><?php endif ?>
      <label>Qu'est-ce qui te pose problème ?<textarea name="body" maxlength="1000" rows="2" required placeholder="ex. Je ne comprends pas comment factoriser"></textarea></label>
      <button class="btn sm"><?= icon('send') ?> Prévenir ma tutrice</button>
    </form>
    <?php if ($blockers): ?><ul class="list blocker-list"><?php foreach ($blockers as $b): ?>
      <li><?= icon($b['seen_at'] ? 'circle-check' : 'clock') ?><div><?= e($b['body']) ?><br><small class="muted"><?= e(fdate($b['created_at'])) ?><?= $b['chapter'] ? ' · ' . e($b['chapter']) : '' ?> · <?= $b['seen_at'] ? 'Vu par ta tutrice' : 'Envoyé' ?></small></div></li>
    <?php endforeach ?></ul><?php endif ?>
  </div>

  <div class="card span-2" id="fichiers">
    <h2><?= icon('download') ?> Mes fichiers</h2>
    <form method="post" enctype="multipart/form-data" class="upload-row"><?= $hiddenSubj() ?><input type="hidden" name="action" value="file_upload">
      <input name="note" maxlength="300" placeholder="Un mot sur ce fichier ? (facultatif)" aria-label="Un mot sur ce fichier">
      <label class="btn file-btn"><?= icon('plus') ?> Envoyer une photo ou un PDF<input type="file" name="file" accept="image/*,application/pdf" required data-autosubmit-file></label>
    </form>
    <p class="muted"><small>Photo d'un exercice, d'une copie corrigée, d'une leçon… 10 Mo maximum. Visible par ta tutrice.</small></p>
    <?php if (!$files): ?><p class="muted">Aucun fichier pour cette matière.</p><?php endif ?>
    <div class="file-grid"><?php foreach ($files as $f): $isImg = str_starts_with($f['mime'], 'image/') && $f['mime'] !== 'image/heic'; ?>
      <div class="file-item">
        <a href="<?= e(url('file', ['id' => $f['id']])) ?>" target="_blank" rel="noopener" class="file-thumb"><?php if ($isImg): ?><img src="<?= e(url('file', ['id' => $f['id']])) ?>" alt="" loading="lazy"><?php else: ?><span><?= icon('file-text') ?></span><?php endif ?></a>
        <div class="file-meta"><strong title="<?= e($f['original']) ?>"><?= e($f['note'] ?: $f['original']) ?></strong><small class="muted"><?= e(fdate($f['created_at'])) ?><?= $f['author_role'] === 'tutor' ? ' · de ta tutrice' : '' ?></small></div>
        <?php if ((int)$f['user_id'] === (int)$u['id']): ?><form method="post" data-confirm="Supprimer ce fichier ?"><?= $hiddenSubj() ?><input type="hidden" name="action" value="file_del"><input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>"><button class="edit-btn" title="Supprimer" aria-label="Supprimer"><?= icon('trash-2') ?></button></form><?php endif ?>
      </div>
    <?php endforeach ?></div>
  </div>
</div>
