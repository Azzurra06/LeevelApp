<?php $title = 'Autorisation' ?>
<div class="card">
  <h1><?= icon('shield') ?> Avant de commencer</h1>
  <p>Cet outil sert uniquement au suivi scolaire de <strong><?= e($s['first_name']) ?></strong> par sa tutrice.</p>
  <ul class="dots">
    <li>Données collectées : réponses aux questionnaires, objectifs, séances, autoévaluations. Aucune donnée médicale.</li>
    <li>Accès : la tutrice, vous-même (informations de suivi partagées) et votre enfant (son propre espace). Les réponses personnelles de votre enfant restent confidentielles entre lui et la tutrice.</li>
    <li>Aucune transmission à des tiers ni à une intelligence artificielle externe.</li>
    <li>Vous pouvez demander à tout moment l'export ou la suppression des données.</li>
  </ul>
  <p><a href="<?= e(url('privacy')) ?>">En savoir plus</a></p>
  <form method="post" action="<?= e(url('consent', $qp)) ?>" class="stack">
    <?= csrf_field() ?>
    <?php if ($tutor): ?><p class="alert warn">Vous remplissez ce questionnaire pour <?= e($respondent['display_name']) ?>. Lisez-lui ces informations : seul le représentant légal peut donner son accord.</p>
    <label class="check"><input type="checkbox" name="agree" value="1" required> Le représentant légal (<?= e($respondent['display_name']) ?>) a pris connaissance de ces informations et donne son accord, recueilli par la tutrice.</label>
    <?php else: ?>
    <label class="check"><input type="checkbox" name="agree" value="1" required> En tant que représentant légal, j'autorise l'utilisation de cet outil par mon enfant et le traitement de ces données pour son accompagnement scolaire.</label>
    <?php endif ?>
    <button class="btn"><?= icon('check') ?> Valider et continuer</button>
  </form>
</div>
