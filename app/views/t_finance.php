<?php $title = 'Finances';
$months = []; for ($i = -6; $i <= 1; $i++) $months[] = date('Y-m', strtotime(date('Y-m-01') . " $i month"));
if (!in_array($month, $months, true)) $months[] = $month;
$toBill = array_sum(array_map(fn($r) => $r['b']['amount'], $rows));
$minTot = array_sum(array_map(fn($r) => $r['b']['minutes'], $rows));
$pendingTot = array_sum(array_map(fn($p) => (float)$p['amount'], $pending));
$hid = fn(string $action) => csrf_field() . '<input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="m" value="' . e($month) . '">';
$methodSel = fn(?string $cur = null) => '<select name="method" aria-label="Moyen de paiement">' . implode('', array_map(fn($k, $l) => '<option value="' . $k . '"' . ($cur === $k ? ' selected' : '') . '>' . e($l) . '</option>', array_keys(PAY_METHODS), PAY_METHODS)) . '</select>';
$late = fn($p) => $p['status'] === 'pending' && $p['due_date'] && $p['due_date'] < date('Y-m-d'); ?>
<div class="page-head"><div><h1><?= icon('file-text') ?> Finances</h1><p>Tarifs, montants à régler et suivi des paiements de vos familles.</p></div>
  <form method="get" class="row"><input type="hidden" name="r" value="t_finance"><select name="m" data-autosubmit aria-label="Mois"><?php foreach (array_reverse($months) as $m): ?><option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= e(month_label($m)) ?></option><?php endforeach ?></select></form></div>

<div class="card"><h2><?= icon('chart-column') ?> <?= e(month_label($month)) ?> en bref</h2>
  <div class="month-stats">
    <div class="stat"><span class="muted">Heures réalisées</span><strong><?= fdur($minTot) ?></strong></div>
    <div class="stat"><span class="muted">Montant du mois</span><strong><?= money($toBill) ?></strong></div>
    <div class="stat"><span class="muted">Encaissé ce mois</span><strong><?= money($cashed) ?></strong></div>
    <div class="stat"><span class="muted">En attente (tous mois)</span><strong><?= money($pendingTot) ?></strong><?php if ($c = count(array_filter($pending, $late))): ?><small class="err-txt"><?= $c ?> en retard</small><?php endif ?></div>
  </div>
</div>

<div class="card"><h2><?= icon('users') ?> Par élève · <?= e(month_label($month)) ?></h2>
  <?php if (!$rows): ?><p class="muted">Aucun élève pour l'instant.</p><?php endif ?>
  <div class="fin-list">
  <?php foreach ($rows as ['s' => $st, 'b' => $b, 'u' => $ub, 'pay' => $pay]): $terms = family_terms($st); ?>
    <div class="fin-row editable">
      <div class="fin-who"><strong><?= e($st['first_name']) ?></strong><small class="muted"><?= e($st['family_name']) ?></small><?php if ($terms): ?><small class="fin-terms"><?= e($terms) ?></small><?php endif ?></div>
      <div class="fin-rate"><div><?php if ($st['hourly_rate'] !== null): ?><?= money((float)$st['hourly_rate']) ?>/h<?php if ($st['hourly_rate_visio'] !== null): ?> <small class="muted">· visio <?= money((float)$st['hourly_rate_visio']) ?>/h</small><?php endif ?><?php elseif (rate_is_default($st)): ?><?= money(default_rate('presentiel')) ?>/h <small class="muted">· tarif par défaut</small><?php else: ?><span class="muted">Tarif non défini</span><?php endif ?>
        <?php if ((float)($st['discount_pct'] ?? 0) > 0): ?><br><span class="badge sage"><?= e(discount_text($st['discount_label'] ?? null, (float)$st['discount_pct'])) ?></span><?php endif ?></div>
        <button type="button" class="edit-btn view" data-edit title="Modifier le tarif et la remise" aria-label="Modifier le tarif et la remise"><?= icon('pencil') ?></button></div>
      <div class="fin-month"><strong><?= $b['missing_rate'] && !$b['amount'] ? '—' : money($b['amount']) ?></strong><?php if ($b['discount'] > 0): ?><small class="muted"><s><?= money($b['gross']) ?></s> − <?= money($b['discount']) ?> de remise</small><?php endif ?><small class="muted"><?= fdur($b['minutes']) ?> réalisée<?= $b['minutes'] > 60 ? 's' : '' ?><?= $b['planned_min'] ? ' · ' . fdur($b['planned_min']) . ' encore prévues' : '' ?></small></div>
      <div class="fin-status">
        <?php if ($ub['amount'] > 0): ?>
          <form method="post" class="fin-create"><?= $hid('create') ?><input type="hidden" name="sid" value="<?= (int)$st['id'] ?>"><input type="hidden" name="notify" value="1">
            <button class="btn sm" title="<?= e(unbilled_label($ub)) ?>"><?= icon('send') ?> Demander <?= money($ub['amount']) ?></button></form>
          <small class="muted"><?= count($ub['sessions']) ?> séance<?= count($ub['sessions']) > 1 ? 's' : '' ?> non facturée<?= count($ub['sessions']) > 1 ? 's' : '' ?></small>
        <?php elseif ($pay): ?>
          <?php if ($pay['status'] === 'paid'): ?><span class="badge sage">Payé le <?= e(fdate($pay['paid_at'])) ?></span><?php else: ?><span class="badge <?= $late($pay) ? 'err' : 'warn' ?>"><?= $late($pay) ? 'En retard' : 'À régler' ?> · <?= money((float)$pay['amount']) ?></span><?php endif ?>
        <?php else: ?><span class="muted"><small><?= $ub['missing_rate'] || rate_for($st, 'presentiel') === null ? 'Définissez un tarif' : 'Rien à régler' ?></small></span><?php endif ?>
      </div>
      <form method="post" class="fin-rate-form edit-form"><?= $hid('rate') ?><input type="hidden" name="sid" value="<?= (int)$st['id'] ?>">
        <label>Tarif / h (€)<input name="hourly_rate" inputmode="decimal" value="<?= e($st['hourly_rate'] ?? (default_rate('presentiel') !== null ? rtrim(rtrim(number_format(default_rate('presentiel'), 2, '.', ''), '0'), '.') : '')) ?>" placeholder="ex. 25"></label>
        <label>Visio / h<input name="hourly_rate_visio" inputmode="decimal" value="<?= e($st['hourly_rate_visio'] ?? (setting('default_rate_visio') ?? '')) ?>" placeholder="—"></label>
        <label>Remise (%)<input name="discount_pct" inputmode="decimal" value="<?= e($st['discount_pct'] ?? '') ?>" placeholder="ex. 10"></label>
        <label class="wide-l">Motif de la remise<input name="discount_label" maxlength="80" value="<?= e($st['discount_label'] ?? '') ?>" placeholder="ex. fratrie, fidélité, forfait 10 séances"></label>
        <label>Paiement<select name="pay_frequency"><option value="">—</option><?php foreach (PAY_FREQUENCIES as $k => $l): ?><option value="<?= $k ?>" <?= ($st['pay_frequency'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></label>
        <label>Moyen<select name="pay_method"><option value="">—</option><?php foreach (PAY_METHODS as $k => $l): ?><option value="<?= $k ?>" <?= ($st['pay_method'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></label>
        <div class="fin-rate-btns"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div></form>
    </div>
  <?php endforeach ?>
  </div>
  <details class="fin-manual"><summary><?= icon('plus') ?> Ajouter une échéance manuelle (forfait, stage, frais…)</summary>
    <form method="post" class="stack"><?= $hid('create') ?><input type="hidden" name="manual" value="1">
      <div class="form-grid">
        <label>Élève<select name="sid"><?php foreach ($rows as ['s' => $st]): ?><option value="<?= (int)$st['id'] ?>"><?= e($st['first_name']) ?></option><?php endforeach ?></select></label>
        <label>Libellé<input name="label" required maxlength="160" placeholder="ex. Stage de révisions (vacances)"></label>
        <label>Montant (€)<input name="amount" required inputmode="decimal" placeholder="ex. 120"></label>
        <label>À régler avant le<input type="date" name="due_date"></label>
        <label>Remise <small class="muted">en € ou en % (ex. 10 %)</small><input name="discount" placeholder="ex. 15 ou 10 %"></label>
        <label>Motif de la remise<input name="discount_label" maxlength="80" placeholder="ex. geste commercial"></label>
      </div>
      <label class="check"><input type="checkbox" name="notify" value="1" checked> Prévenir le parent dans « Messagerie »</label>
      <div><button class="btn sm"><?= icon('plus') ?> Créer l'échéance</button></div>
    </form></details>
</div>

<div class="grid-2 two-cols">
 <div class="col-stack">
  <div class="card"><h2><?= icon('clock') ?> En attente de paiement</h2>
    <?php if (!$pending): ?><p class="muted">Tout est réglé. 🎉</p><?php endif ?>
    <ul class="pay-list"><?php foreach ($pending as $p): ?>
      <li class="editable"><div class="pay-main"><strong><?= e($p['first_name']) ?> · <?= money((float)$p['amount']) ?></strong><small class="muted"><?= e($p['label']) ?><?= $p['due_date'] ? ' · avant le ' . e(fdate($p['due_date'])) : '' ?><?= !empty($p['discount']) ? ' · ' . e($p['discount_label'] ?: 'Remise') . ' −' . money((float)$p['discount']) : '' ?></small><?php if ($late($p)): ?><span class="badge err">En retard</span><?php endif ?></div>
        <div class="row view"><button type="button" class="btn sm" data-edit><?= icon('check') ?> Marquer payé</button>
          <form method="post" data-confirm="Supprimer cette échéance ?"><?= $hid('delete') ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn ghost sm" title="Supprimer" aria-label="Supprimer"><?= icon('trash-2') ?></button></form></div>
        <form method="post" class="edit-form pay-form"><?= $hid('pay') ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <label>Payé le<input type="date" name="paid_at" value="<?= date('Y-m-d') ?>"></label><label>Moyen<?= $methodSel($p['pay_method'] ?? null) ?></label>
          <button class="btn sm"><?= icon('save') ?> Valider</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></form></li>
    <?php endforeach ?></ul>
  </div>
  <div class="card"><h2><?= icon('circle-check') ?> Derniers paiements reçus</h2>
    <?php if (!$paid): ?><p class="muted">Aucun paiement enregistré.</p><?php endif ?>
    <ul class="pay-list"><?php foreach ($paid as $p): ?>
      <li><div class="pay-main"><strong><?= e($p['first_name']) ?> · <?= money((float)$p['amount']) ?></strong><small class="muted"><?= e($p['label']) ?> · payé le <?= e(fdate($p['paid_at'])) ?><?= $p['method'] ? ' · ' . e(PAY_METHODS[$p['method']] ?? $p['method']) : '' ?></small></div>
        <form method="post"><?= $hid('unpay') ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn ghost sm" title="Remettre en attente"><?= icon('refresh-cw') ?></button></form></li>
    <?php endforeach ?></ul>
  </div>
 </div>
 <div class="col-stack">
  <div class="card editable"><div class="ed-head"><h2><?= icon('settings') ?> Réglages</h2><button type="button" class="btn ghost sm" data-edit><?= icon('pencil') ?> Modifier</button></div>
    <dl class="fields view">
      <div class="wide"><dt>Crédit d'impôt (services à la personne)</dt><dd><?= tax_credit_on() ? 'Affiché aux familles (50 % des sommes versées)' : 'Non affiché' ?></dd></div>
      <div class="wide"><dt>Instructions de paiement</dt><dd><?= setting('pay_instructions') ? nl2br(e(setting('pay_instructions'))) : '<span class="muted">Non renseignées</span>' ?></dd></div>
    </dl>
    <form method="post" class="stack edit-form"><?= $hid('settings') ?>
      <label class="check"><input type="checkbox" name="tax_credit" value="1" <?= tax_credit_on() ? 'checked' : '' ?>> Mon activité ouvre droit au crédit d'impôt de 50 % (déclarée services à la personne) : l'afficher aux familles</label>
      <label>Instructions de paiement (visibles par les parents)<textarea name="pay_instructions" maxlength="1000" rows="4" placeholder="ex. Virement : IBAN FR76… · Titulaire : … · Référence : prénom de l'enfant + mois"><?= e(setting('pay_instructions') ?? '') ?></textarea></label>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
    </form>
  </div>
  <div class="card"><h2><?= icon('lightbulb') ?> Comment ça marche</h2>
    <ol class="fin-help">
      <li>Vos tarifs par défaut (Mon compte) s'appliquent à tous ; modifiez-les par élève si besoin, avec une remise éventuelle, grâce au crayon.</li>
      <li>Indiquez pour chaque famille le rythme (après chaque séance, chaque semaine, chaque mois…) et le moyen de paiement.</li>
      <li>« Demander » facture les séances réalisées pas encore facturées : le parent la voit dans « Finances » et reçoit une notification.</li>
      <li>Quand vous recevez le paiement, cliquez sur « Marquer payé ».</li>
    </ol>
  </div>
 </div>
</div>
