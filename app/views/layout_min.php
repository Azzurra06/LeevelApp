<?php require __DIR__ . '/_head.php' ?>
<body class="min<?= in_passation() ? ' passation ' . e(current_user()['role'] ?? '') : '' ?>">
<main class="wrap">
<?php if (in_passation()): ?>
  <div class="row between no-print">
    <span class="badge sage"><?= icon('lock') ?> Mode passation - <?= e(current_user()['display_name'] ?? '') ?></span>
    <form method="post" action="<?= e(url('passation_end')) ?>" data-confirm="Terminer et fermer la passation ? Les réponses sont déjà enregistrées."><?= csrf_field() ?><button class="btn ghost sm"><?= icon('x') ?> Fermer la passation</button></form>
  </div><br>
<?php endif ?>
<?php require __DIR__ . '/_flash.php' ?>
<?= $content ?>
</main>
<footer class="footer no-print"><a href="<?= e(url('privacy')) ?>">Données personnelles</a></footer>
</body></html>
