<?php $title = 'Passation terminée' ?>
<div class="card auth-card">
  <h1><?= icon('circle-check') ?> Merci !</h1>
  <p>Le questionnaire est enregistré et la session de passation est fermée.</p>
  <p class="muted">Vous pouvez rendre l'appareil à la tutrice.</p>
  <a class="btn" href="<?= e(url('login', $tutor ? ['u' => $tutor] : [])) ?>"><?= icon('key') ?> Reconnexion tutrice</a>
</div>
