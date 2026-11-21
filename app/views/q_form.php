<?php $title = $cfg['title']; $isStudent = $code === 'student';
$cycle = grade_cycle($code === 'parent' ? ($answers['p_class'] ?? $s['grade']) : ($answers['s_class'] ?? $s['grade']));
$lvAttr = fn(array $qq, string $ov) => isset($qq['levels'][$ov]) ? ' data-levels="' . e(implode(' ', $qq['levels'][$ov])) . '"' . ($cycle && !in_array($cycle, $qq['levels'][$ov], true) ? ' hidden' : '') : '';
$renderQ = function (array $qq) use ($answers, $lvAttr): void {
    $k = $qq['key']; $v = $answers[$k] ?? null; $name = "a[$k]";
    $attrs = 'class="qq" data-key="' . e($k) . '" data-type="' . e($qq['type']) . '"' . (!empty($qq['show_if']) ? ' data-if="' . e(json_encode($qq['show_if'])) . '"' : '');
    echo "<div $attrs><span class=\"ql\" id=\"l-" . e($k) . '">' . e($qq['label']) . (!empty($qq['hint']) ? ' <span class="q-hint">' . e($qq['hint']) . '</span>' : '') . '</span>';
    if (!empty($qq['help'])) echo '<p class="help">' . e($qq['help']) . '</p>';
    switch ($qq['type']) {
        case 'single': case 'multi':
            $multi = $qq['type'] === 'multi';
            echo '<div class="pills" role="group" aria-labelledby="l-' . e($k) . '">';
            foreach ($qq['options'] as $ov => $ol) {
                $checked = $multi ? in_array((string)$ov, array_map('strval', (array)$v), true) : (string)$v === (string)$ov;
                echo '<label class="pill"' . $lvAttr($qq, $ov) . '><input type="' . ($multi ? 'checkbox' : 'radio') . '" name="' . e($name) . ($multi ? '[]' : '') . '" value="' . e($ov) . '"' . ($checked ? ' checked' : '') . '><span>' . e($ol) . '</span></label>';
            }
            echo '</div>';
            break;
        case 'select':
            echo '<select name="' . e($name) . '" aria-labelledby="l-' . e($k) . '"' . (!empty($qq['level_source']) ? ' data-level-source' : '') . ' class="q-select"><option value="">' . e($qq['placeholder'] ?? 'Choisir…') . '</option>';
            foreach ($qq['options'] as $ov => $ol) echo '<option value="' . e($ov) . '"' . ($v !== null && (string)$v === (string)$ov ? ' selected' : '') . '>' . e($ol) . '</option>';
            echo '</select>';
            break;
        case 'multi_prio':
            $sel = is_array($v) ? $v : (is_string($v) && $v !== '' ? [$v] : []);
            echo '<div class="pills prio-pills" role="group" aria-labelledby="l-' . e($k) . '" data-prio-group data-name="' . e($name) . '">';
            foreach ($qq['options'] as $ov => $ol) {
                $pos = array_search($ov, $sel, true);
                $state = $pos === false ? '' : ($pos === 0 ? 'prio' : 'sec');
                echo '<button type="button" class="pill-btn ' . $state . '" data-value="' . e($ov) . '"' . $lvAttr($qq, $ov) . ' aria-pressed="' . ($state ? 'true' : 'false') . '">'
                    . ($state === 'prio' ? icon('flag') . ' ' : '') . e($ol) . '</button>';
            }
            echo '</div><div data-prio-inputs>';
            foreach ($sel as $ov) echo '<input type="hidden" name="' . e($name) . '[]" value="' . e($ov) . '">';
            echo '</div><p class="prio-legend"><span class="pill-btn prio sm-legend">Priorité</span> <span class="pill-btn sec sm-legend">Secondaire</span></p>';
            break;
        case 'scale':
            echo '<div class="scale" role="group" aria-labelledby="l-' . e($k) . '"><span class="ends">' . e($qq['min'] ?? '') . '</span><div class="pills">';
            for ($i = 1; $i <= 5; $i++) echo '<label class="pill"><input type="radio" name="' . e($name) . '" value="' . $i . '"' . ($v === $i ? ' checked' : '') . ' aria-label="' . $i . ' sur 5"><span>' . $i . '</span></label>';
            echo '</div><span class="ends">' . e($qq['max'] ?? '') . '</span></div>';
            break;
        case 'grades':
            $rows = is_array($v) ? $v : (is_string($v) && $v !== '' ? [['subject' => '', 'avg' => null, 'comment' => $v]] : []);
            if (!$rows) $rows = [['subject' => '', 'avg' => null, 'comment' => '']];
            $rowHtml = function ($i, $r) use ($name, $qq, $lvAttr) {
                $o = '<option value="">Matière…</option>';
                foreach ($qq['options'] as $ov => $ol) $o .= '<option value="' . e($ov) . '"' . (($r['subject'] ?? '') === $ov ? ' selected' : '') . str_replace(' hidden', ($r['subject'] ?? '') === $ov ? '' : ' hidden', $lvAttr($qq, $ov)) . '>' . e($ol) . '</option>';
                return '<div class="grade-row" data-grade-row>'
                    . '<select name="' . e($name) . '[' . $i . '][subject]" aria-label="Matière">' . $o . '</select>'
                    . '<input type="number" name="' . e($name) . '[' . $i . '][avg]" min="0" max="20" step="0.5" inputmode="decimal" placeholder="Moy. /20" value="' . e($r['avg'] ?? '') . '" aria-label="Moyenne sur 20">'
                    . '<input type="text" name="' . e($name) . '[' . $i . '][comment]" maxlength="200" placeholder="Commentaire (facultatif)" value="' . e($r['comment'] ?? '') . '" aria-label="Commentaire">'
                    . '<button type="button" class="edit-btn" data-grade-del title="Supprimer la ligne" aria-label="Supprimer la ligne">' . icon('trash-2') . '</button></div>';
            };
            echo '<div class="grades" data-grades data-name="' . e($name) . '">';
            foreach (array_values($rows) as $i => $r) echo $rowHtml($i, $r);
            echo '</div><button type="button" class="btn ghost sm" data-grade-add>' . icon('plus') . ' Ajouter une matière</button>';
            break;
        case 'textarea':
            echo '<textarea name="' . e($name) . '" maxlength="2000" aria-labelledby="l-' . e($k) . '">' . e(is_string($v) && $v !== SKIP ? $v : '') . '</textarea>';
            break;
        default:
            echo '<input type="text" name="' . e($name) . '" maxlength="200" value="' . e(is_string($v) && $v !== SKIP ? $v : '') . '" aria-labelledby="l-' . e($k) . '">';
    }
    if (!empty($qq['sensitive'])) {
        echo '<div class="skip pills"><label class="pill"><input type="' . ($qq['type'] === 'multi' ? 'checkbox' : 'radio') . '" name="' . e($name) . '" value="' . SKIP . '"' . ($v === SKIP ? ' checked' : '') . '><span>Je préfère ne pas répondre</span></label></div>';
    }
    echo '</div>';
}; ?>
<?php if ($tutor): ?><div class="alert warn row between"><span><?= icon('pencil') ?> Vous remplissez ce questionnaire pour <strong><?= e($respondent['display_name']) ?></strong>. Les réponses sont enregistrées dans son questionnaire.</span><a class="btn ghost sm" href="<?= e(url('t_rdv', ['sid' => $s['id']])) ?>"><?= icon('chevron-left') ?> Retour</a></div><?php endif ?>
<div class="q-head">
  <div class="row between"><h1><?= e($isStudent ? 'Salut ' . $s['first_name'] . ' 👋' : $cfg['title']) ?></h1><span class="badge" id="stepno"></span></div>
  <?php if ((int)$sub['current_step'] === 0 && !$answers): ?><p class="muted"><?= e($cfg['intro']) ?></p><?php endif ?>
  <div class="progress" aria-hidden="true"><?php foreach ($cfg['steps'] as $_): ?><span></span><?php endforeach ?></div>
</div>
<div class="q-layout">
<nav class="q-steps card" aria-label="Étapes du questionnaire">
  <ol><?php foreach ($cfg['steps'] as $i => $st): ?>
    <li><button type="button" data-goto="<?= $i ?>"><span class="q-step-num"><?= $i + 1 ?></span><span><?= e($st['title']) ?></span></button></li>
  <?php endforeach ?></ol>
</nav>
<form id="qform" method="post" action="<?= e(url('q_submit', $qp)) ?>" data-save="<?= e(url('q_save', $qp)) ?>" data-step="<?= (int)$step ?>" class="card">
  <?= csrf_field() ?>
  <?php foreach ($cfg['steps'] as $i => $st): ?>
    <section class="step" aria-label="<?= e($st['title']) ?>">
      <h2><?= icon($st['icon'] ?? 'clipboard-list') ?> <?= e($st['title']) ?></h2>
      <?php if (!empty($st['intro'])): ?><p class="muted"><?= e($st['intro']) ?></p><?php endif ?>
      <?php $rowOpen = false;
      foreach ($st['questions'] as $qq) {
          $half = !empty($qq['half']);
          if ($half && !$rowOpen) { echo '<div class="q-row">'; $rowOpen = true; }
          if (!$half && $rowOpen) { echo '</div>'; $rowOpen = false; }
          $renderQ($qq);
      }
      if ($rowOpen) echo '</div>'; ?>
    </section>
  <?php endforeach ?>
  <div class="q-nav">
    <button type="button" class="btn ghost" id="prev"><?= icon('chevron-left') ?> Précédent</button>
    <span class="saved" id="saved"><?= $answers ? 'Réponses enregistrées - vous pouvez reprendre plus tard' : '' ?></span>
    <button type="button" class="btn" id="next">Suivant <?= icon('chevron-right') ?></button>
    <button class="btn" id="finish" hidden><?= icon('check') ?> <?= $isStudent ? "J'ai fini !" : 'Terminer' ?></button>
  </div>
</form>
</div>
