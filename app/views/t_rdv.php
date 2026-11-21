<?php $title = 'Premier rendez-vous'; $sidp = ['sid' => $s['id']];
$parentU = array_values(array_filter($users, fn($x) => $x['role'] === 'parent'));
$studentU = array_values(array_filter($users, fn($x) => $x['role'] === 'student'));
$done = fn($c) => ($subs[$c]['status'] ?? '') === 'completed';
$pass = function (string $code, string $label) use ($s): void { ?>
  <div class="row">
    <form method="post" action="<?= e(url('t_passation')) ?>" data-confirm="Votre session tutrice va être fermée pour laisser l'appareil en mode passation. Vous vous reconnecterez ensuite. Continuer ?"><?= csrf_field() ?>
      <input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="code" value="<?= e($code) ?>"><button class="btn" title="L'appareil est confié à la personne, sans accès à votre espace"><?= icon('play') ?> <?= e($label) ?></button></form>
    <a class="btn ghost" href="<?= e(url('q', ['sid' => $s['id'], 'code' => $code])) ?>" title="Vous saisissez les réponses vous-même, par exemple pendant l'entretien"><?= icon('pencil') ?> Remplir moi-même</a>
  </div>
  <p class="muted"><small>Passation : la personne répond seule sur l'appareil. Remplir moi-même : vous saisissez ses réponses en restant connectée.</small></p>
<?php }; ?>
<div class="page-head"><div><h1><?= icon('clipboard-list') ?> Premier rendez-vous</h1><p><?= e($s['first_name']) ?> · <?= e($s['family_name']) ?></p></div>
  <a class="btn ghost" href="<?= e(url('t_synthesis', $sidp)) ?>"><?= icon('eye') ?> Synthèse famille</a></div>
<ol class="steps-rdv">
  <li class="<?= $parentU && $studentU ? 'ok' : '' ?>"><h3>Vérifier les comptes</h3>
    <ul class="list"><?php foreach ($users as $x): ?><li><?= icon($x['role'] === 'student' ? 'smile' : ($x['role'] === 'parent' ? 'user' : 'shield')) ?><div><?= e($x['display_name']) ?> · <code><?= e($x['username']) ?></code> <span class="badge"><?= e(ROLE_LABELS[$x['role']]) ?></span> <?= $x['is_active'] ? '' : '<span class="badge err">désactivé</span>' ?></div></li><?php endforeach ?></ul>
    <a href="<?= e(url('t_users')) ?>">Gérer les comptes →</a></li>
  <li class="<?= $done('parent') ? 'ok' : '' ?>"><h3>Questionnaire parent</h3>
    <p class="muted">Statut : <?= !$subs['parent'] ? 'non commencé' : ($done('parent') ? 'terminé' : 'en cours (reprise possible)') ?> · Autorisation du représentant légal : <?= $s['guardian_consent_at'] ? '<span class="badge sage">validée le ' . e(fdate($s['guardian_consent_at'])) . '</span>' : '<span class="badge warn">à valider en début de questionnaire</span>' ?></p>
    <?php $pass('parent', $subs['parent'] ? 'Reprendre en passation' : 'Lancer la passation') ?></li>
  <li class="<?= $done('student') ? 'ok' : '' ?>"><h3>Questionnaire élève</h3>
    <p class="muted">Statut : <?= !$subs['student'] ? 'non commencé' : ($done('student') ? 'terminé' : 'en cours') ?></p>
    <?php if ($s['guardian_consent_at']) $pass('student', $subs['student'] ? 'Reprendre en passation' : 'Lancer la passation'); else echo '<p class="alert warn">Disponible après l\'autorisation du parent.</p>' ?></li>
  <li class="<?= $an ? 'ok' : '' ?>"><h3>Résultats, convergences et divergences, observations, priorités</h3>
    <p class="muted">Étapes 4 à 7 : consultez l'analyse ci-dessous, ajoutez vos observations et ajustez les priorités.</p></li>
  <li class="<?= $nSessions ? 'ok' : '' ?>"><h3>Générer le plan initial</h3>
    <form method="post" action="<?= e(url('t_plan')) ?>" class="stack"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="t_rdv">
      <div class="form-grid"><label>Début (lundi de la semaine 1)<input type="date" name="start" value="<?= e((new DateTime('next monday'))->format('Y-m-d')) ?>" required></label></div>
      <label class="check"><input type="checkbox" name="with_goals" value="1" checked> Créer aussi les objectifs suggérés selon les priorités</label>
      <?php if ($nSessions): ?><label class="check"><input type="checkbox" name="replace" value="1"> Remplacer les séances « prévues » du plan existant</label><?php endif ?>
      <button class="btn"><?= icon('calendar') ?> Générer le planning du premier mois</button>
      <p class="muted"><small>S1 : 6 h présentiel · S2 : 6 h présentiel · S3 : 4 h présentiel + 2 h visio · S4 : 2 h présentiel + 4 h visio. Tout reste modifiable.</small></p>
    </form></li>
  <li><h3>Présenter la synthèse à la famille</h3><p class="muted">Vue épurée sans données privées, à afficher à l'écran ou imprimer.</p>
    <a class="btn" href="<?= e(url('t_synthesis', $sidp)) ?>"><?= icon('eye') ?> Ouvrir la synthèse famille</a></li>
</ol>
<h2><?= icon('chart-column') ?> Analyse</h2>
<?php require __DIR__ . '/_analysis.php' ?>
