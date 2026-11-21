<?php // Contrôles à venir. $s (élève), $tests ; ajout possible par la tutrice, le parent ou l'élève.
$me = current_user(); $isKid = $me['role'] === 'student';
$lvSubjects = array_map(fn($x) => $x[0], (require APP_ROOT . '/app/questionnaires/_levels.php')['subjects']);
$cyc = grade_cycle($s['grade'] ?? null);
$lvMap = (require APP_ROOT . '/app/questionnaires/_levels.php')['subjects']; ?>
<div class="card <?= e($tone ?? '') ?>">
  <h2><?= icon('flag') ?> <?= $isKid ? 'Mes contrôles à venir' : 'Contrôles à venir' ?></h2>
  <?php if (!$tests): ?><p class="muted"><?= $isKid ? 'Aucun contrôle noté. Ajoute-les dès que tu les connais !' : 'Aucun contrôle noté pour l\'instant.' ?></p><?php endif ?>
  <ul class="list"><?php foreach ($tests as $t): $days = (int)floor((strtotime($t['test_date']) - strtotime('today')) / 86400); ?>
    <li><span class="date-chip <?= $days <= 3 ? 'soon' : '' ?>"><b><?= date('d', strtotime($t['test_date'])) ?></b><?= e(['', 'janv', 'févr', 'mars', 'avr', 'mai', 'juin', 'juil', 'août', 'sept', 'oct', 'nov', 'déc'][(int)date('n', strtotime($t['test_date']))]) ?></span>
      <div><strong><?= e($t['subject']) ?></strong> · <?= e(TEST_KINDS[$t['kind']] ?? 'Contrôle') ?><?= $t['title'] ? '<br><small class="muted">' . e($t['title']) . '</small>' : '' ?>
        <br><small class="muted"><?= $days === 0 ? "Aujourd'hui" : ($days === 1 ? 'Demain' : "Dans $days jours") ?></small></div>
      <?php if ($me['role'] === 'tutor' || (int)$t['created_by'] === (int)$me['id']): ?>
        <form method="post" action="<?= e(url('test')) ?>" class="inv-actions" data-confirm="Retirer ce contrôle ?"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>"><input type="hidden" name="delete_id" value="<?= (int)$t['id'] ?>"><button class="edit-btn" title="Retirer" aria-label="Retirer"><?= icon('trash-2') ?></button></form>
      <?php endif ?></li>
  <?php endforeach ?></ul>
  <details class="add-test"><summary><?= icon('plus') ?> Ajouter un contrôle</summary>
    <form method="post" action="<?= e(url('test')) ?>" class="stack"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>">
      <div class="form-grid">
        <label>Date<input type="date" name="test_date" required min="<?= date('Y-m-d') ?>"></label>
        <label>Matière<select name="subject" required><option value="">Choisir…</option>
          <?php foreach ($lvMap as $k => [$l, $levels]): if ($cyc && !in_array($cyc, $levels, true)) continue; ?><option><?= e($l) ?></option><?php endforeach ?></select></label>
        <label>Type<select name="kind"><?php foreach (TEST_KINDS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach ?></select></label>
      </div>
      <label>Sujet (facultatif)<input name="title" maxlength="160" placeholder="ex. Chapitre 4 : les fractions"></label>
      <button class="btn sm"><?= icon('plus') ?> Ajouter</button>
    </form>
  </details>
</div>
