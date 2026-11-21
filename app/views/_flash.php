<?php foreach (flash() ?? [] as [$t, $m]): ?><div class="alert <?= e($t) ?>" role="status"><?= e($m) ?></div><?php endforeach ?>
<?php if (!empty($_SESSION['invite_result'])): $invRes = $_SESSION['invite_result']; unset($_SESSION['invite_result']); ?>
<div class="card invite-box">
  <h3><?= icon('message-circle') ?> Invitations</h3>
  <?php foreach ($invRes as $ir): ?>
    <div class="invite-item">
      <p><strong><?= $ir['role'] === 'parent' ? 'Parent' : 'Élève' ?></strong> · <?= e($ir['email']) ?> ·
        <?= $ir['sent'] ? '<span class="badge sage">E-mail envoyé</span>' : '<span class="badge warn">E-mail non envoyé : transmettez le lien vous-même</span>' ?></p>
      <div class="copy-row"><input type="text" readonly value="<?= e($ir['link']) ?>" aria-label="Lien d'invitation"><button type="button" class="btn soft sm" data-copy><?= icon('file-text') ?> Copier</button></div>
    </div>
  <?php endforeach ?>
  <p class="muted"><small>Ce lien est personnel, à usage unique et valable 7 jours. Il ne sera plus affiché ; vous pourrez en générer un nouveau depuis « Comptes ».</small></p>
</div>
<?php endif ?>
