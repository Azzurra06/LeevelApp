<?php // Tutrice : contenu de l'onglet « Outils » de l'élève. $s, $reco
$kit = student_tools($s, $reco ?? []);
$sections = [
    'tools' => ['icon' => 'sparkles', 'title' => 'Outils adaptés', 'help' => 'Un outil par ligne : « Titre : description ».', 'ph' => "Agenda papier : noter chaque devoir dès qu'il est donné\nMinuteur : travailler 20 min puis 5 min de pause"],
    'methods' => ['icon' => 'list-checks', 'title' => 'Fiches méthode', 'help' => "Une fiche par bloc, séparés par une ligne vide : le titre sur la 1re ligne, puis une étape par ligne (l'élève peut les cocher).", 'ph' => "Préparer un contrôle\n- Je relis ma leçon\n- Je fais une fiche\n- Je me teste\n\nFaire un exercice de maths\n- Je lis l'énoncé deux fois\n- …"],
    'links' => ['icon' => 'globe', 'title' => 'Sites utiles', 'help' => 'Un site par ligne : « Nom | adresse | description ».', 'ph' => "Lumni | https://www.lumni.fr | Vidéos de cours gratuites"],
]; ?>
<div id="outils"></div>
<p class="muted tools-t-intro"><small>Ce que <?= e($s['first_name']) ?> voit dans son onglet « Outils ». Le chrono de travail et les méthodes pour retenir (selon sa façon de mémoriser) restent affichés automatiquement.</small></p>
<div class="grid-2 two-cols">
<?php foreach ($sections as $k => $sec): $items = $kit[$k]; ?>
  <div class="card editable">
    <div class="ed-head"><h2><?= icon($sec['icon']) ?> <?= e($sec['title']) ?></h2><button type="button" class="btn ghost sm" data-edit><?= icon('pencil') ?> Modifier</button></div>
    <div class="view">
      <?php if (!$items): ?><p class="muted">Rien d'affiché pour l'instant.</p><?php endif ?>
      <?php if ($k === 'tools'): ?><ul class="list"><?php foreach ($items as $t): ?><li><?= icon($t['icon'] ?? 'lightbulb') ?><div><strong><?= e($t['title']) ?></strong><?php if ($t['desc']): ?><br><small class="muted"><?= e($t['desc']) ?></small><?php endif ?></div></li><?php endforeach ?></ul>
      <?php elseif ($k === 'methods'): foreach ($items as $m): ?><div class="tt-method"><strong><?= e($m['title']) ?></strong><ul><?php foreach ($m['steps'] as $st): ?><li><?= e($st) ?></li><?php endforeach ?></ul></div><?php endforeach ?>
      <?php else: ?><ul class="list"><?php foreach ($items as $l): ?><li><?= icon('globe') ?><div><a href="<?= e($l['url']) ?>" target="_blank" rel="noopener noreferrer"><strong><?= e($l['name']) ?></strong></a><?php if ($l['desc']): ?><br><small class="muted"><?= e($l['desc']) ?></small><?php endif ?></div></li><?php endforeach ?></ul><?php endif ?>
      <p class="muted"><small><?= !empty($kit['custom'][$k]) ? 'Personnalisé pour ' . e($s['first_name']) . '.' : ($k === 'tools' ? "Proposés automatiquement d'après l'analyse pédagogique." : 'Contenu par défaut.') ?></small></p>
    </div>
    <form method="post" action="<?= e(url('t_tools')) ?>" class="stack edit-form"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="kind" value="<?= $k ?>">
      <label><?= e($sec['help']) ?><textarea name="content" rows="<?= $k === 'methods' ? 12 : 6 ?>" placeholder="<?= e($sec['ph']) ?>"><?= e(tools_to_text($k, $items)) ?></textarea></label>
      <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button>
        <?php if (!empty($kit['custom'][$k])): ?><button class="btn ghost sm" name="reset" value="1" formnovalidate data-confirm="Revenir au contenu par défaut ?"><?= icon('refresh-cw') ?> Contenu par défaut</button><?php endif ?></div>
    </form>
  </div>
<?php endforeach ?>
</div>
