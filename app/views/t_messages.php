<?php $title = 'Messagerie'; $me = current_user(); $channel = $ch; $postUrl = url('t_messages', ['sid' => $s['id']] + ($ch === 'student' ? ['ch' => 'student'] : [])); ?>
<div class="msg-layout msg-single msg-with-side">
  <aside class="card msg-side">
    <div class="msg-side-who"><?= avatar_html($ch === 'student' ? ($studentU ?: ['display_name' => $s['first_name']]) : ($parent ?: ['display_name' => $s['family_name']]), 'md') ?>
      <div><strong><?= e($ch === 'student' ? trim($s['first_name'] . ' ' . $s['last_name']) : ($parent['display_name'] ?? $s['family_name'])) ?></strong><small><?= $ch === 'student' ? 'Élève' . ($s['grade'] ? ' · ' . e($s['grade']) : '') : 'Parent de ' . e($s['first_name']) ?></small></div></div>
    <?php if (messages_have_channels()): ?><nav class="msg-side-ch" aria-label="Discussion">
      <a href="<?= e(url('t_messages', ['sid' => $s['id']])) ?>" class="<?= $ch === 'family' ? 'on' : '' ?>"><?= icon('users') ?> Parent<?= $unreadCh['family'] ? ' <span class="nav-badge">' . $unreadCh['family'] . '</span>' : '' ?></a>
      <a href="<?= e(url('t_messages', ['sid' => $s['id'], 'ch' => 'student'])) ?>" class="<?= $ch === 'student' ? 'on' : '' ?>"><?= icon('smile') ?> <?= e($s['first_name']) ?><?= $unreadCh['student'] ? ' <span class="nav-badge">' . $unreadCh['student'] . '</span>' : '' ?></a>
    </nav><?php endif ?>
    <?php $ph = $ch === 'student' ? ($s['student_phone'] ?? '') : ($parent['phone'] ?? $s['parent_phone'] ?? ''); $em = $ch === 'student' ? ($s['student_email'] ?? '') : ($parent['email'] ?? $s['parent_email'] ?? ''); ?>
    <dl class="msg-side-info">
      <div><dt>Téléphone</dt><dd><?= $ph ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $ph)) . '">' . e($ph) . '</a>' : '<span class="muted">—</span>' ?></dd></div>
      <div><dt>E-mail</dt><dd><?= $em ? '<a href="mailto:' . e($em) . '">' . e($em) . '</a>' : '<span class="muted">—</span>' ?></dd></div>
    </dl>
    <?php if ($ch === 'student' && $studentU): ?><p class="muted msg-side-note"><small><?= icon('eye') ?> Le parent peut lire cette discussion.</small></p><?php endif ?>
    <a class="btn ghost sm msg-side-fiche" href="<?= e(url('t_student', ['sid' => $s['id']])) ?>"><?= icon('user') ?> Fiche de <?= e($s['first_name']) ?></a>
  </aside>
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
