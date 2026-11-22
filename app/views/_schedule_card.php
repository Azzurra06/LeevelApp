<?php // Rythme convenu avec la famille. $s ; $scheduleEdit (bool) : formulaire de la tutrice.
$sched = schedule_of($s);
$scheduleEdit ??= false;
$slotsForm = $sched['slots'] ?? [['day' => 1, 'time' => '17:00', 'duration' => 120, 'mode' => 'presentiel']];
$durations = [30, 45, 60, 75, 90, 120, 150, 180]; ?>
<div class="card schedule-card<?= $scheduleEdit ? ' editable' . ($sched ? '' : ' editing') : '' ?>" id="rythme">
  <div class="ed-head"><h2><?= icon('calendar') ?> Rythme convenu avec la famille</h2>
    <?php if ($scheduleEdit && $sched): ?><button type="button" class="btn ghost sm" data-edit><?= icon('pencil') ?> Modifier</button><?php endif ?></div>
  <div class="view">
    <?php if (!$sched): ?>
      <p class="muted"><?= $scheduleEdit ? 'Renseignez le rythme décidé avec le parent : les séances seront créées automatiquement.' : 'Le rythme des séances sera fixé avec la tutrice.' ?></p>
    <?php else: ?>
      <div class="sched-sum">
        <div class="sched-total"><strong><?= fdur(schedule_minutes_per_week($sched)) ?></strong><small>par semaine</small></div>
        <div class="sched-total"><strong><?= count($sched['slots']) ?></strong><small>séance<?= count($sched['slots']) > 1 ? 's' : '' ?> / semaine</small></div>
        <div class="sched-total"><strong><?= (int)$sched['weeks'] ?></strong><small>semaine<?= (int)$sched['weeks'] > 1 ? 's' : '' ?> à partir du <?= e(fdate($sched['start'])) ?></small></div>
      </div>
      <ul class="sched-slots"><?php foreach ($sched['slots'] as $x): ?>
        <li><span class="sched-day"><?= e(WEEKDAYS[$x['day']]) ?></span><span class="sched-hours"><?= e(str_replace(':', 'h', $x['time'])) ?> – <?= e(str_replace(':', 'h', slot_end($x))) ?></span><span class="sched-dur"><?= fdur((int)$x['duration']) ?></span><span class="badge <?= $x['mode'] === 'visio' ? '' : 'sage' ?>"><?= icon($x['mode'] === 'visio' ? 'video' : 'map-pin') ?> <?= e(MODES[$x['mode']]) ?></span></li>
      <?php endforeach ?></ul>
      <?php if (!empty($sched['note'])): ?><p class="sched-note"><?= icon('message-circle') ?> <?= e($sched['note']) ?></p><?php endif ?>
      <?php if (!empty($sched['agreed_at'])): ?><p class="muted"><small>Convenu le <?= e(fdate($sched['agreed_at'])) ?></small></p><?php endif ?>
    <?php endif ?>
  </div>
  <?php if ($scheduleEdit): ?>
  <form method="post" action="<?= e(url('t_schedule')) ?>" class="stack edit-form"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>">
    <span class="ql">Créneaux de la semaine</span>
    <div class="sched-rows" data-sched-rows>
      <?php foreach ($slotsForm as $x): ?>
      <div class="sched-row" data-sched-row>
        <label>Jour<select name="slot_day[]"><?php foreach (WEEKDAYS as $k => $l): ?><option value="<?= $k ?>" <?= (int)$x['day'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label>
        <label>Début<input type="time" name="slot_time[]" value="<?= e($x['time']) ?>" required></label>
        <label>Fin<input type="time" name="slot_end[]" value="<?= e(slot_end($x)) ?>" required></label>
        <span class="sched-row-dur" data-row-dur><?= fdur((int)$x['duration']) ?></span>
        <label>Modalité<select name="slot_mode[]"><?php foreach (MODES as $k => $l): ?><option value="<?= $k ?>" <?= $x['mode'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label>
        <button type="button" class="edit-btn" data-sched-del title="Retirer ce créneau" aria-label="Retirer ce créneau"><?= icon('trash-2') ?></button>
      </div>
      <?php endforeach ?>
    </div>
    <div class="row between"><button type="button" class="btn ghost sm" data-sched-add><?= icon('plus') ?> Ajouter un créneau</button><span class="muted sched-live" data-sched-total></span></div>
    <div class="form-grid">
      <label>À partir du<input type="date" name="start" required value="<?= e($sched['start'] ?? (new DateTime('next monday'))->format('Y-m-d')) ?>"></label>
      <label>Pendant<select name="weeks"><?php foreach ([1, 2, 4, 6, 8, 10, 12, 16, 20, 30, 36] as $w): ?><option value="<?= $w ?>" <?= (int)($sched['weeks'] ?? 4) === $w ? 'selected' : '' ?>><?= $w ?> semaine<?= $w > 1 ? 's' : '' ?></option><?php endforeach ?></select></label>
    </div>
    <label>Précisions (facultatif)<input name="note" maxlength="500" value="<?= e($sched['note'] ?? '') ?>" placeholder="ex. pas de séance pendant les vacances scolaires"></label>
    <fieldset class="sched-opts"><legend>Après l'enregistrement</legend>
      <label class="check"><input type="checkbox" name="generate" value="1" checked> Créer les séances correspondantes dans le planning</label>
      <label class="check"><input type="checkbox" name="replace" value="1" <?= $sched ? 'checked' : '' ?>> Remplacer les séances futures encore prévues</label>
      <label class="check"><input type="checkbox" name="with_goals" value="1"> Ajouter les objectifs suggérés par l'analyse</label>
      <label class="check"><input type="checkbox" name="notify" value="1" checked> Envoyer le rythme au parent (message dans « Échanges »)</label>
    </fieldset>
    <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer le rythme</button><?php if ($sched): ?><button type="button" class="btn ghost sm" data-edit>Annuler</button><?php endif ?></div>
  </form>
  <?php endif ?>
</div>
