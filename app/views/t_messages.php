<?php $title = 'Échanges'; $me = current_user(); $channel = $ch; $postUrl = url('t_messages', ['sid' => $s['id']] + ($ch === 'student' ? ['ch' => 'student'] : [])); ?>
<div class="page-head"><div><h1><?= icon('message-circle') ?> Échanges</h1><p>Messagerie avec les familles.</p></div></div>
<div class="msg-layout">
  <aside class="card thread-list">
    <h2>Familles</h2>
    <ul class="list">
      <?php foreach ($threads as $th): $x = $th['s']; ?>
        <li class="li-link<?= (int)$x['id'] === (int)$s['id'] ? ' current' : '' ?>"><a href="<?= e(url('t_messages', ['sid' => $x['id']])) ?>">
          <div><strong><?= e($x['first_name']) ?></strong><?php if ($th['unread']): ?> <span class="nav-badge"><?= $th['unread'] ?></span><?php endif ?><br>
            <small class="muted"><?= $th['last'] ? e(mb_strimwidth($th['last']['body'], 0, 48, '…')) : 'Aucun message' ?></small></div></a></li>
      <?php endforeach ?>
    </ul>
  </aside>
  <section class="card chat-card">
    <div class="chat-top"><div class="row between thread-head"><h2><?= e($s['family_name']) ?></h2>
      <?php if ($parent): ?><div class="row"><?php if (!empty($parent['phone'])): ?><a class="btn ghost sm" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $parent['phone'])) ?>"><?= icon('message-circle') ?> <?= e($parent['phone']) ?></a><?php endif ?>
        <?php if (!empty($parent['email'])): ?><a class="btn ghost sm" href="mailto:<?= e($parent['email']) ?>"><?= icon('file-text') ?> E-mail</a><?php endif ?></div><?php endif ?></div>
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
