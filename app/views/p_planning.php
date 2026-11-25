<?php $title = 'Planning';
$done = count(array_filter($sessions, fn($x) => $x['status'] === 'done'));
$upcoming = array_values(array_filter($sessions, fn($x) => strtotime($x['starts_at']) >= time() && $x['status'] === 'planned'));
$total = array_sum(array_map(fn($x) => $x['status'] !== 'cancelled' ? (int)$x['duration_min'] : 0, $sessions)); ?>
<div class="page-head"><div><h1><?= icon('calendar') ?> Agenda</h1><p><?= (current_user()['role'] ?? '') === 'student' ? 'Tes séances, tes devoirs, tes révisions et tes contrôles au même endroit.' : 'Les séances, devoirs, révisions et contrôles de ' . e(kid($s['first_name'])) . '.' ?></p></div></div>
<div class="grid stat-grid">
  <div class="card stat"><span class="muted">Prochaine séance</span><strong><?= $upcoming ? e(fdatetime($upcoming[0]['starts_at'])) : '-' ?></strong><?php if ($upcoming): ?><small class="muted"><?= icon($upcoming[0]['mode'] === 'visio' ? 'video' : 'map-pin') ?> <?= e(MODES[$upcoming[0]['mode']]) ?> · <?= fdur((int)$upcoming[0]['duration_min']) ?></small><?php endif ?></div>
  <div class="card stat"><span class="muted">Séances réalisées</span><strong><?= $done ?> / <?= count(array_filter($sessions, fn($x) => $x['status'] !== 'cancelled')) ?></strong></div>
  <div class="card stat"><span class="muted">Volume prévu</span><strong><?= fdur($total) ?></strong></div>
</div><br>
<?php if ((current_user()['role'] ?? '') === 'student') { require __DIR__ . '/_s_recap.php'; echo '<br>'; } ?>
<?php $scheduleEdit = false; if (schedule_available() && schedule_of($s)) require __DIR__ . '/_schedule_card.php'; ?>
<?php if (function_exists('study_tasks')): $studyEdit = (current_user()['role'] ?? '') === 'student'; $studySubjects = array_map(fn($x) => ['id' => $x['id'], 'name' => $x['name']], $subjects ?? []); $studyBase = url($studyEdit ? 's_planning' : 'p_planning'); $study = $study ?? []; $agendaSessions = $sessions; $agendaTests = $tests ?? []; require __DIR__ . '/_study.php'; ?><br><?php endif ?>
<div class="card"><?php require __DIR__ . '/_planning.php' ?></div>
