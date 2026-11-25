<?php // Carte « Agenda de la semaine » (élève et tutrice) : jour par jour, lignes séparées.
// $agStudy (devoirs/révisions), $agSessions (séances), $agTests (contrôles), $agUrl (lien vers l'agenda), $agKid (tutoiement)
$mon = week_monday(); $sun = week_sunday(); $today = date('Y-m-d');
$late = array_values(array_filter($agStudy, fn($t) => !$t['done_at'] && $t['due_date'] < $today));
$byDay = [];
foreach ($agSessions as $se) { $d = substr($se['starts_at'], 0, 10); if ($d >= $today && $d <= $sun && in_array($se['status'], ['planned', 'done'], true)) $byDay[$d][] = ['sess', date('H\hi', strtotime($se['starts_at'])), 'Séance', session_label($se), false]; }
foreach ($agTests as $te) if ($te['test_date'] >= $today && $te['test_date'] <= $sun) $byDay[$te['test_date']][] = ['test', '', 'Contrôle', $te['subject'] ?: ($te['title'] ?? 'Contrôle'), false];
foreach ($agStudy as $t) if ($t['due_date'] >= $today && $t['due_date'] <= $sun) $byDay[$t['due_date']][] = [$t['kind'] === 'revision' ? 'rev' : 'dev', '', STUDY_KINDS[$t['kind']], ($t['subject'] ? $t['subject'] . ' · ' : '') . $t['title'], (bool)$t['done_at']];
ksort($byDay);
$dn = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']; ?>
<div class="card tone-blue agw-card">
  <h2><?= icon('list-checks') ?> <?= !empty($agKid) ? 'Mon agenda de la semaine' : 'Agenda de la semaine' ?></h2>
  <p class="muted dash-sub"><?= e(week_label()) ?></p>
  <?php if ($late): ?><a class="agw-late" href="<?= e($agUrl . '&av=todo') ?>#devoirs"><?= icon('triangle-alert') ?> <?= count($late) ?> devoir<?= count($late) > 1 ? 's' : '' ?> en retard</a><?php endif ?>
  <?php if (!$byDay): ?><p class="muted agw-empty">Rien de prévu d'ici dimanche.</p><?php endif ?>
  <div class="agw">
    <?php foreach ($byDay as $d => $items): usort($items, fn($a, $b) => strcmp($a[1] ?: 'zz', $b[1] ?: 'zz')); ?>
      <div class="agw-day<?= $d === $today ? ' today' : '' ?>">
        <div class="agw-dh"><?= $d === $today ? "Aujourd'hui" : $dn[(int)date('w', strtotime($d))] ?> <span><?= date('d/m', strtotime($d)) ?></span></div>
        <ul><?php foreach ($items as [$c, $h, $k, $txt, $isDone]): ?>
          <li class="<?= $isDone ? 'done' : '' ?>"><span class="da-k <?= $c ?>"><?= e($k) ?></span><span class="agw-t"><?= $h ? '<b>' . e($h) . '</b> ' : '' ?><?= e($txt) ?></span><?= $isDone ? icon('check', 'agw-ok') : '' ?></li>
        <?php endforeach ?></ul>
      </div>
    <?php endforeach ?>
  </div>
  <a class="card-link" href="<?= e($agUrl) ?>"><?= !empty($agKid) ? 'Ouvrir mon agenda' : "Ouvrir l'agenda" ?> →</a>
</div>
