<?php // Visionneuse de pièce jointe (dans l'app) : retour, enregistrer / partager, aperçu. $f, $back
$title = $f['original'];
$raw = url('msg_file', ['id' => $f['id']]); $dl = url('msg_file', ['id' => $f['id'], 'dl' => 1]);
$isImg = in_array($f['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true); $isPdf = $f['mime'] === 'application/pdf';
$ext = strtoupper(pathinfo($f['stored'], PATHINFO_EXTENSION));
$size = (int)$f['size'] >= 1048576 ? number_format($f['size'] / 1048576, 1, ',', '') . ' Mo' : max(1, (int)round($f['size'] / 1024)) . ' Ko'; ?>
<div class="viewer" data-viewer data-src="<?= e($raw) ?>" data-name="<?= e($f['original']) ?>" data-mime="<?= e($f['mime']) ?>">
  <div class="viewer-bar">
    <a class="btn ghost sm" href="<?= e($back) ?>" data-back><?= icon('chevron-left') ?> Retour</a>
    <div class="viewer-name"><strong><?= e($f['original']) ?></strong><small class="muted"><?= e($ext) ?> · <?= $size ?> · <?= e(fdatetime($f['sent_at'])) ?></small></div>
    <div class="viewer-acts">
      <button type="button" class="btn sm" data-share hidden><?= icon('send') ?> <span>Enregistrer / partager</span></button>
      <a class="btn soft sm" href="<?= e($dl) ?>" download="<?= e($f['original']) ?>" data-download><?= icon('download') ?> <span>Télécharger</span></a>
    </div>
  </div>
  <div class="viewer-body">
    <?php if ($isImg): ?><img src="<?= e($raw) ?>" alt="<?= e($f['original']) ?>">
    <?php elseif ($isPdf): ?><iframe src="<?= e(url('msg_file', ['id' => $f['id'], 'embed' => 1])) ?>" title="<?= e($f['original']) ?>"></iframe>
      <p class="muted viewer-hint"><small>Le document ne s'affiche pas en entier ? Utilisez les boutons en haut pour l'enregistrer ou l'ouvrir dans une autre app.</small></p>
    <?php else: ?><div class="viewer-file"><?= icon('file-text') ?><strong><?= e($f['original']) ?></strong><p class="muted">Aperçu indisponible pour ce type de fichier. Enregistrez-le ou ouvrez-le avec une autre app.</p></div><?php endif ?>
  </div>
</div>
