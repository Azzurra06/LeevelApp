<?php $title = 'Connexion' ?>
<div class="card auth-card">
  <div class="auth-brand"><span class="logo">leevel<span>.app</span></span><small>Suivi et accompagnement scolaire personnalisé</small></div>
  <h1 class="<?= !empty($adding) ? 'login-add-h' : '' ?>"><?= !empty($adding) ? 'Ajouter un compte' : 'Connexion' ?></h1>
  <?php if (!empty($adding)): ?><p class="muted">Connectez un autre compte (parent, élève…) : vous pourrez ensuite passer de l'un à l'autre depuis le menu « Mon compte », sans vous reconnecter.</p><?php endif ?>
  <?php if ($err): ?><p class="alert err"><?= e($err) ?></p><?php endif ?>
  <form method="post" action="<?= e(url('login', !empty($adding) ? ['add' => 1] : [])) ?>" class="stack">
    <?= csrf_field() ?><?php if (!empty($adding)): ?><input type="hidden" name="add" value="1"><?php endif ?>
    <label>Identifiant<input name="username" required autocomplete="username" autocapitalize="none" value="<?= e($prefill) ?>" <?= $prefill ? '' : 'autofocus' ?>></label>
    <label>Mot de passe<input name="password" type="password" required autocomplete="current-password" <?= $prefill ? 'autofocus' : '' ?>></label>
    <button class="btn"><?= icon('log-in') ?> <?= !empty($adding) ? 'Ajouter ce compte' : 'Se connecter' ?></button>
  </form>
  <?php if (!empty($adding)): ?><p class="login-back"><a href="<?= e(url('home')) ?>"><?= icon('chevron-left') ?> Revenir à mon espace</a></p><?php endif ?>
</div>
