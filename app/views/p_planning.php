<?php $title = 'Agenda'; $isKid = (current_user()['role'] ?? '') === 'student'; ?>
<div class="page-head"><div><h1><?= icon('calendar') ?> Agenda</h1><p><?= $isKid ? 'Tes séances, tes contrôles, tes devoirs et tes révisions, semaine par semaine.' : 'Les séances, contrôles, devoirs et révisions de ' . e(kid($s['first_name'])) . ', semaine par semaine.' ?></p></div></div>
<?php if ($isKid && !empty($recap)) { require __DIR__ . '/_s_recap.php'; echo '<br>'; } ?>
<?php if (function_exists('study_tasks')):
  $studyEdit = $isKid; $studySubjects = array_map(fn($x) => ['id' => $x['id'], 'name' => $x['name']], $subjects ?? []);
  $studyBase = url($isKid ? 's_planning' : 'p_planning'); $study = $study ?? [];
  $agendaSessions = $sessions; $agendaTests = $tests ?? []; $agendaSessionsTab = true;
  require __DIR__ . '/_study.php'; endif ?>
<?php if (schedule_available() && schedule_of($s)): ?><br><details class="sched-fold"><summary><?= icon('refresh-cw') ?> Rythme convenu avec la tutrice</summary><?php $scheduleEdit = false; require __DIR__ . '/_schedule_card.php'; ?></details><?php endif ?>
