<?php $title = 'Finances';
$pending = array_values(array_filter($payments, fn($p) => $p['status'] === 'pending'));
$paid = array_values(array_filter($payments, fn($p) => $p['status'] === 'paid'));
$due = array_sum(array_map(fn($p) => (float)$p['amount'], $pending));
$yearPaid = array_sum(array_map(fn($p) => substr((string)$p['paid_at'], 0, 4) === date('Y') ? (float)$p['amount'] : 0, $paid));
$tax = tax_credit_on(); $instr = setting('pay_instructions');
$late = fn($p) => $p['due_date'] && $p['due_date'] < date('Y-m-d');
$dates = array_filter(array_column($pending, 'due_date')); sort($dates);
$payDate = $dates[0] ?? expected_payment_date($s); $isLate = $payDate && $pending && $payDate < date('Y-m-d');
$dayLbl = fn($d) => $d === date('Y-m-d') ? "aujourd'hui" : ($d === date('Y-m-d', strtotime('+1 day')) ? 'demain' : 'le ' . fdate($d)); ?>
<div class="page-head"><div><h1><?= icon('file-text') ?> Finances</h1><p>Tarif, montants à régler et paiements pour les cours de <?= e(kid($s['first_name'])) ?>.</p><?php if ($t = family_terms($s)): ?><p class="fin-terms-p"><?= icon('calendar') ?> Paiement convenu : <strong><?= e(mb_strtolower($t)) ?></strong></p><?php endif ?></div></div>

<div class="card"><h2><?= icon('chart-column') ?> En bref</h2>
  <div class="month-stats">
    <div class="stat"><span class="muted">À régler</span><strong><?= money($due) ?></strong>
      <?php if ($pending && $payDate): ?><small class="muted"><?= $isLate ? 'En retard depuis le ' . e(fdate($payDate)) : 'À payer ' . e($dayLbl($payDate)) ?></small>
      <?php elseif ($payDate): ?><small class="muted">Prochain paiement <?= e($dayLbl($payDate)) ?></small><?php endif ?></div>
    <div class="stat"><span class="muted">Tarif horaire</span><strong><?= $s['hourly_rate'] !== null ? money((float)$s['hourly_rate']) . ' / h' : '—' ?></strong><?php if ($s['hourly_rate_visio'] !== null): ?><small class="muted">Visio : <?= money((float)$s['hourly_rate_visio']) ?> / h</small><?php endif ?><?php if ((float)($s['discount_pct'] ?? 0) > 0): ?><small class="ok-txt"><?= e(discount_text($s['discount_label'] ?? null, (float)$s['discount_pct'])) ?></small><?php endif ?></div>
    <div class="stat"><span class="muted"><?= e(month_label(date('Y-m'))) ?> (en cours)</span><strong><?= money($billing['amount']) ?></strong><?php if ($billing['discount'] > 0): ?><small class="muted"><s><?= money($billing['gross']) ?></s> · remise −<?= money($billing['discount']) ?></small><?php endif ?><small class="muted"><?= fdur($billing['minutes']) ?> réalisée<?= $billing['minutes'] > 60 ? 's' : '' ?><?= $billing['planned_min'] ? ' · ' . fdur($billing['planned_min']) . ' prévues' : '' ?></small></div>
    <div class="stat"><span class="muted">Réglé en <?= date('Y') ?></span><strong><?= money($yearPaid) ?></strong><?php if ($tax && $yearPaid > 0): ?><small class="muted">soit <?= money($yearPaid / 2) ?> après crédit d'impôt</small><?php endif ?></div>
  </div>
</div>

<div class="grid-2 two-cols">
 <div class="col-stack">
  <div class="card<?= $pending ? ' card-due' : '' ?>"><h2><?= icon('clock') ?> À régler<?php if (count($pending) > 1): ?> <span class="due-total"><?= money($due) ?></span><?php endif ?></h2>
    <?php if (!$pending): ?><p class="muted">Rien à régler pour le moment.</p><?php endif ?>
    <ul class="due-list"><?php foreach ($pending as $p): ?>
      <li class="due-item"><div class="due-top"><div class="pay-main"><strong><?= e(payment_title($p)) ?></strong><small class="muted"><?= $p['minutes'] ? fdur((int)$p['minutes']) . ' de cours' : 'Montant convenu avec la tutrice' ?></small><?php if (!empty($p['discount'])): ?><small class="ok-txt"><?= e($p['discount_label'] ?: 'Remise') ?> : −<?= money((float)$p['discount']) ?> (au lieu de <?= money((float)$p['gross']) ?>)</small><?php endif ?></div>
          <strong class="due-amt"><?= money((float)$p['amount']) ?></strong></div>
        <div class="due-foot"><span class="pay-due<?= $late($p) ? ' late' : '' ?>"><?= icon('calendar') ?> <?= !$p['due_date'] ? 'Date à convenir' : ($late($p) ? 'Prévu le ' . e(short_day($p['due_date'])) : 'À payer ' . e($p['due_date'] <= date('Y-m-d', strtotime('+1 day')) ? $dayLbl($p['due_date']) : 'le ' . short_day($p['due_date']))) ?></span><span class="badge <?= $late($p) ? 'err' : 'warn' ?>"><?= $late($p) ? 'En retard' : 'À régler' ?></span></div></li>
    <?php endforeach ?></ul>
    <?php if ($pending && $instr): ?><div class="pay-instr"><strong><?= icon('lightbulb') ?> Comment régler</strong><p><?= nl2br(e($instr)) ?></p></div><?php endif ?>
  </div>
  <div class="card"><h2><?= icon('circle-check') ?> Historique des paiements</h2>
    <?php if (!$paid): ?><p class="muted">Aucun paiement enregistré pour l'instant.</p><?php endif ?>
    <ul class="pay-list"><?php foreach ($paid as $p): ?>
      <li><div class="pay-main"><strong><?= e(payment_title($p)) ?></strong><?php if (!empty($p['discount'])): ?><small class="ok-txt"><?= e($p['discount_label'] ?: 'Remise') ?> : −<?= money((float)$p['discount']) ?></small><?php endif ?><small class="muted">Payé le <?= e(fdate($p['paid_at'])) ?><?= $p['method'] ? ' · ' . e(PAY_METHODS[$p['method']] ?? $p['method']) : '' ?></small></div>
        <div class="pay-amt"><strong><?= money((float)$p['amount']) ?></strong><span class="badge sage">Payé</span></div></li>
    <?php endforeach ?></ul>
  </div>
 </div>
 <div class="col-stack">
  <div class="card"><h2><?= icon('calendar') ?> Séances de la semaine</h2>
    <p class="muted"><small>Du <?= e(date('d/m', strtotime($week['start']))) ?> au <?= e(date('d/m', strtotime($week['end']))) ?></small></p>
    <?php if (!$week['sessions']): ?><p class="muted">Aucune séance cette semaine.</p><?php endif ?>
    <ul class="pay-list"><?php foreach ($week['sessions'] as $x): ?>
      <li><div class="pay-main"><strong><?= e(fdatetime($x['starts_at'])) ?></strong><small class="muted"><?= fdur((int)$x['duration_min']) ?> · <?= e(MODES[$x['mode']]) ?><?= $x['status'] === 'planned' ? ' · prévue' : '' ?></small></div><div class="pay-amt"><strong><?= $x['free'] ? 'Offerte' : ($x['amount'] !== null ? money($x['amount']) : '—') ?></strong></div></li>
    <?php endforeach ?></ul>
  </div>
  <?php if ($tax): ?><div class="card"><h2><?= icon('sparkles') ?> Crédit d'impôt</h2><p>Les cours ouvrent droit à un crédit d'impôt de <strong>50 %</strong> des sommes versées (services à la personne). Une attestation annuelle vous sera remise par la tutrice.</p></div><?php endif ?>
  <?php if ($instr && !$pending): ?><div class="card"><h2><?= icon('lightbulb') ?> Comment régler</h2><p><?= nl2br(e($instr)) ?></p></div><?php endif ?>
 </div>
</div>
