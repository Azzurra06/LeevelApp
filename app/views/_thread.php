<?php // Fil de messages. $messages, $me (utilisateur courant), $postUrl ; facultatif : $channel, $readonly
$readonly ??= false; $channel ??= 'family'; ?>
<div class="thread" data-thread>
  <?php if (!$messages): ?><p class="muted thread-empty"><?= $readonly ? 'Aucun message pour l\'instant.' : ($me['role'] === 'student' ? 'Aucun message pour l\'instant. Écris le premier !' : 'Aucun message pour l\'instant. Écrivez le premier !') ?></p><?php endif ?>
  <?php $prevDay = null; foreach ($messages as $m): $mine = (int)$m['sender_id'] === (int)$me['id']; $day = substr($m['created_at'], 0, 10); ?>
    <?php if ($day !== $prevDay): $prevDay = $day; ?><div class="thread-day"><?= e(fdate($m['created_at'], 'd/m/Y')) ?></div><?php endif ?>
    <div class="msg <?= $mine ? 'mine' : '' ?>">
      <?php if (!$mine): ?><?= avatar_html(['id' => $m['uid'], 'avatar' => $m['avatar'], 'display_name' => $m['display_name'] ?? '?'], 'sm') ?><?php endif ?>
      <div class="bubble">
        <?php if (!$mine): ?><span class="msg-from"><?= e($m['display_name'] ?? 'Compte supprimé') ?></span><?php endif ?>
        <div><?= nl2br(e($m['body'])) ?></div>
        <span class="msg-time"><?= e(date('H\hi', strtotime($m['created_at']))) ?><?= $mine && $m['read_at'] ? ' · lu' : '' ?></span>
      </div>
    </div>
  <?php endforeach ?>
</div>
<?php if (!$readonly): ?>
<form method="post" action="<?= e($postUrl) ?>" class="msg-form"><?= csrf_field() ?><?php if ($channel === 'student'): ?><input type="hidden" name="ch" value="student"><?php endif ?>
  <textarea name="body" required maxlength="4000" rows="2" placeholder="Écrire un message…" aria-label="Message"></textarea>
  <button class="btn"><?= icon('send') ?> Envoyer</button>
</form>
<?php endif ?>
