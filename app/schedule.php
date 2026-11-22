<?php
declare(strict_types=1);
// Rythme des séances convenu avec la famille : créneaux hebdomadaires, période, génération des séances.

const WEEKDAYS = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

function schedule_available(): bool
{
    static $ok = null;
    if ($ok === null) { try { one('SELECT schedule_json FROM students LIMIT 1'); $ok = true; } catch (PDOException) { $ok = false; } }
    return $ok;
}

/** Rythme enregistré : ['slots' => [[day, time, duration, mode]...], 'start' => Y-m-d, 'weeks' => n, 'note' => '', 'agreed_at' => ...] ou null. */
function schedule_of(array $s): ?array
{
    if (!schedule_available() || empty($s['schedule_json'])) return null;
    $d = json_decode((string)$s['schedule_json'], true);
    return is_array($d) && !empty($d['slots']) ? $d : null;
}

function schedule_minutes_per_week(array $sched): int { return array_sum(array_map(fn($x) => (int)$x['duration'], $sched['slots'])); }

/** Lecture et contrôle du formulaire ; renvoie [rythme, erreur]. */
function schedule_from_post(): array
{
    $days = (array)($_POST['slot_day'] ?? []); $times = (array)($_POST['slot_time'] ?? []); $ends = (array)($_POST['slot_end'] ?? []); $durs = (array)($_POST['slot_duration'] ?? []); $modes = (array)($_POST['slot_mode'] ?? []);
    $slots = [];
    foreach ($days as $i => $day) {
        $day = (int)$day; $time = (string)($times[$i] ?? ''); $dur = (int)($durs[$i] ?? 0);
        $end = (string)($ends[$i] ?? '');
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end)) {
            [$h1, $m1] = array_map('intval', explode(':', $time)); [$h2, $m2] = array_map('intval', explode(':', $end));
            $dur = ($h2 * 60 + $m2) - ($h1 * 60 + $m1);
            if ($dur <= 0) continue; // fin avant le début : créneau ignoré
        } $mode = (string)($modes[$i] ?? 'presentiel');
        if (!isset(WEEKDAYS[$day]) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) continue;
        if (array_filter($slots, fn($x) => $x['day'] === $day && $x['time'] === $time)) continue; // même jour et même heure : un seul créneau
        $slots[] = ['day' => $day, 'time' => $time, 'duration' => max(15, min(480, $dur ?: 60)), 'mode' => isset(MODES[$mode]) ? $mode : 'presentiel'];
        if (count($slots) >= 14) break;
    }
    usort($slots, fn($a, $b) => [$a['day'], $a['time']] <=> [$b['day'], $b['time']]);
    $start = post_str('start', 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = (new DateTime('next monday'))->format('Y-m-d');
    $sched = ['slots' => $slots, 'start' => $start, 'weeks' => max(1, min(52, post_int('weeks') ?: 4)), 'note' => post_str('note', 500), 'agreed_at' => date('Y-m-d')];
    return [$sched, $slots ? null : 'Ajoutez au moins un créneau (jour et heure).'];
}

/** Texte lisible du rythme (pour l'écran et le message au parent). */
function schedule_lines(array $sched): array
{
    return array_map(fn($x) => WEEKDAYS[$x['day']] . ' de ' . str_replace(':', 'h', $x['time']) . ' à ' . str_replace(':', 'h', slot_end($x)) . ' (' . fdur((int)$x['duration']) . ') · ' . MODES[$x['mode']], $sched['slots']);
}

/** Crée les séances du rythme (sans doublon). $replace : supprime d'abord les séances futures encore prévues. Renvoie le nombre créé. */
function schedule_generate(array $s, array $u, array $sched, bool $replace, bool $withGoals): int
{
    $sid = (int)$s['id'];
    if ($replace) q('DELETE FROM sessions WHERE student_id=? AND status="planned" AND starts_at > NOW()', [$sid]);
    $start = DateTime::createFromFormat('Y-m-d', $sched['start']) ?: new DateTime('today');
    $start->setTime(0, 0);
    $monday = (clone $start)->modify('monday this week');
    $an = latest_analysis($sid); $prio = effective_priorities($sid, $an); $reco = recommendations($prio['axes'], $an);
    $weeks = plan_weeks();
    $n = 0;
    for ($w = 0; $w < (int)$sched['weeks']; $w++) {
        foreach ($sched['slots'] as $slot) {
            $dt = (clone $monday)->modify('+' . ($w * 7 + $slot['day'] - 1) . ' days');
            [$h, $m] = array_map('intval', explode(':', $slot['time']));
            $dt->setTime($h, $m);
            if ($dt < $start || $dt->getTimestamp() < time()) continue;
            if (one('SELECT 1 FROM sessions WHERE student_id=? AND starts_at=?', [$sid, $dt->format('Y-m-d H:i:s')])) continue;
            $title = $weeks[$w + 1]['title'] ?? 'Séance de suivi';
            $items = $reco['plan'][$w + 1]['items'] ?? [];
            $obj = isset($weeks[$w + 1]) ? '• ' . $title . ($items ? "\n• " . implode("\n• ", $items) : '') : null;
            q('INSERT INTO sessions (student_id, week_no, title, starts_at, duration_min, mode, objectives) VALUES (?,?,?,?,?,?,?)',
                [$sid, $w + 1, $title, $dt->format('Y-m-d H:i:s'), (int)$slot['duration'], $slot['mode'], $obj]);
            $n++;
        }
    }
    if ($withGoals) {
        $target = (clone $monday)->modify('+' . (min(4, (int)$sched['weeks']) * 7 - 1) . ' days')->format('Y-m-d');
        foreach ($reco['goals'] as $i => $goal) {
            if (one('SELECT 1 FROM goals WHERE student_id=? AND title=?', [$sid, $goal['title']])) continue;
            q('INSERT INTO goals (student_id, title, description, category, target_date, is_mission, created_by) VALUES (?,?,?,?,?,?,?)',
                [$sid, $goal['title'], $goal['description'], $goal['category'], $target, $i % 2 === 0 ? 1 : 0, $u['id']]);
        }
    }
    return $n;
}

/**
 * Ajoute un créneau de report à une séance déjà prévue le même jour (au lieu d'en créer une nouvelle).
 * Collé avant : la séance commence plus tôt ; chevauchant ou collé après : elle finit plus tard ; sinon : prolongée d'autant.
 * Renvoie l'id de la séance prolongée, ou null s'il n'y a pas de séance ce jour-là.
 */
function merge_report_into_day(int $sid, DateTime $dt, int $dur, int $origId, int $excludeId = 0): ?int
{
    $day = $dt->format('Y-m-d');
    $cands = all('SELECT * FROM sessions WHERE student_id=? AND status="planned" AND DATE(starts_at)=? AND id<>? AND id<>? ORDER BY starts_at', [$sid, $day, $origId, $excludeId]);
    if (!$cands) return null;
    $new0 = $dt->getTimestamp(); $new1 = $new0 + $dur * 60;
    // la séance la plus proche du créneau
    usort($cands, fn($a, $b) => min(abs(strtotime($a['starts_at']) - $new1), abs(strtotime($a['starts_at']) + $a['duration_min'] * 60 - $new0))
                             <=> min(abs(strtotime($b['starts_at']) - $new1), abs(strtotime($b['starts_at']) + $b['duration_min'] * 60 - $new0)));
    $se = $cands[0];
    $s0 = strtotime($se['starts_at']); $s1 = $s0 + (int)$se['duration_min'] * 60;
    if ($new1 >= $s0 && $new0 <= $s1) { $start = min($s0, $new0); $end = max($s1, $new1); }   // collé ou chevauchant
    else { $start = $s0; $end = $s1 + $dur * 60; }                                               // même jour, à un autre moment
    q('UPDATE sessions SET starts_at=?, duration_min=?, merged_report_from=?, merged_report_min=COALESCE(merged_report_min,0)+? WHERE id=?',
        [date('Y-m-d H:i:00', $start), min(600, (int)round(($end - $start) / 60)), $origId, $dur, $se['id']]);
    return (int)$se['id'];
}

/** Rattrape les reports déjà créés comme séances séparées le jour d'une autre séance prévue. Renvoie le nombre de fusions. */
function merge_existing_reports(?int $sid = null): int
{
    $n = 0;
    foreach (all('SELECT * FROM sessions WHERE rescheduled_from IS NOT NULL AND status="planned"' . ($sid ? ' AND student_id=' . (int)$sid : '') . ' ORDER BY starts_at') as $r) {
        $dt = new DateTime($r['starts_at']);
        if (merge_report_into_day((int)$r['student_id'], $dt, (int)$r['duration_min'], (int)$r['rescheduled_from'], (int)$r['id'])) {
            q('DELETE FROM sessions WHERE id=?', [$r['id']]); $n++;
        }
    }
    return $n;
}

/** Heure de fin d'un créneau (« HH:MM »). */
function slot_end(array $x): string
{
    [$h, $m] = array_map('intval', explode(':', $x['time']));
    $t = $h * 60 + $m + (int)$x['duration'];
    return sprintf('%02d:%02d', intdiv($t, 60) % 24, $t % 60);
}
