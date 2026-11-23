<?php // Fil de messages. $messages, $me (utilisateur courant), $postUrl ; facultatif : $channel, $readonly
$readonly ??= false; $channel ??= 'family';
$att = function_exists('message_files_for') ? message_files_for(array_column($messages, 'id')) : [];
$fsize = fn(int $b) => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '') . ' Mo' : max(1, (int)round($b / 1024)) . ' Ko'; ?>
<div class="thread" data-thread>
  <?php if (!$messages): ?><p class="muted thread-empty"><?= $readonly ? 'Aucun message pour l\'instant.' : ($me['role'] === 'student' ? 'Aucun message pour l\'instant. Écris le premier !' : 'Aucun message pour l\'instant. Écrivez le premier !') ?></p><?php endif ?>
  <?php $prevDay = null; foreach ($messages as $m): $mine = (int)$m['sender_id'] === (int)$me['id']; $day = substr($m['created_at'], 0, 10); ?>
    <?php if ($day !== $prevDay): $prevDay = $day; ?><div class="thread-day"><?= e(fdate($m['created_at'], 'd/m/Y')) ?></div><?php endif ?>
    <?php if (($m['kind'] ?? null) === 'system'): ?>
    <div class="msg-sys"><span class="msg-sys-ic"><?= icon('sparkles') ?></span><div><span class="msg-sys-from">Notification automatique<?= $me['role'] === 'tutor' ? ' · envoyée au parent' : '' ?></span><div><?= nl2br(e($m['body'])) ?></div><span class="msg-time"><?= e(date('H\hi', strtotime($m['created_at']))) ?></span></div></div>
    <?php continue; endif ?>
    <div class="msg <?= $mine ? 'mine' : '' ?>">
      <?php if (!$mine): ?><?= avatar_html(['id' => $m['uid'], 'avatar' => $m['avatar'], 'display_name' => $m['display_name'] ?? '?'], 'sm') ?><?php endif ?>
      <div class="bubble">
        <?php if (!$mine): ?><span class="msg-from"><?= e($m['display_name'] ?? 'Compte supprimé') ?></span><?php endif ?>
        <?php if (trim((string)$m['body']) !== ''): ?><div><?= nl2br(e($m['body'])) ?></div><?php endif ?>
        <?php if (!empty($att[(int)$m['id']])): ?><div class="msg-files"><?php foreach ($att[(int)$m['id']] as $f): $furl = url('msg_file', ['id' => $f['id']]); $dlurl = url('msg_file', ['id' => $f['id'], 'dl' => 1]); $isPdf = $f['mime'] === 'application/pdf'; ?>
          <?php if (in_array($f['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)): ?><a class="msg-img" href="<?= e($furl) ?>" data-lightbox="img" data-dl="<?= e($dlurl) ?>" data-name="<?= e($f['original']) ?>"><img src="<?= e($furl) ?>" alt="<?= e($f['original']) ?>" loading="lazy"></a>
          <?php else: ?><a class="msg-file" href="<?= e($isPdf ? url('msg_file', ['id' => $f['id'], 'embed' => 1]) : $dlurl) ?>"<?= $isPdf ? ' data-lightbox="pdf" data-dl="' . e($dlurl) . '" data-name="' . e($f['original']) . '"' : ' download="' . e($f['original']) . '"' ?>><?= icon('file-text') ?><span><strong><?= e($f['original']) ?></strong><small><?= e(strtoupper(pathinfo($f['stored'], PATHINFO_EXTENSION))) ?> · <?= $fsize((int)$f['size']) ?></small></span><?= icon('download', 'msg-file-dl') ?></a><?php endif ?>
        <?php endforeach ?></div><?php endif ?>
        <span class="msg-time"><?= e(date('H\hi', strtotime($m['created_at']))) ?><?= $mine && $m['read_at'] ? ' · lu' : '' ?></span>
      </div>
    </div>
  <?php endforeach ?>
</div>
<?php if (!$readonly): ?>
<form method="post" action="<?= e($postUrl) ?>" class="msg-form" enctype="multipart/form-data" data-msg-form><?= csrf_field() ?><?php if ($channel === 'student'): ?><input type="hidden" name="ch" value="student"><?php endif ?>
  <div class="msg-attach-list" data-attach-list hidden></div>
  <div class="msg-form-row">
    <label class="msg-attach" title="Joindre une photo ou un fichier" aria-label="Joindre une photo ou un fichier"><?= icon('paperclip') ?><input type="file" name="files[]" multiple data-attach
      accept="image/*,.heic,.heif,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.txt,audio/*,video/mp4,video/quicktime"></label>
    <textarea name="body" maxlength="4000" rows="1" placeholder="Écrire un message…" aria-label="Message" data-autogrow></textarea>
    <button class="btn" aria-label="Envoyer"><?= icon('send') ?><span class="lbl">Envoyer</span></button>
  </div>
</form>
<?php endif ?>
