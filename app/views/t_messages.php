<?php $title = 'Messagerie'; $me = current_user(); $channel = $ch; $postUrl = url('t_messages', ['sid' => $s['id']] + ($ch === 'student' ? ['ch' => 'student'] : [])); ?>
<div class="msg-layout msg-single">
  <section class="card chat-card">
    <div class="chat-top"><div class="row between thread-head"><h2><?= e($s['family_name']) ?></h2>
      <?php if ($parent): ?><div class="row"><?php if (!empty($parent['phone'])): ?><a class="btn ghost sm" title="Appeler" aria-label="Appeler le parent" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $parent['phone'])) ?>"><?= icon('message-circle') ?> <span class="lbl"><?= e($parent['phone']) ?></span></a><?php endif ?>
        <?php if (!empty($parent['email'])): ?><a class="btn ghost sm" title="E-mail" aria-label="Écrire un e-mail au parent" href="mailto:<?= e($parent['email']) ?>"><?= icon('file-text') ?> <span class="lbl">E-mail</span></a><?php endif ?></div><?php endif ?></div>
    <?php if (!$parent): ?><p class="alert warn">Le compte parent n'est pas encore créé : il verra vos messages dès sa première connexion.</p><?php endif ?>
    <?php if (messages_have_channels()): ?><div class="seg seg-tight" role="tablist">
      <a href="<?= e(url('t_messages', ['sid' => $s['id']])) ?>" class="<?= $ch === 'family' ? 'on' : '' ?>" role="tab"><?= icon('users') ?> Parent<?= $unreadCh['family'] ? ' <span class="nav-badge">' . $unreadCh['family'] . '</span>' : '' ?></a>
      <a href="<?= e(url('t_messages', ['sid' => $s['id'], 'ch' => 'student'])) ?>" class="<?= $ch === 'student' ? 'on' : '' ?>" role="tab"><?= icon('smile') ?> <?= e($s['first_name']) ?><?= $unreadCh['student'] ? ' <span class="nav-badge">' . $unreadCh['student'] . '</span>' : '' ?></a>
    </div><?php endif ?></div>
    <?php if ($ch === 'student'): ?>
      <?php if (!$studentU): ?><p class="alert warn">Le compte de <?= e($s['first_name']) ?> n'est pas encore créé : il verra vos messages dès sa première connexion.</p>
      <?php else: ?><p class="muted"><small><?= icon('eye') ?> Le parent peut lire cette discussion depuis son espace.</small></p><?php endif ?>
    <?php endif ?>
    <?php require __DIR__ . '/_thread.php' ?>
  </section>
</div>
