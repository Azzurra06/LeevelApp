<?php $title = 'Créer mon compte'; $isParent = $inv['role'] === 'parent'; ?>
<div class="card auth-card">
  <span class="logo logo-lg">leevel<span>.app</span></span>
  <h1><?= $isParent ? 'Bienvenue !' : 'Salut ' . e($inv['display_name']) . ' 👋' ?></h1>
  <p class="muted"><?= $isParent
      ? 'Créez votre compte pour suivre l\'accompagnement scolaire de ' . e(kid($inv['first_name'])) . '.'
      : 'Crée ton compte pour retrouver tes objectifs, tes missions et tes séances.' ?></p>
  <?php if ($err): ?><p class="alert err"><?= e($err) ?></p><?php endif ?>
  <form method="post" action="<?= e(url('invite', ['token' => $token])) ?>" class="stack">
    <?= csrf_field() ?>
    <label><?= $isParent ? 'Votre nom' : 'Ton prénom' ?><input name="display_name" required maxlength="100" value="<?= e($old['display_name'] ?? $inv['display_name']) ?>"></label>
    <label>Identifiant de connexion<input name="username" required pattern="[a-z0-9._\-]{3,60}" autocapitalize="none" autocomplete="username" value="<?= e($old['username'] ?? '') ?>" placeholder="ex. prenom.nom"></label>
    <p class="muted"><small>3 à 60 caractères : minuscules, chiffres, point, tiret.</small></p>
    <label>Mot de passe<input name="password" type="password" required minlength="10" autocomplete="new-password"></label>
    <label>Confirmer le mot de passe<input name="password2" type="password" required minlength="10" autocomplete="new-password"></label>
    <p class="muted"><small>10 caractères minimum, avec des lettres et des chiffres.</small></p>
    <?php if ($isParent && !$inv['guardian_consent_at']): ?>
      <label class="check consent"><input type="checkbox" name="agree" value="1" required><span>En tant que représentant légal, j'autorise l'utilisation de cet outil par mon enfant et le traitement de ces données pour son accompagnement scolaire.<a class="consent-more" href="<?= e(url('privacy')) ?>" target="_blank" rel="noopener">En savoir plus sur vos données</a></span></label>
    <?php endif ?>
    <button class="btn"><?= icon('check') ?> Créer mon compte</button>
  </form>
</div>
