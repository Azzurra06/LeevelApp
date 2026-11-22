<?php // Dernière séance : bilan + ressenti de l'élève. $recap, $sidp, $s
$moodEmo = ['good' => '😄', 'ok' => '😐', 'hard' => '😣']; ?>
<div class="card <?= e($tone ?? '') ?>">
  <h2><?= icon('calendar') ?> Dernière séance</h2>
  <?php if (!$recap): ?><p class="muted">Aucune séance passée pour l'instant.</p><?php else: $rep = $recap['report']; $fb = $recap['feedback']; ?>
    <p><strong><?= e(fdatetime($recap['starts_at'])) ?></strong> · <span class="muted"><?= e(session_label($recap)) ?></span></p>
    <?php if ($rep && ($rep['worked_on'] || $rep['parent_summary'])): ?><p data-clamp="110"><?= nl2br(e($rep['worked_on'] ?: $rep['parent_summary'])) ?></p>
    <?php else: ?><p class="muted"><small>Bilan pas encore rédigé.</small></p><?php endif ?>
    <?php if ($fb): ?><div class="fb-box"><strong><?= $moodEmo[$fb['mood']] ?? '🙂' ?> Ressenti de <?= e($s['first_name']) ?><?= $fb['mood'] ? ' : ' . e(mb_strtolower(MOODS[$fb['mood']])) : '' ?></strong>
      <?php if (!empty($fb['note'])): ?><p>« <?= e($fb['note']) ?> »</p><?php endif ?>
      <?php if ($fb['understood']): ?><p><small class="muted">A compris :</small> <?= e($fb['understood']) ?></p><?php endif ?>
      <?php if ($fb['to_deepen']): ?><p><small class="muted">À approfondir :</small> <?= e($fb['to_deepen']) ?></p><?php endif ?>
      <?php if ($fb['needs_next']): ?><p><small class="muted">Besoin :</small> <?= e($fb['needs_next']) ?></p><?php endif ?></div>
    <?php else: ?><p class="muted"><small><?= e($s['first_name']) ?> n'a pas encore donné son ressenti.</small></p><?php endif ?>
    <a class="card-link" href="<?= e(url('t_student', $sidp + ['tab' => 'seances'])) ?>#sess-<?= (int)$recap['id'] ?>"><?= $rep ? 'Voir le bilan' : 'Rédiger le bilan' ?> →</a>
  <?php endif ?>
</div>
