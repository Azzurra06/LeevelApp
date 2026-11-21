<?php $title = 'Messages'; $me = $u; $postUrl = url('s_messages'); $channel = 'student'; ?>
<div class="page-head"><div><h1><?= icon('message-circle') ?> Messages</h1><p>Pose tes questions à ta tutrice, quand tu veux.</p></div></div>
<div class="msg-layout">
  <aside class="card contact-card">
    <?php if ($tutor): ?><div class="contact-head"><?= avatar_html($tutor, 'lg') ?><strong><?= e($tutor['display_name']) ?></strong><small class="muted">Ta tutrice</small></div><?php endif ?>
    <ul class="list tips">
      <li><span>💡</span><div><small>Une question sur un exercice ? Envoie aussi une photo dans <a href="<?= e(url('s_subjects')) ?>#fichiers">Mes matières</a>.</small></div></li>
      <li><span>👀</span><div><small>Ton parent peut lire cette discussion.</small></div></li>
    </ul>
  </aside>
  <section class="card"><h2><?= icon('message-circle') ?> Discussion avec ta tutrice</h2><?php require __DIR__ . '/_thread.php' ?></section>
</div>
