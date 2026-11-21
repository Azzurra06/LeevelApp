<?php $title = 'Mon espace';
$next = null; foreach ($sessions as $se) if (strtotime($se['starts_at']) >= time() - 3600 && in_array($se['status'], ['planned', 'postponed'], true)) { $next = $se; break; }
$main = $goals[0] ?? null; foreach ($goals as $g) if ($g['status'] === 'in_progress') { $main = $g; break; }
$progressing = array_filter($goals, fn($g) => in_array($g['status'], ['in_progress', 'rework', 'acquired'], true)); ?>
<div class="page-head"><div><h1 class="hello">Salut <?= e($s['first_name']) ?> 👋</h1><p>Voici ce qui t'attend cette semaine.</p></div>
  <?php if ((int)($s['perm_help_requests'] ?? 1)): ?><button class="btn help-btn" data-toggle="helpbox"><?= icon('circle-help') ?> J'ai besoin d'aide</button><?php endif ?></div>
<div class="card" id="helpbox" hidden><form method="post" action="<?= e(url('s_help')) ?>" class="stack"><?= csrf_field() ?>
  <label>Sur quoi tu bloques ? (facultatif)<textarea name="comment" maxlength="1000" placeholder="Ex. : l'exercice 3 sur les fractions"></textarea></label>
  <button class="btn"><?= icon('message-circle') ?> Prévenir ma tutrice</button></form></div>
<?php require __DIR__ . '/_s_hero.php' ?>
<?php require __DIR__ . '/_onboarding.php' ?>
<?php $awaiting = session_awaiting_feedback((int)$s['id']); if ($awaiting): ?>
<div class="card feedback-card">
  <h2><?= icon('smile') ?> Ta séance du <?= e(fdatetime($awaiting['starts_at'])) ?></h2>
  <p class="muted">3 petites questions pour que la prochaine séance soit encore plus utile.</p>
  <form method="post" action="<?= e(url('s_feedback')) ?>" class="stack"><?= csrf_field() ?><input type="hidden" name="session_id" value="<?= (int)$awaiting['id'] ?>">
    <div><span class="ql">Comment ça s'est passé ?</span>
      <div class="pills"><?php foreach (['good' => 'Bien 🙂', 'ok' => 'Moyen 😐', 'hard' => 'Difficile 😕'] as $k => $l): ?><label class="pill"><input type="radio" name="mood" value="<?= $k ?>"><span><?= $l ?></span></label><?php endforeach ?></div></div>
    <label>Qu'est-ce que tu as compris ou réussi ?<textarea name="understood" maxlength="1000" rows="2"></textarea></label>
    <label>Qu'est-ce que tu dois encore approfondir, ou que tu n'as pas compris ?<textarea name="to_deepen" maxlength="1000" rows="2"></textarea></label>
    <label>De quoi as-tu besoin pour la prochaine séance ?<input name="needs_next" maxlength="1000" placeholder="ex. revoir l'exercice 3, préparer le contrôle de jeudi"></label>
    <button class="btn"><?= icon('send') ?> Envoyer à ma tutrice</button>
  </form>
</div><br>
<?php endif ?>
<div class="grid">
  <div class="card accent"><h2><?= icon('target') ?> Mon objectif principal</h2>
    <?php if ($main): ?><p><strong><?= e($main['title']) ?></strong></p><div class="bar"><i data-w="<?= (int)$main['progress'] ?>"></i></div><p class="muted"><small><?= (int)$main['progress'] ?>% · <?= e(GOAL_STATUS[$main['status']]) ?></small></p>
    <?php else: ?><p class="muted">On le choisit ensemble à la prochaine séance.</p><?php endif ?></div>
  <div class="card"><h2><?= icon('calendar') ?> Prochaine séance</h2>
    <?php if ($next): ?><p class="hello"><strong><?= e(fdatetime($next['starts_at'])) ?></strong></p><p><?= icon($next['mode'] === 'visio' ? 'video' : 'map-pin') ?> <?= e(MODES[$next['mode']]) ?> · <?= fdur((int)$next['duration_min']) ?></p><p class="muted"><?= e(session_label($next)) ?></p>
    <?php else: ?><p class="muted">Pas de séance prévue pour l'instant.</p><?php endif ?></div>
</div><br>
<div class="grid-2 align-start"><?php require __DIR__ . '/_tests.php' ?><?php require __DIR__ . '/_year_goals.php' ?></div><br>
<div class="card"><h2><?= icon('rocket') ?> Mes missions</h2>
  <?php if (!$missions): ?><p class="muted">Pas de mission pour l'instant. Ta tutrice t'en proposera bientôt.</p><?php endif ?>
  <div class="grid"><?php foreach ($missions as $m): ?>
    <div class="mission <?= $m['student_done_at'] ? 'done' : '' ?>"><h3><?= icon($m['student_done_at'] ? 'circle-check' : 'flag') ?> <?= e($m['title']) ?></h3>
      <?php if ($m['description']): ?><p class="muted"><small><?= e($m['description']) ?></small></p><?php endif ?>
      <form method="post" action="<?= e(url('s_goal')) ?>"><?= csrf_field() ?><input type="hidden" name="goal_id" value="<?= (int)$m['id'] ?>">
        <p><small>Comment ça s'est passé ?</small></p>
        <div class="feel pills"><?php foreach (FEELINGS as $k => $l): ?><label class="pill"><input type="radio" name="feeling" value="<?= $k ?>" <?= $m['student_feeling'] === $k ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div>
        <div class="row"><br><?php if (!$m['student_done_at']): ?><label class="check"><input type="checkbox" name="done" value="1"> C'est fait</label><?php else: ?><span class="badge sage">Terminé - ta tutrice validera</span><?php endif ?>
        <button class="btn sm"><?= icon('check') ?> Envoyer</button></div></form></div>
  <?php endforeach ?></div></div><br>
<div class="grid">
  <div class="card"><h2><?= icon('chart-column') ?> Compétences en progression</h2>
    <?php foreach (array_slice($progressing, 0, 5) as $g): ?><div class="axis"><span class="lbl"><?= e($g['title']) ?></span><span class="n"><?= (int)$g['progress'] ?>%</span><div class="bar"><i data-w="<?= (int)$g['progress'] ?>"></i></div></div><?php endforeach ?>
    <?php if (!$progressing): ?><p class="muted">Ça arrive dès les premières séances.</p><?php endif ?></div>
  <div class="card"><h2><?= icon('sparkles') ?> Mes réussites</h2>
    <?php if (!$wins): ?><p class="muted">Tes premières réussites apparaîtront ici.</p><?php endif ?>
    <ul class="list"><?php foreach ($wins as $w): ?><li><?= icon('circle-check') ?><div><?= e($w['title']) ?></div></li><?php endforeach ?></ul></div>
  <div class="card"><h2><?= icon('smile') ?> Ma semaine</h2><form method="post" action="<?= e(url('s_feeling')) ?>" class="stack"><?= csrf_field() ?>
    <div class="pills"><?php foreach (['good' => 'Ça va bien', 'ok' => 'Moyen', 'hard' => "C'est dur"] as $k => $l): ?><label class="pill"><input type="radio" name="mood" value="<?= $k ?>" required><span><?= $l ?></span></label><?php endforeach ?></div>
    <input name="comment" maxlength="500" placeholder="Un mot ? (facultatif)" aria-label="Commentaire"><button class="btn soft sm">Envoyer</button></form></div>
</div><br>
<a class="card tool tools-link" href="<?= e(url('s_tools')) ?>"><span class="ico"><?= icon('lightbulb') ?></span><div><h3>Mes outils</h3><p class="muted"><small>Chrono de travail, méthodes pour retenir à ta façon, fiches méthode…</small></p></div><?= icon('chevron-right', 'go') ?></a><br>
<?php if ($s['shared_message']): ?><div class="card"><h2><?= icon('message-circle') ?> Le mot de ta tutrice</h2><p><?= nl2br(e($s['shared_message'])) ?></p></div><br><?php endif ?>
<div class="card"><h2><?= icon('calendar') ?> Mon planning</h2><?php require __DIR__ . '/_planning.php' ?></div>
