<?php
// Finances : tarifs, montants à régler, suivi des paiements.

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
function rate_for(array $s, string $mode): ?float
{
    $r = $mode === 'visio' && ($s['hourly_rate_visio'] ?? null) !== null ? $s['hourly_rate_visio'] : ($s['hourly_rate'] ?? null);
    return $r === null ? null : (float)$r;
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
    return ['sessions' => $rows, 'minutes' => $min, 'amount' => round($amount, 2), 'missing_rate' => $missingRate, 'planned_min' => array_sum(array_column($planned, 'duration_min')), 'planned_amount' => round($plannedAmount, 2)];
}
function payments_of(int $sid): array
{
    try { return all('SELECT * FROM payments WHERE student_id=? ORDER BY status="paid", COALESCE(due_date, created_at) DESC, id DESC', [$sid]); } catch (PDOException) { return []; }
}
function tax_credit_on(): bool { return setting('tax_credit', '0') === '1'; }
