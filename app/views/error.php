<div class="card auth-card"><h2><?= icon('triangle-alert') ?> Accès impossible</h2><p><?= e($msg) ?></p>
<?php if (!in_passation()): ?><a class="btn ghost" href="<?= e(url('home')) ?>"><?= icon('house') ?> Retour</a><?php endif ?></div>
