<?php $title = 'Récapitulatif'; $isStudent = $cfg['code'] === 'student'; ?>
<?php if ($done): ?>
<div class="card accent">
  <h1><?= icon('sparkles') ?> <?= $isStudent ? 'Bravo, tu as terminé !' : 'Questionnaire terminé' ?></h1>
  <p><?= e($cfg['outro']) ?></p>
  <?php if (in_passation()): ?>
    <form method="post" action="<?= e(url('passation_end')) ?>"><?= csrf_field() ?><button class="btn"><?= icon('lock') ?> Terminer et rendre l'appareil</button></form>
  <?php endif ?>
</div><br>
<?php endif ?>
<div class="card">
  <div class="row between"><h2><?= icon('list-checks') ?> Récapitulatif de <?= $isStudent ? 'tes' : 'vos' ?> réponses</h2>
    <span class="badge <?= $sub['status'] === 'completed' ? 'sage' : 'warn' ?>"><?= $sub['status'] === 'completed' ? 'Terminé' : 'En cours' ?></span></div>
  <p class="muted"><?= $isStudent ? 'Tu peux modifier une réponse à tout moment.' : 'Vous pouvez modifier chaque étape.' ?></p>
  <?php foreach ($cfg['steps'] as $i => $st): ?>
    <fieldset><legend><span class="sec-tag"><?= e($st['title']) ?></span></legend>
      <dl class="recap">
      <?php foreach ($st['questions'] as $qq): if (!qn_visible($qq, $answers)) continue; ?>
        <dt><?= e($qq['label']) ?></dt><dd><?= e(qn_display($qq, $answers[$qq['key']] ?? null)) ?></dd>
      <?php endforeach ?>
      </dl>
      <p><a class="btn ghost sm" href="<?= e(url('q', $qp + ['step' => $i])) ?>"><?= icon('pencil') ?> Modifier</a></p>
    </fieldset><br>
  <?php endforeach ?>
  <?php if (in_passation() && !$done): ?>
    <form method="post" action="<?= e(url('passation_end')) ?>"><?= csrf_field() ?><button class="btn"><?= icon('lock') ?> Terminer la passation</button></form>
  <?php elseif ($tutor): ?>
    <a class="btn" href="<?= e(url('t_rdv', ['sid' => $s['id']])) ?>"><?= icon('chevron-left') ?> Retour au premier rendez-vous</a>
  <?php elseif (!in_passation()): ?>
    <a class="btn" href="<?= e(url('home')) ?>"><?= icon('house') ?> Retour à l'accueil</a>
  <?php endif ?>
</div>
