<?php $title = 'Bienvenue';
$isParent = $u['role'] === 'parent';
$keys = array_keys($steps); $idx = array_search($step, $keys, true); $total = count($keys);
$kidName = $s ? kid($s['first_name']) : 'votre enfant';
$qDone = $sub && $sub['status'] === 'completed';
$hid = fn(string $action = 'next') => csrf_field() . '<input type="hidden" name="step" value="' . e($step) . '"><input type="hidden" name="action" value="' . $action . '">';
$nav = function (string $label = 'Continuer', bool $ghost = false) use ($idx, $keys) {
    $prev = $idx > 0 ? '<a class="btn ghost" href="' . e(url('welcome', ['step' => $keys[$idx - 1]])) . '">' . icon('chevron-left') . ' Retour</a>' : '<span></span>';
    return '<div class="wl-nav">' . $prev . '<button class="btn' . ($ghost ? ' ghost' : '') . '">' . e($label) . ' ' . icon('chevron-right') . '</button></div>';
};
$needsList = ['dys' => 'Trouble « dys »', 'tdah' => 'TDAH / attention', 'hpi' => 'Haut potentiel', 'anxiety' => 'Anxiété scolaire', 'other' => 'Autre'];
$accList = ['pap' => 'PAP', 'pps' => 'PPS', 'pai' => 'PAI', 'tiers_temps' => 'Tiers-temps'];
$needs = json_decode((string)($s['needs_json'] ?? ''), true) ?: ['items' => [], 'acc' => []]; ?>
<div class="wl">
  <div class="wl-top">
    <span class="logo">leevel<span>.app</span></span>
    <?php if ($step !== 'done'): ?><form method="post"><?= $hid('skip_all') ?><button class="wl-skip">Passer et découvrir mon espace</button></form><?php endif ?>
  </div>
  <ol class="wl-steps" aria-label="Étapes">
    <?php foreach ($steps as $k => [$lb, $ic]): $i = array_search($k, $keys, true); ?>
      <li class="<?= $i < $idx ? 'done' : ($i === $idx ? 'on' : '') ?>"><span class="wl-dot"><?= $i < $idx ? icon('check') : $i + 1 ?></span><span class="wl-lbl"><?= e($lb) ?></span></li>
    <?php endforeach ?>
  </ol>
  <div class="bar wl-bar"><i data-w="<?= (int)round($idx / max(1, $total - 1) * 100) ?>"></i></div>

  <div class="card wl-card">
  <?php if ($step === 'intro'): ?>
    <div class="wl-hero"><?= $isParent ? '👋' : '🚀' ?></div>
    <h1><?= $isParent ? 'Bienvenue sur leevel.app !' : 'Bienvenue, ' . e($u['display_name']) . ' !' ?></h1>
    <?php if ($isParent): ?>
      <p class="wl-lead">Ici, vous suivez l'accompagnement scolaire de <?= e($kidName) ?> : séances, bilans, progrès et échanges avec <?= !empty($tutor) ? e($tutor['display_name']) : 'la tutrice' ?>.</p>
      <p>Avant de commencer, quelques étapes rapides pour que la tutrice dispose de tout ce qu'il faut :</p>
    <?php else: ?>
      <p class="wl-lead">C'est ton espace pour suivre tes séances, tes missions et tes progrès avec <?= !empty($tutor) ? e($tutor['display_name']) : 'ta tutrice' ?>.</p>
      <p>Avant de commencer, deux petites étapes :</p>
    <?php endif ?>
    <?php $intro = $isParent ? [
        'contact' => ['Vos coordonnées', 'Pour que la tutrice puisse vous joindre.', '1 min'],
        'child' => ['Présenter ' . $kidName, 'Sa classe, son établissement, ses besoins éventuels.', '2 min'],
        'questionnaire' => ['Répondre au questionnaire', 'Sa situation et vos attentes.', '10 min'],
        'wishes' => ['L\'accompagnement souhaité', 'Volume, besoins et formule.', ''],
        'payment' => ['Modalités de paiement', 'Tarif et modalités convenus.', '1 min'],
      ] : [
        'questionnaire' => ['Répondre à ton questionnaire', 'Pour des séances faites pour toi.', '10 min'],
        'tour' => ['Découvrir ton espace', 'Tes onglets en 30 secondes.', '1 min'],
      ]; ?>
    <ol class="wl-list"><?php $n = 0; foreach (array_slice($steps, 1, -1, true) as $k => [$lb, $ic]): $n++; [$t, $d, $dur] = $intro[$k] ?? [$lb, '', '']; ?>
      <li><span class="wl-li-ic"><?= icon($ic) ?></span><div><strong><?= $n ?>. <?= e($t) ?></strong><?php if ($d): ?><small><?= e($d) ?></small><?php endif ?></div></li>
    <?php endforeach ?></ol>
    <p class="muted"><small>Vous pouvez vous arrêter à tout moment : tout est enregistré au fur et à mesure.</small></p>
    <form method="post"><?= $hid() ?><?= $nav('Commencer') ?></form>

  <?php elseif ($step === 'contact'): ?>
    <h1><?= icon('message-circle') ?> Vos coordonnées</h1>
    <p class="wl-lead">Pour que la tutrice puisse vous joindre en cas de besoin (absence, imprévu…).</p>
    <form method="post" class="stack"><?= $hid('save') ?>
      <label>Votre nom<input name="display_name" required maxlength="100" value="<?= e($me['display_name']) ?>"></label>
      <div class="form-grid">
        <label>Téléphone<input type="tel" name="phone" maxlength="30" value="<?= e($me['phone'] ?? '') ?>" placeholder="06 12 34 56 78"></label>
        <label>E-mail<input type="email" name="email" maxlength="120" value="<?= e($me['email'] ?? '') ?>" placeholder="vous@exemple.fr"></label>
      </div>
      <label>Adresse <small class="muted">pour les séances à domicile</small><input name="address" maxlength="255" value="<?= e($me['address'] ?? ($s['parent_address'] ?? '')) ?>"></label>
      <label>Préférences de contact <small class="muted">facultatif</small><input name="contact_notes" maxlength="500" value="<?= e($s['contact_notes'] ?? '') ?>" placeholder="ex. plutôt par SMS, après 18h"></label>
      <label class="check"><input type="checkbox" name="notify_messages" value="1" <?= (int)($me['notify_messages'] ?? 1) ? 'checked' : '' ?>> Me prévenir par e-mail quand j'ai un message non lu</label>
      <?= $nav() ?>
    </form>

  <?php elseif ($step === 'child'): ?>
    <h1><?= icon('user') ?> Votre enfant</h1>
    <p class="wl-lead">Quelques informations pour que la tutrice connaisse <?= e($kidName) ?>.</p>
    <form method="post" class="stack"><?= $hid('save') ?>
      <div class="form-grid">
        <label>Prénom<input name="first_name" required maxlength="80" value="<?= e(($s['first_name'] ?? '') === CHILD_PLACEHOLDER ? '' : ($s['first_name'] ?? '')) ?>"></label>
        <label>Nom<input name="last_name" maxlength="80" value="<?= e($s['last_name'] ?? '') ?>"></label>
        <label>Date de naissance<input type="date" name="birth_date" value="<?= e($s['birth_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></label>
        <label>Classe<?= grade_select('grade', $s['grade'] ?? null) ?></label>
      </div>
      <label>Établissement <small class="muted">facultatif</small><input name="school" maxlength="120" value="<?= e($s['school'] ?? '') ?>"></label>
      <details class="wl-more" <?= $needs['items'] || $needs['acc'] ? 'open' : '' ?>><summary>Besoins particuliers <small class="muted">(facultatif)</small></summary>
        <div class="stack">
          <div><span class="ql">Difficultés ou particularités</span><div class="pills"><?php foreach ($needsList as $k => $l): ?><label class="pill"><input type="checkbox" name="needs[]" value="<?= $k ?>" <?= in_array($k, $needs['items'], true) ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div></div>
          <div><span class="ql">Dispositifs en place à l'école</span><div class="pills"><?php foreach ($accList as $k => $l): ?><label class="pill"><input type="checkbox" name="acc[]" value="<?= $k ?>" <?= in_array($k, $needs['acc'], true) ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div></div>
          <label>Aménagements utiles<input name="accommodations" maxlength="500" value="<?= e($s['accommodations'] ?? '') ?>" placeholder="ex. consignes lues à voix haute, pauses fréquentes"></label>
          <label>Autre chose à savoir ?<textarea name="parent_notes" maxlength="2000" rows="2"><?= e($s['parent_notes'] ?? '') ?></textarea></label>
          <p class="muted"><small>Ces informations restent confidentielles entre vous et la tutrice.</small></p>
        </div>
      </details>
      <?= $nav() ?>
    </form>

  <?php elseif ($step === 'questionnaire'): ?>
    <h1><?= icon('clipboard-list') ?> <?= $isParent ? 'Le questionnaire' : 'Ton questionnaire' ?></h1>
    <?php if (!$isParent && $s && empty($s['guardian_consent_at'])): ?>
      <p class="alert warn"><?= icon('lock') ?> Ton questionnaire sera disponible dès que ton parent aura validé l'autorisation d'utilisation.</p>
      <form method="post"><?= $hid() ?><?= $nav() ?></form>
    <?php else: ?>
      <p class="wl-lead"><?= $isParent ? 'Une dizaine de minutes pour présenter la situation de ' . e($kidName) . ' : ses difficultés, ce qui a déjà été essayé, vos attentes. C\'est la base de l\'accompagnement.' : 'Quelques questions sur toi, ta façon de travailler et ce que tu aimes : pour que les séances soient vraiment faites pour toi. Il n\'y a pas de bonne ou de mauvaise réponse.' ?></p>
      <div class="wl-q <?= $qDone ? 'done' : '' ?>"><span class="wl-q-ic"><?= $qDone ? icon('circle-check') : icon('clipboard-list') ?></span>
        <div><strong><?= $qDone ? 'Questionnaire terminé, merci !' : ($sub ? 'Questionnaire commencé' : 'Pas encore commencé') ?></strong><small class="muted"><?= $qDone ? 'Vos réponses ont bien été transmises à la tutrice.' : 'Vous reviendrez ici automatiquement à la fin.' ?></small></div>
        <?php if (!$qDone): ?><a class="btn" href="<?= e(url('q')) ?>"><?= icon('play') ?> <?= $sub ? 'Reprendre' : 'Commencer' ?></a><?php endif ?></div>
      <form method="post"><?= $hid() ?><?= $nav($qDone ? 'Continuer' : 'Plus tard', !$qDone) ?></form>
    <?php endif ?>

  <?php elseif ($step === 'wishes'): $w = wishes_of($s); $subjOpts = wish_subject_options($s); ?>
    <h1><?= icon('target') ?> L'accompagnement souhaité</h1>
    <p class="wl-lead">Ce que vous attendez de l'accompagnement de <?= e($kidName) ?>. Rien n'est figé : vous en reparlerez avec la tutrice.</p>
    <form method="post" class="stack wl-wish"><?= $hid('save') ?>
      <label>Combien d'heures par semaine ?<input name="hours" maxlength="40" value="<?= e(isset(WISH_HOURS[$w['hours']]) ? WISH_HOURS[$w['hours']] : $w['hours']) ?>" placeholder="ex. 2 h, 1 h 30, à voir avec la tutrice"></label>
      <div><span class="ql">Quels sont les besoins ? <small class="muted">plusieurs choix possibles</small></span>
        <div class="pills"><?php foreach (WISH_NEEDS as $k => $l): ?><label class="pill"><input type="checkbox" name="needs[]" value="<?= $k ?>" <?= in_array($k, $w['needs'], true) ? 'checked' : '' ?> <?= $k === 'subjects' ? 'data-wish-subj' : '' ?><?= $k === 'other' ? ' data-wish-other' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div></div>
      <div class="wl-sub" data-wish-subj-box <?= in_array('subjects', $w['needs'], true) ? '' : 'hidden' ?>><span class="ql">Quelles matières ?</span>
        <div class="pills"><label class="pill"><input type="checkbox" name="general" value="1" <?= $w['general'] ? 'checked' : '' ?>><span>Soutien général (toutes matières)</span></label><?php foreach ($subjOpts as $k => $l): ?><label class="pill"><input type="checkbox" name="subjects[]" value="<?= e($k) ?>" <?= in_array($k, $w['subjects'], true) ? 'checked' : '' ?>><span><?= e($l) ?></span></label><?php endforeach ?></div></div>
      <label class="wl-sub" data-wish-other-box <?= in_array('other', $w['needs'], true) ? '' : 'hidden' ?>>Précisez<input name="other" maxlength="200" value="<?= e($w['other']) ?>" placeholder="ex. préparer l'entrée en 6e"></label>
      <div><span class="ql">Plutôt en présentiel ou en visio ?</span>
        <div class="pills"><?php foreach (WISH_MODES as $k => $l): ?><label class="pill"><input type="radio" name="mode" value="<?= $k ?>" <?= $w['mode'] === $k ? 'checked' : '' ?> data-wish-mode><span><?= e($l) ?></span></label><?php endforeach ?></div>
        <label class="wl-sub wl-sub-in" data-wish-mode-box <?= $w['mode'] === 'other' ? '' : 'hidden' ?>>Précisez<input name="mode_other" maxlength="200" value="<?= e($w['mode_other']) ?>" placeholder="ex. présentiel au début, puis visio"></label></div>
      <label>Jours et créneaux qui vous arrangent <small class="muted">facultatif</small><input name="slots" maxlength="300" value="<?= e($w['slots']) ?>" placeholder="ex. mercredi après-midi, samedi matin"></label>
      <label>Autre chose à préciser ? <small class="muted">facultatif</small><textarea name="note" maxlength="1000" rows="2"><?= e($w['note']) ?></textarea></label>
      <?= $nav() ?>
    </form>

  <?php elseif ($step === 'payment'): $rate = $s ? rate_for($s, 'presentiel') : null; $terms = $s ? family_terms($s) : ''; $instr = setting('pay_instructions'); ?>
    <h1><?= icon('file-text') ?> Modalités de paiement</h1>
    <p class="wl-lead">Voici les modalités convenues avec la tutrice. Vous retrouverez tout dans l'onglet « Finances ».</p>
    <dl class="fields wl-box">
      <div><dt>Tarif horaire</dt><dd><?= $rate !== null ? e(money($rate)) . ' / h' : '<span class="muted">Sera précisé par la tutrice</span>' ?></dd></div>
      <div><dt>Rythme de paiement</dt><dd><?= $terms !== '' ? e($terms) : '<span class="muted">Sera précisé par la tutrice</span>' ?></dd></div>
      <?php if ((float)($s['discount_pct'] ?? 0) > 0): ?><div class="wide"><dt>Remise</dt><dd><?= e(discount_text($s['discount_label'] ?? null, (float)$s['discount_pct'])) ?></dd></div><?php endif ?>
      <?php if ($instr): ?><div class="wide"><dt>Comment régler</dt><dd><?= nl2br(e($instr)) ?></dd></div><?php endif ?>
      <?php if (tax_credit_on()): ?><div class="wide"><dt>Crédit d'impôt</dt><dd>Les cours ouvrent droit à un crédit d'impôt de 50 % des sommes versées.</dd></div><?php endif ?>
    </dl>
    <p class="wl-note"><?= icon('message-circle') ?> Chaque échéance vous sera notifiée dans la messagerie, avec le détail des séances.</p>
    <form method="post"><?= $hid() ?><?= $nav() ?></form>

  <?php elseif ($step === 'tour'): ?>
    <h1><?= icon('house') ?> Ton espace en 30 secondes</h1>
    <ul class="wl-tour">
      <li><span>🏠</span><div><strong>Mon espace</strong><small>Tes missions, ta prochaine séance et ton humeur de la semaine.</small></div></li>
      <li><span>📅</span><div><strong>Planning</strong><small>Toutes tes séances, avec le détail de chacune.</small></div></li>
      <li><span>📚</span><div><strong>Mes matières</strong><small>Tes notes, tes chapitres, des quiz pour t'entraîner, et le bilan de chaque séance.</small></div></li>
      <li><span>💡</span><div><strong>Mes outils</strong><small>Chrono de travail, méthodes pour retenir, fiches.</small></div></li>
      <li><span>💬</span><div><strong>Messagerie</strong><small>Écris à <?= !empty($tutor) ? e($tutor['display_name']) : 'ta tutrice' ?> quand tu bloques ou si tu as une question.</small></div></li>
    </ul>
    <form method="post"><?= $hid() ?><?= $nav() ?></form>

  <?php else: ?>
    <div class="wl-hero">🎉</div>
    <h1><?= $isParent ? 'Tout est prêt !' : "C'est parti !" ?></h1>
    <p class="wl-lead"><?= $isParent ? 'Merci ! La tutrice a maintenant ce qu\'il faut pour préparer l\'accompagnement de ' . e($kidName) . '. Vous retrouverez les séances, les bilans et les échanges dans votre espace.' : 'Ton espace est prêt. Bonne première séance !' ?></p>
    <?php if (!$qDone && ($isParent || !empty($s['guardian_consent_at']))): ?><p class="alert warn"><?= icon('clipboard-list') ?> Pensez à terminer le questionnaire : il reste accessible depuis le menu « Mon compte ».</p><?php endif ?>
    <form method="post"><?= $hid('finish') ?><div class="wl-nav"><a class="btn ghost" href="<?= e(url('welcome', ['step' => $keys[max(0, $idx - 1)]])) ?>"><?= icon('chevron-left') ?> Retour</a><button class="btn"><?= $isParent ? 'Découvrir mon espace' : 'Découvrir mon espace' ?> <?= icon('chevron-right') ?></button></div></form>
  <?php endif ?>
  </div>
</div>
