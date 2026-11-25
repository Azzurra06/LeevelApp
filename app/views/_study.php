<?php // Planning devoirs & révisions. $s, $study, $studyEdit (bool), $studySubjects ([id, name]), $studyBase (url de la page)
$today = date('Y-m-d'); $sm = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['sm'] ?? '')) ? $_GET['sm'] : null; $month = $sm ?? date('Y-m');
$todo = array_values(array_filter($study, fn($t) => !$t['done_at']));
$done = array_reverse(array_values(array_filter($study, fn($t) => (bool)$t['done_at'])));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$groups = ['En retard' => [], "Aujourd'hui" => [], 'Cette semaine' => [], 'Plus tard' => []];
foreach ($todo as $t) $groups[$t['due_date'] < $today ? 'En retard' : ($t['due_date'] === $today ? "Aujourd'hui" : ($t['due_date'] <= $weekEnd ? 'Cette semaine' : 'Plus tard'))][] = $t;
$day = fn(string $d) => ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'][(int)date('w', strtotime($d))] . ' ' . date('d/m', strtotime($d));
$hid = fn(array $x = []) => csrf_field() . '<input type="hidden" name="sid" value="' . (int)$s['id'] . '">' . ($sm ? '<input type="hidden" name="sm" value="' . e($sm) . '">' : '') . implode('', array_map(fn($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string)$v) . '">', array_keys($x), $x));
$form = function (?array $t) use ($hid, $studySubjects, $today) { ?>
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
$item = function (array $t) use ($hid, $studyEdit, $day, $today, $form) { $late = !$t['done_at'] && $t['due_date'] < $today; ?>
  <li class="study-item<?= $t['done_at'] ? ' done' : '' ?><?= $late ? ' late' : '' ?>">
    <?php if ($studyEdit): ?><form method="post" action="<?= e(url('study')) ?>" class="study-check"><?= $hid(['action' => 'toggle', 'task_id' => $t['id']]) ?><button class="check-btn" aria-label="<?= $t['done_at'] ? 'Marquer à faire' : 'Marquer comme fait' ?>" title="<?= $t['done_at'] ? 'Marquer à faire' : 'Marquer comme fait' ?>"><?= icon('check') ?></button></form>
    <?php else: ?><span class="check-btn ro" aria-hidden="true"><?= $t['done_at'] ? icon('check') : '' ?></span><?php endif ?>
    <div class="study-main">
      <div class="study-top"><span class="badge <?= $t['kind'] === 'revision' ? 'rev' : 'sage' ?>"><?= e(STUDY_KINDS[$t['kind']]) ?></span><?php if ($t['subject']): ?><span class="study-subj"><?= e($t['subject']) ?></span><?php endif ?></div>
      <strong class="study-title"><?= e($t['title']) ?></strong>
      <?php if ($t['details']): ?><p class="study-det"><?= nl2br(e($t['details'])) ?></p><?php endif ?>
      <small class="muted"><?= $t['done_at'] ? 'Fait le ' . e(fdate($t['done_at'])) : ($late ? 'En retard · ' : '') . 'Pour le ' . e($day($t['due_date'])) ?><?= $t['author_role'] === 'tutor' ? ' · ajouté par la tutrice' : '' ?></small>
    </div>
    <?php if ($studyEdit): ?><div class="study-acts">
      <details class="study-edit"><summary class="edit-btn" title="Modifier" aria-label="Modifier"><?= icon('pencil') ?></summary><div class="study-pop"><?php $form($t) ?></div></details>
      <form method="post" action="<?= e(url('study')) ?>" data-confirm="Supprimer ?"><?= $hid(['action' => 'delete', 'task_id' => $t['id']]) ?><button class="edit-btn" aria-label="Supprimer"><?= icon('trash-2') ?></button></form>
    </div><?php endif ?>
  </li>
<?php }; ?>
<div class="card study-card" id="devoirs">
  <div class="row between study-head"><h2><?= icon('list-checks') ?> <?= isset($agendaSessions) ? 'Devoirs, révisions et calendrier' : 'Devoirs et révisions' ?></h2>
    <?php if ($studyEdit): ?><button type="button" class="btn sm" data-toggle="study-new"><?= icon('plus') ?> Ajouter</button><?php endif ?></div>
  <?php if ($studyEdit): ?><div id="study-new" class="new-panel" hidden><?php $form(null) ?></div><?php endif ?>
  <div data-views>
    <div class="seg"><button type="button" class="<?= $sm ? '' : 'on' ?>" data-view="slist"><?= icon('list-checks') ?> Liste</button><button type="button" class="<?= $sm ? 'on' : '' ?>" data-view="scal"><?= icon('calendar') ?> Calendrier</button></div>
    <div data-pane="slist" <?= $sm ? 'hidden' : '' ?>>
      <?php if (!$todo): ?><p class="muted study-empty"><?= $studyEdit ? 'Rien à faire pour le moment. Ajoute tes devoirs et tes révisions pour t\'organiser.' : 'Aucun devoir ni révision prévu.' ?></p><?php endif ?>
      <?php foreach ($groups as $g => $list): if (!$list) continue; ?>
        <h3 class="study-gh<?= $g === 'En retard' ? ' late' : '' ?>"><?= e($g) ?> <span class="muted">· <?= count($list) ?></span></h3>
        <ul class="study-list"><?php foreach ($list as $t) $item($t) ?></ul>
      <?php endforeach ?>
      <?php if ($done): ?><details class="study-done"><summary>Faits <span class="muted">· <?= count($done) ?></span></summary><ul class="study-list"><?php foreach (array_slice($done, 0, 30) as $t) $item($t) ?></ul></details><?php endif ?>
    </div>
    <div data-pane="scal" <?= $sm ? '' : 'hidden' ?>>
      <?php $first = strtotime($month . '-01'); $start = strtotime('monday this week', $first); $byDay = [];
        foreach ($study as $t) $byDay[$t['due_date']][] = $t;
        $evDay = [];
        foreach ($agendaSessions ?? [] as $se) if (in_array($se['status'], ['planned', 'done'], true)) $evDay[substr($se['starts_at'], 0, 10)][] = ['c' => 'sess', 't' => date('H\hi', strtotime($se['starts_at'])) . ' Séance', 'h' => session_label($se)];
        foreach ($agendaTests ?? [] as $te) $evDay[$te['test_date']][] = ['c' => 'test', 't' => 'Contrôle' . (!empty($te['subject']) ? ' · ' . $te['subject'] : ''), 'h' => trim(($te['subject'] ?? '') . ' ' . ($te['label'] ?? $te['title'] ?? ''))];
        $prev = date('Y-m', strtotime('-1 month', $first)); $next = date('Y-m', strtotime('+1 month', $first));
        $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']; ?>
      <div class="study-cal-nav"><a class="edit-btn" href="<?= e($studyBase . '&sm=' . $prev) ?>#devoirs" aria-label="Mois précédent"><?= icon('chevron-left') ?></a><strong><?= ucfirst($mois[(int)date('n', $first)]) ?> <?= date('Y', $first) ?></strong><a class="edit-btn" href="<?= e($studyBase . '&sm=' . $next) ?>#devoirs" aria-label="Mois suivant"><?= icon('chevron-right') ?></a></div>
      <div class="study-cal"><?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $d): ?><div class="sc-h"><?= $d ?></div><?php endforeach ?>
        <?php for ($i = 0; $i < 42; $i++): $d = date('Y-m-d', strtotime("+$i day", $start)); if ($i >= 35 && substr($d, 0, 7) !== $month) break; ?>
          <div class="sc-d<?= substr($d, 0, 7) !== $month ? ' out' : '' ?><?= $d === $today ? ' today' : '' ?>"><span class="sc-n"><?= (int)substr($d, 8) ?></span>
            <?php foreach ($evDay[$d] ?? [] as $ev): ?><span class="sc-t <?= $ev['c'] ?>" title="<?= e($ev['h']) ?>"><?= e($ev['t']) ?></span><?php endforeach ?>
            <?php foreach ($byDay[$d] ?? [] as $t): ?><span class="sc-t<?= $t['done_at'] ? ' done' : '' ?><?= $t['kind'] === 'revision' ? ' rev' : '' ?>" title="<?= e(STUDY_KINDS[$t['kind']] . ($t['subject'] ? ' · ' . $t['subject'] : '') . ' : ' . $t['title']) ?>"><?= e($t['subject'] ? $t['subject'] . ' · ' : '') ?><?= e($t['title']) ?></span><?php endforeach ?></div>
        <?php endfor ?></div>
      <p class="study-legend muted"><small><?php if (isset($agendaSessions)): ?><span class="sc-t sess">Séance</span> <span class="sc-t test">Contrôle</span> <?php endif ?><span class="sc-t">Devoir</span> <span class="sc-t rev">Révision</span> <span class="sc-t done">Fait</span></small></p>
    </div>
  </div>
</div>
