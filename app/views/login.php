<?php $title = 'Connexion' ?>
<div class="card auth-card">
  <div class="row"><span class="brand"><span class="logo logo-lg">leevel<span>.app</span></span></span></div><p class="muted">Suivi et accompagnement scolaire personnalisé</p>
  <h1>Connexion</h1>
  <?php if ($err): ?><p class="alert err"><?= e($err) ?></p><?php endif ?>
  <form method="post" action="<?= e(url('login')) ?>" class="stack">
    <?= csrf_field() ?>
    <label>Identifiant<input name="username" required autocomplete="username" autocapitalize="none" value="<?= e($prefill) ?>" <?= $prefill ? '' : 'autofocus' ?>></label>
    <label>Mot de passe<input name="password" type="password" required autocomplete="current-password" <?= $prefill ? 'autofocus' : '' ?>></label>
    <button class="btn"><?= icon('key') ?> Se connecter</button>
  </form>
</div>
