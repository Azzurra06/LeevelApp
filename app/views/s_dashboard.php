<?php $title = 'Mon espace';
$next = null; foreach ($sessions as $se) if (strtotime($se['starts_at']) >= time() - 3600 && $se['status'] === 'planned') { $next = $se; break; }
$main = $goals[0] ?? null; foreach ($goals as $g) if ($g['status'] === 'in_progress') { $main = $g; break; }
$progressing = array_filter($goals, fn($g) => in_array($g['status'], ['in_progress', 'rework', 'acquired'], true));
$tests = tests_upcoming((int)$s['id'], 4);
$days = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
$months = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
$lastMood = one('SELECT value FROM progress_entries WHERE student_id=? AND entry_type="feeling" AND created_at > NOW() - INTERVAL 7 DAY ORDER BY id DESC LIMIT 1', [$s['id']])['value'] ?? null;
$moods = ['good' => ['😄', 'Ça va bien'], 'ok' => ['😐', 'Moyen'], 'hard' => ['😣', "C'est dur"]]; ?>
<div class="page-head"><div><h1 class="hello">Salut <?= e($s['first_name']) ?> 👋</h1><p>Voici ce qui t'attend cette semaine.</p></div></div>
<?php require __DIR__ . '/_s_hero.php' ?>
<?php if (!onboarding_available()) require __DIR__ . '/_onboarding.php' ?>
<?php $awaiting = session_awaiting_feedback((int)$s['id']); if ($awaiting): ?>
<a class="card tone-pink link-card fb-cta" href="<?= e(url('s_subjects')) ?>#recap"><span class="lc-emoji">💬</span><div><h2>Ta séance du <?= e(fdatetime($awaiting['starts_at'])) ?></h2><small>Dis à ta tutrice comment ça s'est passé, en 1 minute.</small></div><?= icon('chevron-right', 'go') ?></a>
<?php endif ?>

<div class="s-tiles">
  <a class="card tile tone-violet tile-link" href="<?= e(url('s_subjects')) ?>#recap" aria-label="Voir le bilan de ma dernière séance">
    <h2><?= icon('file-text') ?> Ma dernière séance <?= icon('chevron-right', 'tile-chev') ?></h2>
    <?php if (!empty($recap)): $ts = strtotime($recap['starts_at']); $rep = $recap['report']; $fb = $recap['feedback']; ?>
      <div class="tile-row"><div class="cal-page"><small><?= e($days[(int)date('w', $ts)]) ?></small><b><?= date('j', $ts) ?></b><small><?= e($months[(int)date('n', $ts)]) ?></small></div>
        <div><strong><?= e(session_label($recap)) ?></strong>
          <small><?= $rep && ($rep['worked_on'] || $rep['next_focus']) ? icon('check') . ' Le bilan de ta tutrice est là' : 'Bilan bientôt disponible' ?></small>
          <small><?= $fb ? icon('check') . ' Retour envoyé' : icon('message-circle') . ' Donne ton retour' ?></small></div></div>
    <?php else: ?><div class="tile-row"><span class="tile-emoji">📅</span><div><strong>Pas encore de séance</strong><small>Le bilan apparaîtra ici après ta première séance.</small></div></div><?php endif ?>
  </a>
  <a class="card tile tone-blue tile-link" href="<?= e(url('s_planning')) ?>" aria-label="Voir le détail de mes séances">
    <h2><?= icon('calendar') ?> Prochaine séance <?= icon('chevron-right', 'tile-chev') ?></h2>
    <?php if ($next): $ts = strtotime($next['starts_at']); ?>
      <div class="tile-row"><div class="cal-page"><small><?= e($days[(int)date('w', $ts)]) ?></small><b><?= date('j', $ts) ?></b><small><?= e($months[(int)date('n', $ts)]) ?></small></div>
        <div><strong><?= date('H\hi', $ts) ?> · <?= fdur((int)$next['duration_min']) ?></strong><small><?= icon($next['mode'] === 'visio' ? 'video' : 'map-pin') ?> <?= e(MODES[$next['mode']]) ?></small></div></div>
    <?php else: ?><div class="tile-row"><span class="tile-emoji">📅</span><div><strong>Pas encore prévue</strong><small>Ta tutrice la planifie bientôt.</small></div></div><?php endif ?>
  </a>
  <div class="card tile tone-pink">
    <h2><?= icon('smile') ?> Mon humeur de la semaine</h2>
    <form method="post" action="<?= e(url('s_feeling')) ?>" class="mood-row"><?= csrf_field() ?>
      <?php foreach ($moods as $k => [$emo, $lbl]): ?><button name="mood" value="<?= $k ?>" class="mood <?= $lastMood === $k ? 'on' : '' ?>" title="<?= e($lbl) ?>"><span><?= $emo ?></span><small><?= e($lbl) ?></small></button><?php endforeach ?>
    </form>
  </div>
</div>

<div class="card tone-violet missions-card">
  <div class="row between"><h2><?= icon('rocket') ?> Mes missions</h2><a class="card-link" href="<?= e(url('s_goals')) ?>">Mes objectifs →</a></div>
  <?php if (!$missions): ?>
    <div class="empty-ill"><span>🚀</span><p>Pas de mission pour l'instant.<br><small class="muted">Ta tutrice t'en proposera bientôt : de petits défis à relever entre deux séances.</small></p></div>
  <?php endif ?>
  <div class="mission-list"><?php foreach ($missions as $i => $m): $done = (bool)$m['student_done_at']; ?>
    <div class="mission-item <?= $done ? 'done' : '' ?>">
      <span class="mission-num"><?= $done ? icon('check') : $i + 1 ?></span>
      <div class="mission-body">
        <strong class="mission-title"><?= e($m['title']) ?></strong>
        <?php if ($m['description']): ?><p class="mission-desc"><?= e($m['description']) ?></p><?php endif ?>
        <?php if ($done): ?><span class="badge sage">Terminée · ta tutrice validera</span><?php endif ?>
        <details class="mission-act">
          <summary class="btn sm <?= $done ? 'ghost' : 'btn-outline' ?>"><?= $done ? 'Modifier mon ressenti' : 'J\'ai fini cette mission' ?></summary>
          <form method="post" action="<?= e(url('s_goal')) ?>" class="mission-form"><?= csrf_field() ?><input type="hidden" name="goal_id" value="<?= (int)$m['id'] ?>">
            <span class="mission-q">Comment ça s'est passé ?</span>
            <div class="feel pills"><?php foreach (FEELINGS as $k => $l): ?><label class="pill"><input type="radio" name="feeling" value="<?= $k ?>" <?= $m['student_feeling'] === $k ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div>
            <div class="mission-foot"><?php if (!$done): ?><label class="check"><input type="checkbox" name="done" value="1" checked> C'est terminé</label><?php endif ?>
              <button class="btn sm"><?= icon('send') ?> Envoyer</button></div>
          </form>
        </details>
      </div>
    </div>
  <?php endforeach ?></div>
</div>

<div class="grid-2">
  <?php $tone = 'tone-orange'; require __DIR__ . '/_tests.php'; ?>
  <?php $tone = 'tone-green'; require __DIR__ . '/_year_goals.php'; $tone = ''; ?>
  <div class="card tone-blue">
    <h2><?= icon('chart-column') ?> Compétences en progression</h2>
    <?php foreach (array_slice($progressing, 0, 5) as $g): ?><div class="axis"><span class="lbl"><?= e($g['title']) ?></span><span class="n"><?= (int)$g['progress'] ?>%</span><div class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></div></div><?php endforeach ?>
    <?php if (!$progressing): ?><div class="empty-ill sm"><span>📈</span><p class="muted">Tes compétences apparaîtront ici dès les premières séances.</p></div><?php endif ?>
  </div>
</div><br>

<a class="card tone-teal link-card" href="<?= e(url('s_tools')) ?>"><span class="lc-emoji">🧰</span><div><h2>Mes outils</h2><small>Chrono de travail, méthodes pour retenir à ta façon, fiches méthode…</small></div><?= icon('chevron-right', 'go') ?></a><br>
<?php if ($s['shared_message']): ?><div class="card tone-violet"><h2><?= icon('message-circle') ?> Le mot de ta tutrice</h2><p><?= nl2br(e($s['shared_message'])) ?></p></div><br><?php endif ?>

