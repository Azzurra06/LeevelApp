<?php $title = 'Finances';
$months = []; for ($i = -6; $i <= 1; $i++) $months[] = date('Y-m', strtotime(date('Y-m-01') . " $i month"));
if (!in_array($month, $months, true)) $months[] = $month;
$toBill = array_sum(array_map(fn($r) => $r['b']['amount'], $rows));
$minTot = array_sum(array_map(fn($r) => $r['b']['minutes'], $rows));
$pendingTot = array_sum(array_map(fn($p) => (float)$p['amount'], $pending));
$unbilledTot = array_sum(array_map(fn($r) => $r['u']['amount'], $rows));
$hid = fn(string $action) => csrf_field() . '<input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="m" value="' . e($month) . '">'
    . ($fview === 'eleve' ? '<input type="hidden" name="view" value="eleve"><input type="hidden" name="vsid" value="' . (int)$vsid . '">' : '');
$methodSel = fn(?string $cur = null) => '<select name="method" aria-label="Moyen de paiement">' . implode('', array_map(fn($k, $l) => '<option value="' . $k . '"' . ($cur === $k ? ' selected' : '') . '>' . e($l) . '</option>', array_keys(PAY_METHODS), PAY_METHODS)) . '</select>';
$late = fn($p) => $p['status'] === 'pending' && $p['due_date'] && $p['due_date'] < date('Y-m-d');
$lateN = count(array_filter($pending, $late));
$rateTxt = function (array $st): string {
    if ($st['hourly_rate'] !== null) return money((float)$st['hourly_rate']) . '/h' . ($st['hourly_rate_visio'] !== null ? ' · visio ' . money((float)$st['hourly_rate_visio']) . '/h' : '');
    return rate_is_default($st) ? money(default_rate('presentiel')) . '/h (par défaut)' : 'Tarif non défini';
};
$payItem = function (array $p, bool $withName) use ($hid, $methodSel, $late) { ?>
  <li class="<?= $p['status'] === 'pending' ? 'editable' : '' ?>"><div class="pay-main"><strong><?= $withName ? e($p['first_name']) . ' · ' : '' ?><?= money((float)$p['amount']) ?></strong>
    <small class="muted"><?= e(payment_title($p)) ?><?= $p['status'] === 'paid' ? ' · payé le ' . e(fdate($p['paid_at'])) . ($p['method'] ? ' · ' . e(PAY_METHODS[$p['method']] ?? $p['method']) : '') : ($p['due_date'] ? ' · avant le ' . e(fdate($p['due_date'])) : '') ?><?= !empty($p['discount']) ? ' · ' . e($p['discount_label'] ?: 'Remise') . ' −' . money((float)$p['discount']) : '' ?></small>
    <?php if ($late($p)): ?><span class="badge err">En retard</span><?php endif ?></div>
    <?php if ($p['status'] === 'pending'): ?>
      <div class="row view"><button type="button" class="btn sm" data-edit><?= icon('check') ?> Marquer payé</button>
        <form method="post" data-confirm="Supprimer cette échéance ?"><?= $hid('delete') ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn ghost sm" title="Supprimer" aria-label="Supprimer"><?= icon('trash-2') ?></button></form></div>
      <form method="post" class="edit-form pay-form"><?= $hid('pay') ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <label>Payé le<input type="date" name="paid_at" value="<?= date('Y-m-d') ?>"></label><label>Moyen<?= $methodSel($p['pay_method'] ?? null) ?></label>
        <button class="btn sm"><?= icon('save') ?> Valider</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></form>
    <?php else: ?>
      <form method="post"><?= $hid('unpay') ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn ghost sm" title="Remettre en attente" aria-label="Remettre en attente"><?= icon('refresh-cw') ?></button></form>
    <?php endif ?></li>
<?php }; ?>
<div class="page-head"><div><h1><?= icon('file-text') ?> Finances</h1><p>Chiffre d'affaires, paiements à recevoir et suivi par élève.</p></div></div>

<nav class="tabs" aria-label="Finances">
  <a href="<?= e(url('t_finance', ['m' => $month])) ?>" class="<?= $fview === 'ensemble' ? 'on' : '' ?>">Vue d'ensemble</a>
  <a href="<?= e(url('t_finance', ['m' => $month, 'view' => 'eleve', 'sid' => $vsid])) ?>" class="<?= $fview === 'eleve' ? 'on' : '' ?>">Par élève</a>
</nav>

<?php if ($fview === 'ensemble'): $max = max(1, max($series)); ?>
<div class="card"><h2><?= icon('chart-column') ?> Chiffre d'affaires</h2>
  <div class="month-stats">
    <div class="stat"><span class="muted">Encaissé en <?= date('Y') ?></span><strong><?= money($yearPaid) ?></strong></div>
    <div class="stat"><span class="muted">Encaissé · <?= e(mb_strtolower(month_label($month))) ?></span><strong><?= money($cashed) ?></strong></div>
    <div class="stat"><span class="muted">À recevoir</span><strong><?= money($pendingTot) ?></strong><small class="<?= $lateN ? 'err-txt' : 'muted' ?>"><?= $lateN ? $lateN . ' en retard' : count($pending) . ' échéance' . (count($pending) > 1 ? 's' : '') ?></small></div>
    <div class="stat"><span class="muted">Séances non facturées</span><strong><?= money($unbilledTot) ?></strong><small class="muted">à demander aux familles</small></div>
  </div>
  <div class="ca-chart" role="img" aria-label="Encaissements des 12 derniers mois">
    <?php foreach ($series as $k => $v): ?><div class="ca-col" title="<?= e(month_label($k)) ?> : <?= e(money($v)) ?>"><span class="ca-val"><?= $v > 0 ? e(money($v)) : '' ?></span><span class="ca-bar<?= $k === date('Y-m') ? ' now' : '' ?>" style="height:<?= max(2, (int)round($v / $max * 100)) ?>%"></span><small><?= ['', 'janv', 'févr', 'mars', 'avr', 'mai', 'juin', 'juil', 'août', 'sept', 'oct', 'nov', 'déc'][(int)substr($k, 5, 2)] ?></small></div><?php endforeach ?>
  </div>
  <p class="muted"><small>Encaissements des 12 derniers mois.</small></p>
</div>

<div class="card"><div class="row between"><h2><?= icon('users') ?> Par élève</h2>
  <form method="get" class="row"><input type="hidden" name="r" value="t_finance"><label class="sr-only" for="fin-m">Mois</label><select id="fin-m" name="m" data-autosubmit aria-label="Mois"><?php foreach (array_reverse($months) as $m): ?><option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= e(month_label($m)) ?></option><?php endforeach ?></select></form></div>
  <?php if (!$rows): ?><p class="muted">Aucun élève pour l'instant.</p><?php else: ?>
  <div class="table-wrap"><table class="fin-table">
    <tr><th>Élève</th><th>Séances du mois</th><th>Encaissé</th><th>À recevoir</th><th>Non facturé</th><th></th></tr>
    <?php foreach ($rows as ['s' => $st, 'b' => $b, 'u' => $ub, 'm' => $mo]): ?>
    <tr><td><a href="<?= e(url('t_finance', ['m' => $month, 'view' => 'eleve', 'sid' => $st['id']])) ?>"><strong><?= e($st['first_name']) ?></strong></a><small class="muted"><?= e($rateTxt($st)) ?><?= !empty($st['first_free']) ? ' · 1re séance offerte' : '' ?></small></td>
      <td><?= money($b['amount']) ?><small class="muted"><?= fdur($b['minutes']) ?></small></td>
      <td><?= money($mo['paid']) ?></td>
      <td><?= $mo['pending'] > 0 ? money($mo['pending']) : '—' ?><?php if ($mo['late'] > 0): ?><small class="err-txt">dont <?= money($mo['late']) ?> en retard</small><?php endif ?></td>
      <td><?= $ub['amount'] > 0 ? money($ub['amount']) : '—' ?></td>
      <td class="t-right"><?php if ($ub['amount'] > 0): ?><form method="post"><?= $hid('create') ?><input type="hidden" name="sid" value="<?= (int)$st['id'] ?>"><input type="hidden" name="notify" value="1"><button class="btn sm"><?= icon('send') ?> Demander</button></form><?php else: ?><a class="btn ghost sm" href="<?= e(url('t_finance', ['m' => $month, 'view' => 'eleve', 'sid' => $st['id']])) ?>">Détail</a><?php endif ?></td></tr>
    <?php endforeach ?>
  </table></div>
  <?php endif ?>
</div>

<div class="grid-2 two-cols">
 <div class="col-stack">
  <div class="card"><h2><?= icon('clock') ?> À recevoir</h2>
    <?php if (!$pending): ?><p class="muted">Tout est réglé. 🎉</p><?php endif ?>
    <ul class="pay-list"><?php foreach ($pending as $p) $payItem($p, true) ?></ul>
  </div>
  <div class="card"><h2><?= icon('circle-check') ?> Derniers paiements reçus</h2>
    <?php if (!$paid): ?><p class="muted">Aucun paiement enregistré.</p><?php endif ?>
    <ul class="pay-list"><?php foreach ($paid as $p) $payItem($p, true) ?></ul>
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
      <li>Vos tarifs (Mon compte) s'appliquent à tous ; dans « Par élève », ajustez le tarif, la remise, la 1re séance offerte et les modalités.</li>
      <li>« Demander » facture les séances réalisées pas encore facturées ; le parent est notifié.</li>
      <li>Quand vous recevez le paiement, cliquez sur « Marquer payé ».</li>
    </ol>
  </div>
 </div>
</div>

<?php else: $cur = null; foreach ($rows as $r) if ((int)$r['s']['id'] === $vsid) $cur = $r; ?>
<?php if (!$cur): ?><div class="card"><p class="muted">Aucun élève pour l'instant.</p></div><?php else: ['s' => $st, 'u' => $ub, 'm' => $mo] = $cur; $terms = family_terms($st); ?>
<nav class="subj-tabs" aria-label="Élèves">
  <?php foreach ($rows as $r): ?><a class="subj-tab<?= (int)$r['s']['id'] === $vsid ? ' on' : '' ?>" href="<?= e(url('t_finance', ['m' => $month, 'view' => 'eleve', 'sid' => $r['s']['id']])) ?>"><?= e($r['s']['first_name']) ?><?php if ($r['m']['pending'] > 0): ?> <small><?= e(money($r['m']['pending'])) ?></small><?php endif ?></a><?php endforeach ?>
</nav>

<div class="card"><h2><?= e($st['first_name']) ?> <small class="muted">· <?= e($st['family_name']) ?></small></h2>
  <div class="month-stats">
    <div class="stat"><span class="muted">Encaissé</span><strong><?= money($mo['paid']) ?></strong><small class="muted">sur <?= money($mo['billed']) ?> facturés</small></div>
    <div class="stat"><span class="muted">À recevoir</span><strong><?= money($mo['pending']) ?></strong><?php if ($mo['late'] > 0): ?><small class="err-txt">dont <?= money($mo['late']) ?> en retard</small><?php endif ?></div>
    <div class="stat"><span class="muted">Non facturé</span><strong><?= money($ub['amount']) ?></strong><small class="muted"><?= count($ub['billable']) ?> séance<?= count($ub['billable']) > 1 ? 's' : '' ?></small></div>
    <div class="stat"><span class="muted">Tarif</span><strong class="fin-rate-v"><?= e($rateTxt($st)) ?></strong><?php if ($terms): ?><small class="muted"><?= e($terms) ?></small><?php endif ?></div>
  </div>
</div>

<div class="grid-2 two-cols">
 <div class="col-stack">
  <div class="card"><div class="row between"><h2><?= icon('calendar') ?> Séances non facturées</h2>
    <?php if ($ub['amount'] > 0): ?><form method="post"><?= $hid('create') ?><input type="hidden" name="sid" value="<?= (int)$st['id'] ?>"><input type="hidden" name="notify" value="1"><button class="btn sm"><?= icon('send') ?> Demander <?= money($ub['amount']) ?></button></form><?php endif ?></div>
    <?php if (!$ub['sessions']): ?><p class="muted">Aucune séance en attente de facturation.</p><?php endif ?>
    <ul class="pay-list"><?php foreach ($ub['sessions'] as $x): ?><li><div class="pay-main"><strong><?= e(fdatetime($x['starts_at'])) ?></strong><small class="muted"><?= fdur((int)$x['duration_min']) ?> · <?= e(MODES[$x['mode']] ?? '') ?></small></div>
      <div class="pay-amt"><?php if ($x['free']): ?><span class="badge sage">Offerte</span><?php else: ?><strong><?= $x['amount'] !== null ? money($x['amount']) : '—' ?></strong><?php endif ?></div></li><?php endforeach ?></ul>
    <?php if ($ub['discount'] > 0): ?><p class="muted"><small>Remise appliquée : −<?= money($ub['discount']) ?></small></p><?php endif ?>
  </div>
  <div class="card"><h2><?= icon('file-text') ?> Échéances et paiements</h2>
    <?php if (!$studentPayments): ?><p class="muted">Aucune échéance pour l'instant.</p><?php endif ?>
    <ul class="pay-list"><?php foreach ($studentPayments as $p) $payItem($p + ['first_name' => $st['first_name'], 'pay_method' => $st['pay_method'] ?? null], false) ?></ul>
    <details class="fin-manual"><summary><?= icon('plus') ?> Ajouter une échéance (forfait, stage, frais…)</summary>
      <form method="post" class="stack"><?= $hid('create') ?><input type="hidden" name="manual" value="1"><input type="hidden" name="sid" value="<?= (int)$st['id'] ?>">
        <div class="form-grid">
          <label>Libellé<input name="label" required maxlength="160" placeholder="ex. Stage de révisions (vacances)"></label>
          <label>Montant (€)<input name="amount" required inputmode="decimal" placeholder="ex. 120"></label>
          <label>À régler avant le<input type="date" name="due_date"></label>
          <label>Remise <small class="muted">en € ou en %</small><input name="discount" placeholder="ex. 15 ou 10 %"></label>
          <label>Motif de la remise<input name="discount_label" maxlength="80" placeholder="ex. geste commercial"></label>
        </div>
        <label class="check"><input type="checkbox" name="notify" value="1" checked> Prévenir le parent</label>
        <div><button class="btn sm"><?= icon('plus') ?> Créer l'échéance</button></div>
      </form></details>
  </div>
 </div>
 <div class="col-stack">
  <div class="card editable"><div class="ed-head"><h2><?= icon('settings') ?> Tarif et modalités</h2><button type="button" class="btn ghost sm" data-edit><?= icon('pencil') ?> Modifier</button></div>
    <dl class="fields view">
      <div class="wide"><dt>Tarif</dt><dd><?= e($rateTxt($st)) ?></dd></div>
      <div><dt>Remise</dt><dd><?= (float)($st['discount_pct'] ?? 0) > 0 ? e(discount_text($st['discount_label'] ?? null, (float)$st['discount_pct'])) : '<span class="muted">Aucune</span>' ?></dd></div>
      <div><dt>1re séance</dt><dd><?= !empty($st['first_free']) ? '<span class="badge sage">Offerte</span>' : 'Payante' ?></dd></div>
      <div class="wide"><dt>Paiement</dt><dd><?= $terms !== '' ? e($terms) : '<span class="muted">Non précisé</span>' ?></dd></div>
    </dl>
    <form method="post" class="stack edit-form"><?= $hid('rate') ?><input type="hidden" name="sid" value="<?= (int)$st['id'] ?>">
      <div class="form-grid">
        <label>Tarif / h (€)<input name="hourly_rate" inputmode="decimal" value="<?= e($st['hourly_rate'] ?? (default_rate('presentiel') !== null ? rtrim(rtrim(number_format(default_rate('presentiel'), 2, '.', ''), '0'), '.') : '')) ?>" placeholder="ex. 25"></label>
        <label>Visio / h<input name="hourly_rate_visio" inputmode="decimal" value="<?= e($st['hourly_rate_visio'] ?? (default_rate('visio') !== null ? rtrim(rtrim(number_format(default_rate('visio'), 2, '.', ''), '0'), '.') : '')) ?>" placeholder="—"></label>
        <label>Remise (%)<input name="discount_pct" inputmode="decimal" value="<?= e($st['discount_pct'] ?? '') ?>" placeholder="ex. 10"></label>
        <label>Motif de la remise<input name="discount_label" maxlength="80" value="<?= e($st['discount_label'] ?? '') ?>" placeholder="ex. fratrie"></label>
        <label>Paiement<select name="pay_frequency"><option value="">—</option><?php foreach (PAY_FREQUENCIES as $k => $l): ?><option value="<?= $k ?>" <?= ($st['pay_frequency'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></label>
        <label>Moyen<select name="pay_method"><option value="">—</option><?php foreach (PAY_METHODS as $k => $l): ?><option value="<?= $k ?>" <?= ($st['pay_method'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></label>
      </div>
      <label class="check"><input type="checkbox" name="first_free" value="1" <?= !empty($st['first_free']) ? 'checked' : '' ?>> Première séance offerte</label>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
    </form>
  </div>
 </div>
</div>
<?php endif ?>
<?php endif ?>
