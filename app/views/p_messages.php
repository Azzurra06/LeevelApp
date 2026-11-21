<?php $title = 'Échanges'; $me = $u; $postUrl = url('p_messages', ['sid' => $s['id']]); ?>
<div class="page-head"><div><h1><?= icon('message-circle') ?> Échanges</h1><p>Vos échanges avec la tutrice au sujet de <?= e($s['first_name']) ?>.</p></div></div>
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
  <section class="card"><h2>Messagerie</h2><?php require __DIR__ . '/_thread.php' ?></section>
</div>
