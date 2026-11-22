<?php // En-tête de la fiche élève et ses onglets. $s, $ficheTab (clé d'onglet, ou 'rdv'), $headExtra (HTML facultatif)
$sidp = ['sid' => $s['id']];
$ficheTabs = ['profil' => 'Profil', 'objectifs' => 'Objectifs', 'progression' => 'Progression', 'bilans' => 'Bilans mensuels', 'analyse' => 'Analyse pédagogique', 'parent' => 'Questionnaires'];
$tabOn = fn($k) => $ficheTab === $k || ($k === 'parent' && $ficheTab === 'eleve'); ?>
<div class="page-head"><div><h1><?= e($s['first_name']) ?> <?php if ($s['is_demo']): ?><span class="badge demo">DÉMO</span><?php endif ?></h1><p><?= e($s['grade']) ?><?= $s['school'] ? ' · ' . e($s['school']) : '' ?> · <?= e($s['family_name'] ?? '') ?></p></div>
  <div class="row fiche-actions"><?= $headExtra ?? '' ?><a class="btn ghost sm" href="<?= e(url('t_export', $sidp)) ?>"><?= icon('download') ?> Exporter</a></div></div>
<nav class="tabs" aria-label="Fiche élève"><?php foreach ($ficheTabs as $k => $l): ?><a href="<?= e(url('t_student', $sidp + ['tab' => $k])) ?>" class="<?= $tabOn($k) ? 'on' : '' ?>"<?= $tabOn($k) ? ' aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach ?>
  <a href="<?= e(url('t_rdv', $sidp)) ?>" class="tab-rdv<?= $ficheTab === 'rdv' ? ' on' : '' ?>"<?= $ficheTab === 'rdv' ? ' aria-current="page"' : '' ?>><?= icon('clipboard-list') ?> 1er RDV</a></nav>
