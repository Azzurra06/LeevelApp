<?php $title = 'Échanges'; $me = current_user(); $postUrl = url('t_messages', ['sid' => $s['id']]); ?>
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
  <section class="card">
    <div class="row between thread-head"><h2><?= e($s['family_name']) ?></h2>
      <?php if ($parent): ?><div class="row"><?php if (!empty($parent['phone'])): ?><a class="btn ghost sm" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $parent['phone'])) ?>"><?= icon('message-circle') ?> <?= e($parent['phone']) ?></a><?php endif ?>
        <?php if (!empty($parent['email'])): ?><a class="btn ghost sm" href="mailto:<?= e($parent['email']) ?>"><?= icon('file-text') ?> E-mail</a><?php endif ?></div><?php endif ?></div>
    <?php if (!$parent): ?><p class="alert warn">Le compte parent n'est pas encore créé : il verra vos messages dès sa première connexion.</p><?php endif ?>
    <?php require __DIR__ . '/_thread.php' ?>
  </section>
</div>
