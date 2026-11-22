<?php $title = 'Échanges'; $me = $u; $postUrl = url('p_messages', ['sid' => $s['id']]); $readonly = $ch === 'student'; $channel = $ch; ?>
<div class="page-head"><div><h1><?= icon('message-circle') ?> Échanges</h1><p>Vos échanges avec la tutrice au sujet de <?= e(kid($s['first_name'])) ?>.</p></div></div>
<div class="msg-layout">
  <aside class="card contact-card">
    <h2>Votre tutrice</h2>
    <?php if ($tutor): ?>
      <div class="contact-head"><?= avatar_html($tutor, 'lg') ?><strong><?= e($tutor['display_name']) ?></strong></div>
      <ul class="list">
        <li><?= icon('message-circle') ?><div><small class="muted">Téléphone</small><br><?= !empty($tutor['phone']) ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $tutor['phone'])) . '">' . e($tutor['phone']) . '</a>' : '<span class="muted">Non renseigné</span>' ?></div></li>
        <li><?= icon('file-text') ?><div><small class="muted">E-mail</small><br><?= !empty($tutor['email']) ? '<a href="mailto:' . e($tutor['email']) . '">' . e($tutor['email']) . '</a>' : '<span class="muted">Non renseigné</span>' ?></div></li>
      </ul>
    <?php endif ?>
    <p class="muted"><small>Pour une absence ou un imprévu, prévenez de préférence par message ou par téléphone.</small></p>
  </aside>
  <section class="card chat-card">
    <div class="chat-top"><div class="chat-head"><?= $tutor ? avatar_html($tutor, 'sm') : '' ?><div><strong><?= e($tutor['display_name'] ?? 'La tutrice') ?></strong><small><?= $readonly ? icon('eye') . ' Discussion de ' . e(kid($s['first_name'])) . ' · lecture seule' : 'Au sujet de ' . e(kid($s['first_name'])) ?></small></div>
      <?php if (!empty($tutor['phone'])): ?><a class="chat-call" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $tutor['phone'])) ?>" aria-label="Appeler la tutrice"><?= icon('message-circle') ?></a><?php endif ?></div>
    <?php if ($s['user_id'] && messages_have_channels()): ?><div class="seg seg-tight" role="tablist">
      <a href="<?= e(url('p_messages', ['sid' => $s['id']])) ?>" class="<?= $ch === 'family' ? 'on' : '' ?>" role="tab"><?= icon('message-circle') ?> Avec la tutrice</a>
      <a href="<?= e(url('p_messages', ['sid' => $s['id'], 'ch' => 'student'])) ?>" class="<?= $ch === 'student' ? 'on' : '' ?>" role="tab"><?= icon('eye') ?> Fil de <?= e(kid($s['first_name'])) ?></a>
    </div><?php endif ?></div>
    <?php if ($readonly): ?><p class="alert ro-note"><?= icon('eye') ?> Discussion entre <?= e(kid($s['first_name'])) ?> et la tutrice, en lecture seule. <?= e(kid($s['first_name'])) ?> sait que vous pouvez la lire.</p><?php endif ?>
    <?php require __DIR__ . '/_thread.php' ?></section>
</div>
