<?php
// Finances : tarifs, montants à régler, suivi des paiements.

const PAY_FREQUENCIES = ['session' => 'Après chaque séance', 'weekly' => 'Chaque semaine', 'monthly' => 'Chaque mois', 'package' => 'Par forfait (à l\'avance)'];
const PAY_METHODS = ['virement' => 'Virement', 'especes' => 'Espèces', 'cheque' => 'Chèque', 'cesu' => 'CESU', 'urssaf' => 'Avance immédiate (Urssaf)', 'autre' => 'Autre'];

function setting(string $k, ?string $default = null): ?string
{
    try { $r = one('SELECT v FROM app_settings WHERE k=?', [$k]); return $r ? $r['v'] : $default; } catch (PDOException) { return $default; }
}
function setting_set(string $k, ?string $v): void
{
    q('INSERT INTO app_settings (k, v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)', [$k, $v]);
}
function finance_available(): bool
{
    try { db()->query('SELECT 1 FROM payments LIMIT 1'); return true; } catch (PDOException) { return false; }
}
function money(float $v): string
{
    return (fmod($v, 1.0) == 0.0 ? number_format($v, 0, ',', ' ') : number_format($v, 2, ',', ' ')) . ' €';
}
/** Tarifs par défaut de la tutrice (Mon compte), repris quand l'élève n'a pas de tarif propre. */
function default_rate(string $mode): ?float
{
    $v = setting($mode === 'visio' ? 'default_rate_visio' : 'default_rate');
    if (($v === null || $v === '') && $mode === 'visio') $v = setting('default_rate');
    return $v === null || $v === '' ? null : (float)$v;
}
function rate_for(array $s, string $mode): ?float
{
    if ($mode === 'visio' && ($s['hourly_rate_visio'] ?? null) !== null) return (float)$s['hourly_rate_visio'];
    if (($s['hourly_rate'] ?? null) !== null) return (float)$s['hourly_rate'];
    return default_rate($mode);
}
function rate_is_default(array $s): bool { return ($s['hourly_rate'] ?? null) === null && default_rate('presentiel') !== null; }
/** Séances réalisées pas encore couvertes par une échéance (quel que soit le rythme de paiement). */
function unbilled(array $s): array
{
    try { $last = one('SELECT MAX(period_end) d FROM payments WHERE student_id=? AND period_end IS NOT NULL', [$s['id']])['d'] ?? null; } catch (PDOException) { $last = null; }
    $rows = all('SELECT id, starts_at, duration_min, mode FROM sessions WHERE student_id=? AND starts_at < NOW() AND (status="done" OR status="planned")' . ($last ? ' AND DATE(starts_at) > ?' : '') . ' ORDER BY starts_at', $last ? [$s['id'], $last] : [$s['id']]);
    $gross = 0.0; $min = 0; $missing = false;
    foreach ($rows as $x) { $r = rate_for($s, $x['mode']); if ($r === null) $missing = true; else $gross += $r * (int)$x['duration_min'] / 60; $min += (int)$x['duration_min']; }
    $pct = (float)($s['discount_pct'] ?? 0); $gross = round($gross, 2); $disc = $pct > 0 ? round($gross * $pct / 100, 2) : 0.0;
    return ['sessions' => $rows, 'minutes' => $min, 'gross' => $gross, 'discount' => $disc, 'amount' => round($gross - $disc, 2), 'missing_rate' => $missing,
            'start' => $rows ? substr($rows[0]['starts_at'], 0, 10) : null, 'end' => $rows ? substr(end($rows)['starts_at'], 0, 10) : null];
}
/** Libellé d'une échéance selon les séances couvertes. */
function unbilled_label(array $u): string
{
    $n = count($u['sessions']);
    if ($n === 1) return 'Séance du ' . fdate($u['start']);
    if (substr($u['start'], 0, 7) === substr($u['end'], 0, 7) && substr($u['start'], 8) <= '07') return 'Séances de ' . mb_strtolower(month_label(substr($u['start'], 0, 7)));
    return $n . ' séances du ' . date('d/m', strtotime($u['start'])) . ' au ' . date('d/m', strtotime($u['end']));
}
function family_terms(array $s): string
{
    $f = trim((string)($s['pay_frequency'] ?? '')); $m = trim((string)($s['pay_method'] ?? ''));
    return implode(' · ', array_filter([PAY_FREQUENCIES[$f] ?? '', PAY_METHODS[$m] ?? '']));
}
/** Séances réalisées d'un mois et montant correspondant. */
function month_billing(array $s, string $month): array
{
    $start = $month . '-01'; $end = date('Y-m-d', strtotime($start . ' +1 month'));
    $rows = all('SELECT id, starts_at, duration_min, mode FROM sessions WHERE student_id=? AND starts_at >= ? AND starts_at < ? AND (status="done" OR (status="planned" AND starts_at < NOW())) ORDER BY starts_at', [$s['id'], $start, $end]);
    $min = 0; $amount = 0.0; $missingRate = false;
    foreach ($rows as &$x) {
        $rate = rate_for($s, $x['mode']);
        $x['amount'] = $rate === null ? null : round($rate * (int)$x['duration_min'] / 60, 2);
        if ($rate === null) $missingRate = true; else $amount += $x['amount'];
        $min += (int)$x['duration_min'];
    }
    unset($x);
    $planned = all('SELECT duration_min, mode FROM sessions WHERE student_id=? AND starts_at >= ? AND starts_at < ? AND status="planned" AND starts_at >= NOW()', [$s['id'], max($start, date('Y-m-d H:i:s')), $end]);
    $plannedAmount = 0.0; foreach ($planned as $x) $plannedAmount += (rate_for($s, $x['mode']) ?? 0) * (int)$x['duration_min'] / 60;
    $pct = (float)($s['discount_pct'] ?? 0);
    $disc = $pct > 0 ? round($amount * $pct / 100, 2) : 0.0;
    return ['sessions' => $rows, 'minutes' => $min, 'gross' => round($amount, 2), 'discount' => $disc, 'discount_pct' => $pct, 'discount_label' => (string)($s['discount_label'] ?? ''),
            'amount' => round($amount - $disc, 2), 'missing_rate' => $missingRate, 'planned_min' => array_sum(array_column($planned, 'duration_min')), 'planned_amount' => round($plannedAmount, 2)];
}
function payments_of(int $sid): array
{
    try { return all('SELECT * FROM payments WHERE student_id=? ORDER BY status="paid", COALESCE(due_date, created_at) DESC, id DESC', [$sid]); } catch (PDOException) { return []; }
}
/** « Remise fratrie (-10 %) » */
function discount_text(?string $label, float $pct = 0): string
{
    return 'Remise' . ($label ? ' ' . $label : '') . ($pct > 0 ? ' (−' . rtrim(rtrim(number_format($pct, 2, ',', ''), '0'), ',') . ' %)' : '');
}
function tax_credit_on(): bool { return setting('tax_credit', '0') === '1'; }
