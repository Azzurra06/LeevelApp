<?php // Étapes de démarrage (parent / élève). $u, $s ; affiché tant que les étapes essentielles ne sont pas faites.
$me = one('SELECT * FROM users WHERE id=?', [$u['id']]);
$isParent = $u['role'] === 'parent';
$sub = qn_latest($isParent ? 'parent' : 'student', (int)$s['id']);
$qDone = $sub && $sub['status'] === 'completed';
$steps = $isParent ? [
    ['done' => !empty($me['phone']) || !empty($me['email']), 'req' => true, 'icon' => 'message-circle', 'title' => 'Compléter mes coordonnées', 'desc' => 'Téléphone et e-mail, pour que la tutrice puisse vous joindre.', 'url' => url('account'), 'cta' => 'Compléter'],
    ['done' => $qDone, 'req' => true, 'icon' => 'clipboard-list', 'title' => 'Répondre au questionnaire', 'desc' => 'Une dizaine de minutes pour présenter la situation de ' . $s['first_name'] . '.', 'url' => url('q'), 'cta' => $sub ? 'Reprendre' : 'Commencer'],
    ['done' => (bool)one('SELECT 1 FROM messages WHERE sender_id=? LIMIT 1', [$u['id']]), 'req' => false, 'icon' => 'send', 'title' => 'Dire bonjour à la tutrice', 'desc' => 'Facultatif : un premier message dans « Messagerie ».', 'url' => url('p_messages'), 'cta' => 'Écrire'],
] : [
    ['done' => $qDone, 'req' => true, 'icon' => 'clipboard-list', 'title' => 'Répondre à mon questionnaire', 'desc' => 'Pour que les séances soient vraiment faites pour toi.', 'url' => url('q'), 'cta' => $sub ? 'Reprendre' : 'Commencer'],
    ['done' => $sub && !empty(qn_answers((int)$sub['id'])['s_interests']), 'req' => false, 'icon' => 'sparkles', 'title' => 'Compléter mon profil', 'desc' => "Ta classe, tes centres d'intérêt… pour des séances qui te ressemblent.", 'url' => url('account'), 'cta' => 'Compléter'],
];
$pending = array_filter($steps, fn($x) => $x['req'] && !$x['done']);
if (!$pending) return;
$doneCount = count(array_filter($steps, fn($x) => $x['done'])); ?>
<?php $obNext = array_values(array_filter($steps, fn($x) => !$x['done']))[0] ?? null; ?>
<details class="card onboarding">
  <summary>
    <div class="row between"><h2><?= icon('rocket') ?> <?= $isParent ? 'Pour bien démarrer' : 'Pour commencer' ?></h2>
      <span class="row"><span class="badge sage"><?= $doneCount ?> / <?= count($steps) ?></span><?= icon('chevron-right', 'chev') ?></span></div>
    <div class="bar bar-gap"><i data-w="<?= (int)round($doneCount / count($steps) * 100) ?>"></i></div>
    <?php if ($obNext): ?><div class="ob-next"><span class="muted">Prochaine étape :</span> <strong><?= e($obNext['title']) ?></strong>
      <a class="btn sm" href="<?= e($obNext['url']) ?>"><?= e($obNext['cta']) ?></a></div><?php endif ?>
  </summary>
  <ol class="ob-steps">
    <?php foreach ($steps as $i => $st): ?>
      <li class="<?= $st['done'] ? 'done' : '' ?>">
        <span class="ob-num"><?= $st['done'] ? icon('check') : $i + 1 ?></span>
        <div class="ob-text"><strong><?= e($st['title']) ?></strong><small class="muted"><?= e($st['desc']) ?></small></div>
        <?php if (!$st['done']): ?><a class="btn <?= $st['req'] ? '' : 'ghost' ?> sm" href="<?= e($st['url']) ?>"><?= e($st['cta']) ?></a><?php else: ?><span class="badge sage">Fait</span><?php endif ?>
      </li>
    <?php endforeach ?>
  </ol>
</details>
