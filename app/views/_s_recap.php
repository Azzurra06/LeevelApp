<?php // Élève : bilan de la dernière séance + son retour. $recap
$moodEmo = ['good' => '😄', 'ok' => '😐', 'hard' => '😣']; ?>
<?php $fb = $recap['feedback'] ?? null; $rep = $recap['report'] ?? null; ?>
<div class="card tone-blue editable<?= $recap && !$fb ? ' editing' : '' ?>" id="recap">
  <div class="ed-head"><h2><?= icon('file-text') ?> Bilan de la dernière séance</h2><?= $fb ? '<button type="button" class="btn ghost sm" data-edit>' . icon('pencil') . ' Modifier</button>' : '' ?></div>
  <?php if (!$recap): ?>
    <div class="empty-ill sm"><span>📅</span><p class="muted">Après ta première séance, tu trouveras ici le bilan de ta tutrice et tu pourras lui dire comment ça s'est passé.</p></div>
  <?php else: ?>
    <p class="recap-date"><span class="badge"><?= e(fdatetime($recap['starts_at'])) ?></span> <span class="muted"><?= e(session_label($recap)) ?></span></p>
    <?php ob_start() ?>
    <div class="recap-note">
      <h3 class="mini-h"><?= icon('message-circle') ?> Le mot de ta tutrice</h3>
      <?= report_subject_chips($rep) ?>
      <?php if ($rep && $rep['worked_on']): ?><p><?= nl2br(e($rep['worked_on'])) ?></p>
      <?php else: ?><p class="muted">Ta tutrice n'a pas encore écrit le bilan de cette séance.</p><?php endif ?>
      <?php if ($rep && $rep['next_focus']): ?><p class="recap-next"><strong>Pour la prochaine fois :</strong> <?= e($rep['next_focus']) ?></p><?php endif ?>
    </div>
    <?php $noteHtml = ob_get_clean(); if ($rep) echo $noteHtml; ?>
    <div class="recap-me view">
      <h3 class="mini-h"><?= icon('smile') ?> Mon retour</h3>
      <?php if ($fb): ?>
        <p class="recap-mood"><span><?= $moodEmo[$fb['mood']] ?? '🙂' ?></span> <?= e(MOODS[$fb['mood']] ?? 'Retour envoyé') ?></p>
        <?php $myNote = $fb['note'] ?? $fb['understood'] ?? null; if ($myNote): ?><p class="recap-mynote">« <?= e($myNote) ?> »</p><?php endif ?>
      <?php endif ?>
    </div>
    <form method="post" action="<?= e(url('s_feedback')) ?>" class="stack edit-form recap-form"><?= csrf_field() ?><input type="hidden" name="back" value="s_planning"><input type="hidden" name="session_id" value="<?= (int)$recap['id'] ?>">
      <span class="ql">Comment ça s'est passé ?</span>
      <div class="mood-row mood-pick"><?php foreach (['good' => 'Bien', 'ok' => 'Moyen', 'hard' => 'Difficile'] as $k => $l): ?><label class="mood"><input type="radio" name="mood" value="<?= $k ?>" required <?= ($fb['mood'] ?? '') === $k ? 'checked' : '' ?>><span><?= $moodEmo[$k] ?></span><small><?= $l ?></small></label><?php endforeach ?></div>
      <label>Un petit mot pour ta tutrice ? <span class="q-hint">facultatif</span><textarea name="note" maxlength="1000" rows="2" placeholder="ex. J'ai bien compris les fractions, mais pas encore les équations"><?= e($fb['note'] ?? '') ?></textarea></label>
      <div class="row"><button class="btn sm"><?= icon('send') ?> Envoyer</button><?php if ($fb): ?><button type="button" class="btn ghost sm" data-edit>Annuler</button><?php endif ?></div>
    </form>
    <?php if (!$rep): ?><p class="muted recap-wait"><small>Ta tutrice n'a pas encore écrit son bilan : tu peux déjà donner ton retour, tu verras son mot ici ensuite.</small></p><?php endif ?>
  <?php endif ?>
</div>
