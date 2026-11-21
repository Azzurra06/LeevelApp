<?php $title = 'Mes outils';
$mem = array_values(array_filter((array)($memory ?? []), fn($k) => isset(MEMORY_TYPES[$k]) && $k !== 'not_sure'));
$memTips = [
    'visual' => ['icon' => 'eye', 'title' => 'Tu retiens en voyant', 'tips' => [
        ['Carte mentale', 'Le thème au centre, les idées en branches, une couleur par partie.'],
        ['Fiches en couleurs', 'Surligne toujours de la même façon : définitions en bleu, exemples en vert…'],
        ['Schémas et dessins', "Transforme une leçon en schéma : si tu peux le dessiner, tu l'as compris."]]],
    'auditory' => ['icon' => 'message-circle', 'title' => 'Tu retiens en entendant', 'tips' => [
        ['Explique à voix haute', "Fais comme si tu étais le prof : explique la leçon à quelqu'un (ou à ton miroir)."],
        ["Enregistre-toi", 'Lis ta fiche en t\'enregistrant, puis réécoute-la dans le bus ou avant de dormir.'],
        ['Questions-réponses', 'Demande à quelqu\'un de te poser les questions et réponds à l\'oral.']]],
    'kinesthetic' => ['icon' => 'pencil', 'title' => 'Tu retiens en faisant', 'tips' => [
        ['Réécris pour retenir', 'Recopie l\'essentiel de mémoire, puis vérifie et corrige.'],
        ['Flashcards à manipuler', 'Question au recto, réponse au verso : fais deux tas « je sais » / « à revoir ».'],
        ['Bouge en révisant', 'Récite en marchant, fais des pauses actives entre deux exercices.']]],
];
$shown = $mem ?: array_keys($memTips);
$methods = [
    ['icon' => 'list-checks', 'title' => 'Ma checklist devoirs', 'steps' => ['Je lis toute la consigne', 'Je souligne les mots importants', 'Je fais', 'Je vérifie ma réponse', 'Je coche dans mon agenda']],
    ['icon' => 'flag', 'title' => 'Préparer un contrôle (J-3)', 'steps' => ['J-3 : je relis et je fais ma fiche', 'J-2 : je me teste sans regarder', 'J-1 : je revois seulement ce qui bloque', 'Le jour J : je respire, je lis tout le sujet avant de commencer']],
    ['icon' => 'brain', 'title' => 'Se tester plutôt que relire', 'steps' => ['Je cache ma leçon', "J'écris tout ce dont je me souviens", 'Je compare avec la leçon', 'Je recommence le lendemain sur ce qui manquait']],
];
$links = [
    ['Lumni', 'https://www.lumni.fr/', 'Vidéos et jeux gratuits pour toutes les matières, du service public.'],
    ['Khan Academy', 'https://fr.khanacademy.org/', 'Cours et exercices de maths et de sciences, à ton rythme.'],
]; ?>
<div class="page-head"><div><h1><?= icon('lightbulb') ?> Mes outils</h1><p>Des méthodes et des astuces pour travailler plus facilement, à ta façon.</p></div></div>

<div class="grid-2 align-start">
  <div class="card tool-timer" data-timer>
    <h2><?= icon('timer') ?> Mon chrono de travail</h2>
    <p class="muted"><small>Travaille à fond pendant un temps court, puis fais une vraie pause. C'est plus efficace que des heures d'affilée.</small></p>
    <div class="seg" role="group" aria-label="Durée">
      <button type="button" class="on" data-min="15">15 min</button><button type="button" data-min="20">20 min</button><button type="button" data-min="25">25 min</button>
    </div>
    <div class="timer-ring" data-ring style="--p:0"><div><strong data-time>15:00</strong><small data-phase>Travail</small></div></div>
    <div class="row timer-btns">
      <button type="button" class="btn" data-start><?= icon('play') ?> <span>Démarrer</span></button>
      <button type="button" class="btn ghost" data-reset><?= icon('refresh-cw') ?> Recommencer</button>
    </div>
    <p class="muted timer-count"><small>Sessions terminées aujourd'hui : <strong data-done>0</strong></small></p>
  </div>

  <div class="card">
    <h2><?= icon('sparkles') ?> Choisis pour toi</h2>
    <?php if (!$reco['tools']): ?><p class="muted">Ta tutrice te proposera des outils après votre premier rendez-vous.</p><?php endif ?>
    <div class="col-stack"><?php foreach (array_slice($reco['tools'], 0, 4) as $t): ?>
      <div class="tool"><span class="ico"><?= icon($t['icon']) ?></span><div><h3><?= e($t['title']) ?></h3><p class="muted"><small><?= e($t['desc']) ?></small></p></div></div>
    <?php endforeach ?></div>
  </div>
</div><br>

<h2><?= icon('brain') ?> Mes méthodes pour retenir</h2>
<?php if (!$mem): ?><p class="muted">Tu ne sais pas encore comment tu retiens le mieux ? Essaie les trois façons, et indique celle qui marche dans <a href="<?= e(url('account')) ?>">Mon compte</a>.</p><?php endif ?>
<div class="grid">
  <?php foreach ($shown as $k): $m = $memTips[$k]; ?>
  <div class="card mem-card mem-<?= e($k) ?>"><h3><span class="ico"><?= icon($m['icon']) ?></span> <?= e($m['title']) ?></h3>
    <ul class="list"><?php foreach ($m['tips'] as [$tt, $td]): ?><li><?= icon('check') ?><div><strong><?= e($tt) ?></strong><br><small class="muted"><?= e($td) ?></small></div></li><?php endforeach ?></ul>
  </div>
  <?php endforeach ?>
</div><br>

<h2><?= icon('list-checks') ?> Mes fiches méthode</h2>
<div class="grid">
  <?php foreach ($methods as $i => $m): ?>
  <div class="card"><h3><?= icon($m['icon']) ?> <?= e($m['title']) ?></h3>
    <div class="checklist" data-checklist="m<?= $i ?>"><?php foreach ($m['steps'] as $j => $st): ?><label class="check"><input type="checkbox" value="<?= $j ?>"> <?= e($st) ?></label><?php endforeach ?></div>
    <button type="button" class="more-btn" data-check-reset>Tout décocher</button>
  </div>
  <?php endforeach ?>
</div><br>

<div class="card"><h2><?= icon('book-open') ?> Sites utiles</h2>
  <ul class="list"><?php foreach ($links as [$n, $href, $d]): ?><li class="li-link"><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= icon('book-open') ?><div><strong><?= e($n) ?></strong><br><small class="muted"><?= e($d) ?></small></div><?= icon('chevron-right', 'go') ?></a></li><?php endforeach ?></ul>
</div>
