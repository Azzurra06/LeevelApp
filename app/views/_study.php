<?php // Agenda : séances, contrôles, devoirs et révisions. $s, $study, $studyEdit (bool), $studySubjects ([id, name]), $studyBase (url de la page),
// $agendaSessions, $agendaTests ; facultatif : $agendaSessionsTab (true : onglet « Séances » avec le détail des séances, élève/parent)
$today = date('Y-m-d');
$av = in_array($_GET['av'] ?? '', ['week', 'month', 'todo', 'sessions'], true) ? $_GET['av'] : 'week';
if ($av === 'sessions' && empty($agendaSessionsTab)) $av = 'week';
$wk = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['wk'] ?? '')) ? $_GET['wk'] : $today;
$sm = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['sm'] ?? '')) ? $_GET['sm'] : null; $month = $sm ?? date('Y-m');
$agendaSessions ??= []; $agendaTests ??= [];
$todo = array_values(array_filter($study, fn($t) => !$t['done_at']));
$done = array_reverse(array_values(array_filter($study, fn($t) => (bool)$t['done_at'])));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$groups = ['En retard' => [], "Aujourd'hui" => [], 'Cette semaine' => [], 'Plus tard' => []];
foreach ($todo as $t) $groups[$t['due_date'] < $today ? 'En retard' : ($t['due_date'] === $today ? "Aujourd'hui" : ($t['due_date'] <= $weekEnd ? 'Cette semaine' : 'Plus tard'))][] = $t;
$late = count($groups['En retard']);
$dayNames = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
$day = fn(string $d) => ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'][(int)date('w', strtotime($d))] . ' ' . date('d/m', strtotime($d));
$link = fn(array $p) => e($studyBase . '&' . http_build_query($p)) . '#devoirs';
$hid = fn(array $x = []) => csrf_field() . '<input type="hidden" name="sid" value="' . (int)$s['id'] . '"><input type="hidden" name="av" value="' . e($av) . '"><input type="hidden" name="wk" value="' . e($wk) . '">' . ($sm ? '<input type="hidden" name="sm" value="' . e($sm) . '">' : '') . implode('', array_map(fn($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string)$v) . '">', array_keys($x), $x));
// événements datés (séances, contrôles) par jour
$evDay = [];
foreach ($agendaSessions as $se) if (in_array($se['status'], ['planned', 'done'], true)) {
    $d = substr($se['starts_at'], 0, 10);
    $evDay[$d][] = ['c' => 'sess', 'time' => date('H\hi', strtotime($se['starts_at'])), 'title' => session_label($se), 'sub' => fdur((int)$se['duration_min']) . ' · ' . (MODES[$se['mode']] ?? ''), 'sort' => $se['starts_at']];
}
foreach ($agendaTests as $te) $evDay[$te['test_date']][] = ['c' => 'test', 'time' => 'Contrôle', 'title' => $te['subject'] ?: 'Contrôle', 'sub' => $te['title'] ?? '', 'sort' => $te['test_date'] . ' 00:00'];
$taskDay = []; foreach ($study as $t) $taskDay[$t['due_date']][] = $t;

$form = function (?array $t) use ($hid, $studySubjects) { ?>
  <form method="post" action="<?= e(url('study')) ?>" class="stack study-form"><?= $hid(['action' => 'save'] + ($t ? ['task_id' => $t['id']] : [])) ?>
    <div class="seg seg-tight study-kind" role="radiogroup" aria-label="Type"><?php foreach (STUDY_KINDS as $k => $l): ?><label class="seg-opt"><input type="radio" name="kind" value="<?= $k ?>" <?= ($t['kind'] ?? 'devoir') === $k ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach ?></div>
    <div class="form-grid">
      <label>Intitulé<input name="title" maxlength="200" required value="<?= e($t['title'] ?? '') ?>" placeholder="ex. Exercices 4 et 5 p. 82"></label>
      <label>Pour le<input type="date" name="due_date" required value="<?= e($t['due_date'] ?? date('Y-m-d', strtotime('+1 day'))) ?>"></label>
      <?php if ($studySubjects): ?><label>Matière<select name="subject_id"><option value="">—</option><?php foreach ($studySubjects as $x): ?><option value="<?= (int)$x['id'] ?>" <?= (int)($t['subject_id'] ?? 0) === (int)$x['id'] ? 'selected' : '' ?>><?= e($x['name']) ?></option><?php endforeach ?></select></label><?php endif ?>
    </div>
    <label>Détails (facultatif)<textarea name="details" rows="2" maxlength="2000" placeholder="ex. Relire la leçon avant, refaire l'exemple du cours"><?= e($t['details'] ?? '') ?></textarea></label>
    <div class="row"><button class="btn sm"><?= icon('save') ?> <?= $t ? 'Enregistrer' : 'Ajouter' ?></button></div>
  </form>
<?php };
$item = function (array $t, bool $showDate = true) use ($hid, $studyEdit, $day, $today, $form) { $isLate = !$t['done_at'] && $t['due_date'] < $today; ?>
  <li class="study-item<?= $t['done_at'] ? ' done' : '' ?><?= $isLate ? ' late' : '' ?>">
    <?php if ($studyEdit): ?><form method="post" action="<?= e(url('study')) ?>" class="study-check"><?= $hid(['action' => 'toggle', 'task_id' => $t['id']]) ?><button class="check-btn" aria-label="<?= $t['done_at'] ? 'Marquer à faire' : 'Marquer comme fait' ?>" title="<?= $t['done_at'] ? 'Marquer à faire' : 'Marquer comme fait' ?>"><?= icon('check') ?></button></form>
    <?php else: ?><span class="check-btn ro" aria-hidden="true"><?= $t['done_at'] ? icon('check') : '' ?></span><?php endif ?>
    <div class="study-main">
      <div class="study-top"><span class="badge <?= $t['kind'] === 'revision' ? 'rev' : 'sage' ?>"><?= e(STUDY_KINDS[$t['kind']]) ?></span><?php if ($t['subject']): ?><span class="study-subj"><?= e($t['subject']) ?></span><?php endif ?></div>
      <strong class="study-title"><?= e($t['title']) ?></strong>
      <?php if ($t['details']): ?><p class="study-det"><?= nl2br(e($t['details'])) ?></p><?php endif ?>
      <?php if ($showDate || $t['done_at'] || $t['author_role'] === 'tutor'): ?><small class="muted"><?= $t['done_at'] ? 'Fait le ' . e(fdate($t['done_at'])) : ($showDate ? ($isLate ? 'En retard · ' : '') . 'Pour le ' . e($day($t['due_date'])) : '') ?><?= $t['author_role'] === 'tutor' ? ($showDate || $t['done_at'] ? ' · ' : '') . 'ajouté par la tutrice' : '' ?></small><?php endif ?>
    </div>
    <?php if ($studyEdit): ?><div class="study-acts">
      <details class="study-edit"><summary class="edit-btn" title="Modifier" aria-label="Modifier"><?= icon('pencil') ?></summary><div class="study-pop"><?php $form($t) ?></div></details>
      <form method="post" action="<?= e(url('study')) ?>" data-confirm="Supprimer ?"><?= $hid(['action' => 'delete', 'task_id' => $t['id']]) ?><button class="edit-btn" aria-label="Supprimer"><?= icon('trash-2') ?></button></form>
    </div><?php endif ?>
  </li>
<?php };
$ev = function (array $e) { ?>
  <div class="ag-ev <?= $e['c'] ?>"><span class="ag-time"><?= e($e['time']) ?></span><div><strong><?= $e['c'] === 'sess' ? 'Séance · ' : '' ?><?= e($e['title']) ?></strong><?php if ($e['sub'] !== ''): ?><small><?= e($e['sub']) ?></small><?php endif ?></div></div>
<?php }; ?>
<div class="card agenda-card" id="devoirs">
  <div class="ag-top">
    <nav class="ag-tabs" aria-label="Vue de l'agenda">
      <a href="<?= $link(['av' => 'week']) ?>" class="<?= $av === 'week' ? 'on' : '' ?>"><?= icon('calendar') ?> Semaine</a>
      <a href="<?= $link(['av' => 'month']) ?>" class="<?= $av === 'month' ? 'on' : '' ?>"><?= icon('calendar') ?> Mois</a>
      <a href="<?= $link(['av' => 'todo']) ?>" class="<?= $av === 'todo' ? 'on' : '' ?>"><?= icon('list-checks') ?> À faire <?php if ($todo): ?><small class="<?= $late ? 'late' : '' ?>"><?= count($todo) ?></small><?php endif ?></a>
      <?php if (!empty($agendaSessionsTab)): ?><a href="<?= $link(['av' => 'sessions']) ?>" class="<?= $av === 'sessions' ? 'on' : '' ?>"><?= icon('clock') ?> Séances</a><?php endif ?>
    </nav>
    <?php if ($studyEdit): ?><button type="button" class="btn sm" data-toggle="study-new"><?= icon('plus') ?> Devoir / révision</button><?php endif ?>
  </div>
  <?php if ($studyEdit): ?><div id="study-new" class="new-panel" hidden><?php $form(null) ?></div><?php endif ?>

  <?php if ($av === 'week'): $mon = week_monday($wk); ?>
    <div class="ag-nav"><a class="edit-btn" href="<?= $link(['av' => 'week', 'wk' => date('Y-m-d', strtotime($mon . ' -7 days'))]) ?>" aria-label="Semaine précédente"><?= icon('chevron-left') ?></a>
      <strong>Semaine <?= e(week_label($mon)) ?></strong>
      <a class="edit-btn" href="<?= $link(['av' => 'week', 'wk' => date('Y-m-d', strtotime($mon . ' +7 days'))]) ?>" aria-label="Semaine suivante"><?= icon('chevron-right') ?></a>
      <?php if ($mon !== week_monday()): ?><a class="more-btn" href="<?= $link(['av' => 'week']) ?>">Cette semaine</a><?php endif ?></div>
    <div class="ag-week">
      <?php for ($i = 0; $i < 7; $i++): $d = date('Y-m-d', strtotime($mon . " +$i days")); $evs = $evDay[$d] ?? []; usort($evs, fn($a, $b) => strcmp($a['sort'], $b['sort'])); $tks = $taskDay[$d] ?? []; ?>
        <div class="ag-day<?= $d === $today ? ' today' : '' ?><?= $d < $today ? ' past' : '' ?>">
          <div class="ag-dlabel"><strong><?= ucfirst($dayNames[(int)date('w', strtotime($d))]) ?></strong><small><?= date('d/m', strtotime($d)) ?><?= $d === $today ? " · aujourd'hui" : '' ?></small></div>
          <div class="ag-items">
            <?php foreach ($evs as $e) $ev($e) ?>
            <?php if ($tks): ?><ul class="study-list"><?php foreach ($tks as $t) $item($t, false) ?></ul><?php endif ?>
            <?php if (!$evs && !$tks): ?><span class="ag-empty">—</span><?php endif ?>
          </div>
        </div>
      <?php endfor ?>
    </div>
    <?php if ($late): ?><p class="ag-late-note"><?= icon('triangle-alert') ?> <?= $late ?> devoir<?= $late > 1 ? 's' : '' ?> en retard : <a href="<?= $link(['av' => 'todo']) ?>">voir « À faire »</a></p><?php endif ?>

  <?php elseif ($av === 'month'): $first = strtotime($month . '-01'); $start = strtotime('monday this week', $first);
    $prev = date('Y-m', strtotime('-1 month', $first)); $next = date('Y-m', strtotime('+1 month', $first));
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']; ?>
    <div class="ag-nav"><a class="edit-btn" href="<?= $link(['av' => 'month', 'sm' => $prev]) ?>" aria-label="Mois précédent"><?= icon('chevron-left') ?></a><strong><?= ucfirst($mois[(int)date('n', $first)]) ?> <?= date('Y', $first) ?></strong><a class="edit-btn" href="<?= $link(['av' => 'month', 'sm' => $next]) ?>" aria-label="Mois suivant"><?= icon('chevron-right') ?></a></div>
    <div class="study-cal"><?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dn): ?><div class="sc-h"><?= $dn ?></div><?php endforeach ?>
      <?php for ($i = 0; $i < 42; $i++): $d = date('Y-m-d', strtotime("+$i day", $start)); if ($i >= 35 && substr($d, 0, 7) !== $month) break; ?>
        <a class="sc-d<?= substr($d, 0, 7) !== $month ? ' out' : '' ?><?= $d === $today ? ' today' : '' ?>" href="<?= $link(['av' => 'week', 'wk' => $d]) ?>"><span class="sc-n"><?= (int)substr($d, 8) ?></span>
          <?php foreach ($evDay[$d] ?? [] as $e): ?><span class="sc-t <?= $e['c'] ?>"><?= e($e['c'] === 'sess' ? $e['time'] . ' Séance' : 'Contrôle · ' . $e['title']) ?></span><?php endforeach ?>
          <?php foreach ($taskDay[$d] ?? [] as $t): ?><span class="sc-t<?= $t['done_at'] ? ' done' : '' ?><?= $t['kind'] === 'revision' ? ' rev' : '' ?>"><?= e(($t['subject'] ? $t['subject'] . ' · ' : '') . $t['title']) ?></span><?php endforeach ?></a>
      <?php endfor ?></div>
    <p class="study-legend muted"><small><span class="sc-t sess">Séance</span> <span class="sc-t test">Contrôle</span> <span class="sc-t">Devoir</span> <span class="sc-t rev">Révision</span> <span class="sc-t done">Fait</span> · Clique sur un jour pour voir sa semaine.</small></p>

  <?php elseif ($av === 'todo'): ?>
    <?php if (!$todo): ?><p class="muted study-empty"><?= $studyEdit ? 'Rien à faire pour le moment.' : 'Aucun devoir ni révision prévu.' ?></p><?php endif ?>
    <?php foreach ($groups as $g => $list): if (!$list) continue; ?>
      <h3 class="study-gh<?= $g === 'En retard' ? ' late' : '' ?>"><?= e($g) ?> <span class="muted">· <?= count($list) ?></span></h3>
      <ul class="study-list"><?php foreach ($list as $t) $item($t) ?></ul>
    <?php endforeach ?>
    <?php if ($done): ?><details class="study-done"><summary>Faits <span class="muted">· <?= count($done) ?></span></summary><ul class="study-list"><?php foreach (array_slice($done, 0, 30) as $t) $item($t) ?></ul></details><?php endif ?>

  <?php else: $sessions = $agendaSessions; ?>
    <?php require __DIR__ . '/_planning.php' ?>
  <?php endif ?>
</div>
