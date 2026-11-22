<?php $title = 'Récapitulatif'; $isStudent = $cfg['code'] === 'student';
[$nDone, $nTotal] = qn_progress($cfg['code'], $answers);
$pct = $nTotal ? (int)round($nDone / $nTotal * 100) : 0;
// Rendu d'une réponse : pastilles pour les choix multiples, points pour les échelles, texte sinon
$answer = function (array $qq, mixed $v): string {
    if ($v === null || $v === '' || $v === []) return '<span class="ra-empty">Pas de réponse</span>';
    if ($v === SKIP) return '<span class="ra-empty">Préfère ne pas répondre</span>';
    if (in_array($qq['type'], ['multi', 'multi_prio'], true) && is_array($v)) {
        return '<span class="ra-chips">' . implode('', array_map(fn($x, $i) => '<span class="ra-chip' . ($qq['type'] === 'multi_prio' && $i === 0 ? ' prio' : '') . '">'
            . ($qq['type'] === 'multi_prio' && $i === 0 ? icon('flag') . ' ' : '') . e($qq['options'][$x] ?? (string)$x) . '</span>', $v, array_keys($v))) . '</span>';
    }
    if ($qq['type'] === 'scale' && is_int($v)) {
        $dots = ''; for ($i = 1; $i <= 5; $i++) $dots .= '<i class="' . ($i <= $v ? 'on' : '') . '"></i>';
        return '<span class="ra-scale"><span class="ra-dots" aria-hidden="true">' . $dots . '</span><strong>' . $v . '/5</strong> <small class="muted">' . e(($qq['min'] ?? '') . ' → ' . ($qq['max'] ?? '')) . '</small></span>';
    }
    return '<span class="ra-text">' . nl2br(e(qn_display($qq, $v))) . '</span>';
}; ?>
<?php if ($done): ?>
<div class="card accent">
  <h1><?= icon('sparkles') ?> <?= $isStudent ? 'Bravo, tu as terminé !' : 'Questionnaire terminé' ?></h1>
  <p><?= e($cfg['outro']) ?></p>
  <?php if (in_passation()): ?>
    <form method="post" action="<?= e(url('passation_end')) ?>"><?= csrf_field() ?><button class="btn"><?= icon('lock') ?> Terminer et rendre l'appareil</button></form>
  <?php endif ?>
</div>
<?php endif ?>

<div class="card recap-head">
  <div class="row between">
    <div><h1><?= icon('list-checks') ?> Récapitulatif de <?= $isStudent ? 'tes' : 'vos' ?> réponses</h1>
      <p class="muted"><?= $isStudent ? 'Tu peux modifier une réponse à tout moment.' : 'Vous pouvez modifier chaque étape à tout moment.' ?></p></div>
    <span class="badge <?= $sub['status'] === 'completed' ? 'ok' : 'warn' ?>"><?= icon($sub['status'] === 'completed' ? 'circle-check' : 'clock') ?> <?= $sub['status'] === 'completed' ? 'Terminé' : 'En cours' ?></span>
  </div>
  <div class="recap-progress"><div class="bar"><i data-w="<?= $pct ?>"></i></div><small class="muted"><?= $nDone ?> réponse<?= $nDone > 1 ? 's' : '' ?> sur <?= $nTotal ?> question<?= $nTotal > 1 ? 's' : '' ?></small></div>
</div>

<div class="grid-2">
  <?php foreach ($cfg['steps'] as $i => $st): $qs = array_filter($st['questions'], fn($qq) => qn_visible($qq, $answers)); ?>
  <section class="card recap-step">
    <div class="ed-head"><h2><span class="step-num"><?= $i + 1 ?></span> <?= e($st['title']) ?></h2>
      <a class="btn ghost sm" href="<?= e(url('q', $qp + ['step' => $i])) ?>"><?= icon('pencil') ?> Modifier</a></div>
    <dl class="ra-list">
      <?php foreach ($qs as $qq): ?>
        <div class="ra-row"><dt><?= e($qq['label']) ?></dt><dd><?= $answer($qq, $answers[$qq['key']] ?? null) ?></dd></div>
      <?php endforeach ?>
    </dl>
  </section>
  <?php endforeach ?>
</div>

<div class="row recap-actions">
  <?php if (in_passation() && !$done): ?>
    <form method="post" action="<?= e(url('passation_end')) ?>"><?= csrf_field() ?><button class="btn"><?= icon('lock') ?> Terminer la passation</button></form>
  <?php elseif ($tutor): ?>
    <a class="btn" href="<?= e(url('t_rdv', ['sid' => $s['id']])) ?>"><?= icon('chevron-left') ?> Retour au premier rendez-vous</a>
  <?php elseif (!in_passation()): ?>
    <a class="btn ghost" href="<?= e(url('home')) ?>"><?= icon('house') ?> Retour à l'accueil</a>
  <?php endif ?>
</div>
