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
    $days = (array)($_POST['slot_day'] ?? []); $times = (array)($_POST['slot_time'] ?? []); $durs = (array)($_POST['slot_duration'] ?? []); $modes = (array)($_POST['slot_mode'] ?? []);
    $slots = [];
    foreach ($days as $i => $day) {
        $day = (int)$day; $time = (string)($times[$i] ?? ''); $dur = (int)($durs[$i] ?? 0); $mode = (string)($modes[$i] ?? 'presentiel');
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
    return array_map(fn($x) => WEEKDAYS[$x['day']] . ' à ' . str_replace(':', 'h', $x['time']) . ' · ' . fdur((int)$x['duration']) . ' · ' . MODES[$x['mode']], $sched['slots']);
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
