<?php // Bandeau motivant de l'élève : progression, compteurs, badges. $s, $goals, $sessions, $next, $studentSub
$sid = (int)$s['id'];
$avgProg = $goals ? (int)round(array_sum(array_map(fn($g) => $g['status'] === 'acquired' ? 100 : (int)$g['progress'], $goals)) / count($goals)) : 0;
$acquired = count(array_filter($goals, fn($g) => $g['status'] === 'acquired'));
$missionsDone = (int)(one('SELECT COUNT(*) n FROM goals WHERE student_id=? AND is_mission=1 AND (student_done_at IS NOT NULL OR status="acquired")', [$sid])['n'] ?? 0);
$sessDone = count(array_filter($sessions, fn($x) => $x['status'] === 'done' || ($x['status'] === 'planned' && strtotime($x['starts_at']) + 60 * (int)$x['duration_min'] < time())));
$selfEvals = (int)(one('SELECT COUNT(*) n FROM progress_entries WHERE student_id=? AND entry_type IN ("self_eval","feeling","task_done")', [$sid])['n'] ?? 0);
$daysNext = $next ? max(0, (int)floor((strtotime(date('Y-m-d', strtotime($next['starts_at']))) - strtotime('today')) / 86400)) : null;
$qDone = $studentSub && $studentSub['status'] === 'completed';
$cheer = match (true) {
    $avgProg >= 75 => 'Tu es sur une super lancée, continue comme ça ! 🚀',
    $avgProg >= 40 => 'Tu avances bien, chaque séance compte. 💪',
    $avgProg > 0 => 'Les premiers pas sont faits, le plus dur est derrière toi. 🌱',
    default => "C'est le début de l'aventure : on avance ensemble, à ton rythme. ✨",
};
$badges = [
    ['icon' => 'clipboard-list', 'title' => 'Je me présente', 'desc' => 'Questionnaire terminé', 'on' => $qDone],
    ['icon' => 'calendar', 'title' => 'Première séance', 'desc' => '1 séance réalisée', 'on' => $sessDone >= 1],
    ['icon' => 'rocket', 'title' => 'En mission', 'desc' => '1re mission terminée', 'on' => $missionsDone >= 1],
    ['icon' => 'smile', 'title' => "Je m'exprime", 'desc' => '3 ressentis partagés', 'on' => $selfEvals >= 3],
    ['icon' => 'flag', 'title' => 'Régulier', 'desc' => '5 séances réalisées', 'on' => $sessDone >= 5],
    ['icon' => 'sparkles', 'title' => 'Objectif atteint', 'desc' => '1 objectif acquis', 'on' => $acquired >= 1],
];
$unlocked = count(array_filter($badges, fn($b) => $b['on'])); ?>
<?php $nextTxt = $daysNext === null ? '—' : ($daysNext === 0 ? "Aujourd'hui" : ($daysNext === 1 ? 'Demain' : 'J-' . $daysNext)); ?>
<div class="card s-hero">
  <span class="s-eyebrow">Mon parcours</span>
  <h2 class="s-hero-title">Chaque progrès compte, <?= e($s['first_name']) ?>.</h2>
  <p class="s-hero-sub">Continue à avancer à ton rythme.</p>
  <div class="s-stats">
    <div class="s-stat"><span class="ico"><?= icon('calendar') ?></span><strong><?= $sessDone ?></strong><small><?= $sessDone > 1 ? 'Séances réalisées' : 'Séance réalisée' ?></small></div>
    <div class="s-stat"><span class="ico"><?= icon('rocket') ?></span><strong><?= $missionsDone ?></strong><small><?= $missionsDone > 1 || $missionsDone === 0 ? 'Missions réussies' : 'Mission réussie' ?></small></div>
    <div class="s-stat"><span class="ico"><?= icon('target') ?></span><strong><?= $acquired ?></strong><small><?= $acquired > 1 || $acquired === 0 ? 'Objectifs atteints' : 'Objectif atteint' ?></small></div>
    <div class="s-stat"><span class="ico"><?= icon('clock') ?></span><strong><?= e($nextTxt) ?></strong><small>Prochaine séance</small></div>
  </div>
</div>
<div class="card tone-violet s-badges">
  <div class="row between"><h2><?= icon('sparkles') ?> Mes badges</h2><span class="badge sage"><?= $unlocked ?> / <?= count($badges) ?></span></div>
  <div class="badge-grid">
    <?php foreach ($badges as $b): ?>
      <div class="bdg <?= $b['on'] ? 'on' : '' ?>" title="<?= e($b['desc']) ?>"><span class="bdg-ico"><?= icon($b['on'] ? $b['icon'] : 'lock') ?></span><strong><?= e($b['title']) ?></strong><small><?= e($b['desc']) ?></small></div>
    <?php endforeach ?>
  </div>
</div>
