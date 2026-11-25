<?php $title = 'Mon espace';
$next = null; foreach ($sessions as $se) if (strtotime($se['starts_at']) >= time() - 3600 && $se['status'] === 'planned') { $next = $se; break; }
$main = $goals[0] ?? null; foreach ($goals as $g) if ($g['status'] === 'in_progress') { $main = $g; break; }
$progressing = array_filter($goals, fn($g) => in_array($g['status'], ['in_progress', 'rework', 'acquired'], true));
$tests = tests_upcoming((int)$s['id'], 4);
$days = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
$months = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
$lastMood = one('SELECT value FROM progress_entries WHERE student_id=? AND entry_type="feeling" AND created_at > NOW() - INTERVAL 7 DAY ORDER BY id DESC LIMIT 1', [$s['id']])['value'] ?? null;
$moods = ['good' => ['😄', 'Ça va bien'], 'ok' => ['😐', 'Moyen'], 'hard' => ['😣', "C'est dur"]];
$gsplit = goals_split($goals); $wk = $gsplit['missions'];
$wkDone = count(array_filter($wk, fn($g) => $g['status'] === 'acquired')); $wkPct = $wk ? (int)round($wkDone * 100 / count($wk)) : 0;
$catEmo = ['methodo' => '🗂️', 'autonomie' => '🚀', 'motivation' => '💪', 'evaluations' => '🎯', 'comprehension' => '💡']; ?>
<div class="page-head"><div><h1 class="hello">Salut <?= e($s['first_name']) ?> 👋</h1><p>Voici ce qui t'attend cette semaine.</p></div></div>
<?php if (!onboarding_available()) require __DIR__ . '/_onboarding.php' ?>

<?php /* 1. Mon parcours */ ?>
<?php require __DIR__ . '/_s_hero.php' ?>

<?php /* 2. Séances */ ?>
<h2 class="dash-sec">Mes séances</h2>
<?php $awaiting = session_awaiting_feedback((int)$s['id']); ?>
<div class="s-row3">
  <a class="card tile s-last tile-link" href="<?= e(url('s_planning')) ?>#recap" aria-label="Voir ma dernière séance">
    <h2><?= icon('message-circle') ?> Ma dernière séance <?= icon('chevron-right', 'tile-chev') ?></h2>
    <?php $ls = $awaiting ?: ($recap ?? null); if ($ls): $ts = strtotime($ls['starts_at']); ?>
      <div class="tile-row"><div class="cal-page"><small><?= e($days[(int)date('w', $ts)]) ?></small><b><?= date('j', $ts) ?></b><small><?= e($months[(int)date('n', $ts)]) ?></small></div>
        <div><strong><?= date('H\hi', $ts) ?> · <?= fdur((int)$ls['duration_min']) ?></strong>
        <?php if ($awaiting): ?><small>Ton avis n'est pas encore donné</small><?php else: $rep = $recap['report']; ?><small><?= $rep && ($rep['worked_on'] || $rep['next_focus']) ? 'Le bilan de ta tutrice est prêt' : 'Retour envoyé · bilan bientôt' ?></small><?php endif ?></div></div>
      <span class="s-last-cta"><?= $awaiting ? 'Donner mon retour (1 min)' : 'Lire le bilan' ?> <?= icon('chevron-right') ?></span>
    <?php else: ?><div class="tile-row"><span class="tile-emoji">💬</span><div><strong>Pas encore de séance</strong><small>Le bilan apparaîtra ici après ta première séance.</small></div></div><?php endif ?>
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

<?php /* 4. Missions de la semaine */ ?>
<h2 class="dash-sec">Mes missions de la semaine</h2>
<div class="card wk-card" id="missions">
  <div class="wk-head">
    <div class="wk-intro"><strong><?= !$wk ? 'Choisis ta première mission !' : ($wkDone === count($wk) ? 'Tout est fait, bravo ! 🎉' : $wkDone . ' sur ' . count($wk) . ' réussie' . ($wkDone > 1 ? 's' : '')) ?></strong><small><?= e(week_label()) ?></small></div>
    <a class="btn ghost sm" href="<?= e(url('s_goals')) ?>#banque"><?= icon('plus') ?> Choisir une mission</a>
  </div>
  <?php if ($wk): ?><div class="wk-bar"><i data-w="<?= $wkPct ?>"></i></div>
  <ul class="wk-list"><?php foreach ($wk as $g): $isDone = $g['status'] === 'acquired'; ?>
    <li class="wk-item<?= $isDone ? ' done' : '' ?>">
      <form method="post" action="<?= e(url('s_goal')) ?>" class="wk-check"><?= csrf_field() ?><input type="hidden" name="goal_id" value="<?= (int)$g['id'] ?>"><input type="hidden" name="back" value="s"><input type="hidden" name="mstatus" value="<?= $isDone ? 'todo' : 'acquired' ?>">
        <button class="wk-box" aria-label="<?= $isDone ? 'Décocher' : 'Mission réalisée' ?>" title="<?= $isDone ? 'Décocher' : 'Je l\'ai fait !' ?>"><?= icon('check') ?></button></form>
      <div class="wk-main"><strong><?= $catEmo[$g['category']] ?? '⭐' ?> <?= e($g['title']) ?></strong><?php if ($g['description']): ?><small><?= e($g['description']) ?></small><?php endif ?></div>
      <form method="post" action="<?= e(url('s_goal')) ?>" class="wk-more"><?= csrf_field() ?><input type="hidden" name="goal_id" value="<?= (int)$g['id'] ?>"><input type="hidden" name="back" value="s">
        <span class="wk-dl">Difficulté</span><span class="wk-df"><?php foreach (DIFFICULTY as $k => $l): ?><button name="difficulty" value="<?= $k ?>" class="<?= ($g['difficulty'] ?? '') === $k ? 'on df-' . $k : '' ?>"><?= e($l) ?></button><?php endforeach ?></span>
      </form>
    </li>
  <?php endforeach ?></ul><?php endif ?>
</div>

<?php /* 3. Contrôles et agenda */ ?>
<h2 class="dash-sec">Mon agenda</h2>
<div class="grid-2 two-cols dash-row">
  <?php $tone = 'tone-orange'; require __DIR__ . '/_tests.php'; $tone = ''; ?>
  <?php $agStudy = $study ?? []; $agSessions = $sessions; $agTests = tests_upcoming((int)$s['id'], 20); $agUrl = url('s_planning'); $agKid = true; require __DIR__ . '/_agenda_week.php'; ?>
</div>

<?php /* 5. Progression, objectifs de l'année, outils */ ?>
<h2 class="dash-sec">Ma progression</h2>
<div class="grid-2 two-cols dash-row eq">
  <div class="card tone-blue">
    <h2><?= icon('chart-column') ?> Mes progrès</h2>
    <?php foreach (array_slice($progressing, 0, 6) as $g): ?><div class="axis"><span class="lbl"><?= e($g['title']) ?></span><span class="n"><?= $g['status'] === 'acquired' ? 100 : (int)$g['progress'] ?>%</span><div class="bar"><i data-w="<?= $g['status'] === 'acquired' ? 100 : (int)$g['progress'] ?>"></i></div></div><?php endforeach ?>
    <?php if (!$progressing): ?><div class="empty-ill sm"><span>📈</span><p class="muted">Tes progrès apparaîtront ici dès les premières séances.</p></div><?php endif ?>
  </div>
  <div class="col-stack">
    <?php $tone = 'tone-green'; require __DIR__ . '/_year_goals.php'; $tone = ''; ?>
    <a class="card tone-teal link-card" href="<?= e(url('s_tools')) ?>"><span class="lc-emoji">🧰</span><div><h2>Mes outils</h2><small>Chrono de travail, méthodes pour retenir à ta façon, fiches méthode…</small></div><?= icon('chevron-right', 'go') ?></a>
  </div>
</div>
<?php if ($s['shared_message']): ?><br><div class="card tone-violet"><h2><?= icon('message-circle') ?> Le mot de ta tutrice</h2><p><?= nl2br(e($s['shared_message'])) ?></p></div><?php endif ?>
