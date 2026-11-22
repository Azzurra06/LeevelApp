<?php $title = 'Mon compte'; $full = one('SELECT * FROM users WHERE id=?', [$u['id']]);
$isTutor = $u['role'] === 'tutor'; $isParent = $u['role'] === 'parent'; $isStudent = $u['role'] === 'student';
$discounts = json_decode((string)($full['discounts_json'] ?? ''), true) ?: [];
$dTypes = ['children' => 'Plusieurs enfants', 'pack' => "Pack d'heures", 'other' => 'Autre'];
$money = fn($v) => $v !== null && $v !== '' ? rtrim(rtrim(number_format((float)$v, 2, ',', ' '), '0'), ',') : '';
$val = fn($v) => ($v ?? '') !== '' ? e($v) : '<span class="muted">Non renseigné</span>';
$tel = fn($v) => ($v ?? '') !== '' ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $v)) . '">' . e($v) . '</a>' : $val(null);
$mail = fn($v) => ($v ?? '') !== '' ? '<a href="mailto:' . e($v) . '">' . e($v) . '</a>' : $val(null);
$editBtn = fn(string $label = 'Modifier') => '<button type="button" class="btn ghost sm" data-edit>' . icon('pencil') . ' ' . e($label) . '</button>';
// Élève : autorisations accordées par le parent
$me = $isStudent ? one('SELECT * FROM students WHERE user_id=?', [$u['id']]) : null;
$canContacts = !$isStudent || (int)($me['perm_edit_contacts'] ?? 1);
$canPhoto = !$isStudent || (int)($me['perm_edit_photo'] ?? 1);
$children = $isParent ? students_of($u) : [];
// Élève : profil partagé avec son questionnaire (mêmes réponses)
$pa = [];
if ($me) { $ssub = qn_latest('student', (int)$me['id']); $pa = student_profile_defaults($me, $ssub ? qn_answers((int)$ssub['id']) : []); }
$pv = fn(string $k) => isset($pa[$k]) && $pa[$k] !== SKIP && $pa[$k] !== '' ? $pa[$k] : null;
$pills = fn(string $name, array $opts, $cur) => '<div class="pills">' . implode('', array_map(fn($k, $l) => '<label class="pill"><input type="checkbox" name="' . $name . '[]" value="' . e($k) . '"' . (in_array($k, (array)$cur, true) ? ' checked' : '') . '><span>' . e($l) . '</span></label>', array_keys($opts), $opts)) . '</div>'; ?>
<div class="page-head"><div><h1><?= icon('settings') ?> Mon compte</h1><p><?= e(ROLE_LABELS[$u['role']]) ?> · identifiant <code><?= e($u['username']) ?></code></p></div></div>

<div class="grid-2 align-start">
    <div class="card">
      <h2><?= icon('user') ?> Photo de profil</h2>
      <div class="avatar-edit">
        <?= avatar_html($full, 'lg') ?>
        <?php if ($canPhoto): ?>
        <div class="stack">
          <form method="post" enctype="multipart/form-data" class="row" data-avatar-form><?= csrf_field() ?><input type="hidden" name="action" value="avatar">
            <label class="btn soft sm file-btn"><?= icon('download') ?> Choisir une photo<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required data-crop-input></label>
          </form>
          <?php if (!empty($full['avatar'])): ?><form method="post" data-confirm="Supprimer la photo ?"><?= csrf_field() ?><input type="hidden" name="action" value="avatar_remove"><button class="btn ghost sm"><?= icon('trash-2') ?> Supprimer</button></form><?php endif ?>
          <p class="muted"><small>JPEG, PNG ou WebP, 4 Mo maximum. Vous pourrez recadrer la photo avant l'envoi.</small></p>
        </div>
        <?php else: ?><p class="muted"><small>C'est ton parent qui gère ta photo de profil.</small></p><?php endif ?>
      </div>
      <?php if ($canPhoto): ?>
      <div class="cropper" data-cropper hidden>
        <div class="crop-stage" data-crop-stage><canvas width="320" height="320" data-crop-canvas></canvas><div class="crop-mask"></div></div>
        <label class="crop-zoom"><?= icon('eye') ?> Zoom<input type="range" min="1" max="4" step="0.01" value="1" data-crop-zoom></label>
        <p class="muted"><small>Faites glisser l'image pour la placer dans le cercle.</small></p>
        <div class="row"><button type="button" class="btn sm" data-crop-save><?= icon('save') ?> Enregistrer la photo</button><button type="button" class="btn ghost sm" data-crop-cancel>Annuler</button></div>
      </div>
      <?php endif ?>
    </div>

    <div class="card editable">
      <div class="ed-head"><h2><?= icon('message-circle') ?> Mes coordonnées</h2><?= $canContacts ? $editBtn() : '' ?></div>
      <dl class="fields view">
        <div class="wide"><dt>Nom affiché</dt><dd><?= $val($full['display_name']) ?></dd></div>
        <div><dt>Téléphone</dt><dd><?= $tel($full['phone'] ?? null) ?></dd></div>
        <div><dt>E-mail</dt><dd><?= $mail($full['email'] ?? null) ?></dd></div>
        <?php if (!$isStudent): ?><div class="wide"><dt>Adresse postale</dt><dd><?= $val($full['address'] ?? null) ?></dd></div>
        <div class="wide"><dt>Notifications</dt><dd><?= (int)($full['notify_messages'] ?? 1) ? 'E-mail si un message reste non lu plus de 30 min' : 'Désactivées' ?></dd></div><?php endif ?>
      </dl>
      <?php if (!$canContacts): ?><p class="muted"><small>C'est ton parent qui gère tes coordonnées.</small></p><?php endif ?>
      <?php if ($canContacts): ?>
      <form method="post" class="stack edit-form"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
        <label>Nom affiché<input name="display_name" required maxlength="100" value="<?= e($full['display_name']) ?>"></label>
        <div class="form-grid"><label>Téléphone<input type="tel" name="phone" maxlength="30" value="<?= e($full['phone'] ?? '') ?>" autocomplete="tel"></label>
          <label>E-mail<input type="email" name="email" maxlength="120" value="<?= e($full['email'] ?? '') ?>" autocomplete="email"></label></div>
        <?php if (!$isStudent): ?>
          <label>Adresse postale<input name="address" maxlength="255" value="<?= e($full['address'] ?? '') ?>" autocomplete="street-address" placeholder="N°, rue, code postal, ville"></label>
          <label class="check"><input type="checkbox" name="notify_messages" value="1" <?= (int)($full['notify_messages'] ?? 1) ? 'checked' : '' ?>> Me prévenir par e-mail des messages non lus</label>
          <p class="muted"><small>Un seul e-mail regroupé, si un message reste non lu plus de 30 minutes, au maximum un toutes les 2 heures.</small></p>
        <?php endif ?>
        <p class="muted"><small><?= $isTutor ? 'Téléphone et e-mail sont affichés aux familles dans « Messagerie ».' : 'Ces coordonnées sont visibles uniquement par votre tutrice.' ?></small></p>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
      </form>
      <?php endif ?>
    </div>

    <div class="card editable">
      <div class="ed-head"><h2><?= icon('lock') ?> Identifiants de connexion</h2><?= $editBtn('Modifier mon mot de passe') ?></div>
      <dl class="fields view">
        <div><dt>Identifiant</dt><dd><code><?= e($u['username']) ?></code></dd></div>
        <div><dt>Mot de passe</dt><dd>•••••••••• <span class="muted"><small>(jamais affiché)</small></span></dd></div>
      </dl>
      <form method="post" class="stack edit-form"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <p class="muted"><small>Identifiant : <code><?= e($u['username']) ?></code></small></p>
        <div class="form-grid">
          <label>Mot de passe actuel<input type="password" name="current_password" required autocomplete="current-password"></label>
          <span></span>
          <label>Nouveau mot de passe<input type="password" name="new_password" required minlength="10" autocomplete="new-password"></label>
          <label>Confirmer<input type="password" name="new_password2" required minlength="10" autocomplete="new-password"></label>
        </div>
        <p class="muted"><small>10 caractères minimum, avec des lettres et des chiffres.</small></p>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Changer le mot de passe</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
      </form>
    </div>

    <?php foreach ($children as $ch): ?>
    <a class="card link-card tone-violet" href="<?= e(url('p_child', ['sid' => $ch['id']])) ?>"><span class="lc-emoji">🧒</span><div><h2>Informations de <?= e(kid($ch['first_name'])) ?></h2><small>Identité, coordonnées, besoins particuliers et autorisations : tout est dans « Mon enfant ».</small></div><?= icon('chevron-right', 'go') ?></a>
    <?php endforeach ?>

    <?php if ($me): ?>
    <div class="card editable span-2">
      <div class="ed-head"><h2><?= icon('sparkles') ?> Mon profil</h2><?= $editBtn() ?></div>
      <dl class="fields view">
        <div><dt>Prénom</dt><dd><?= $val($pv('s_first_name')) ?></dd></div>
        <div><dt>Nom</dt><dd><?= $val($pv('s_last_name')) ?></dd></div>
        <div><dt>Âge</dt><dd><?= $pv('s_age') ? e($pv('s_age')) . ' ans' : $val(null) ?></dd></div>
        <div><dt>Classe</dt><dd><?= $pv('s_class') ? e(CLASSES[$pv('s_class')] ?? $pv('s_class')) : $val(null) ?></dd></div>
        <div class="wide"><dt>Établissement</dt><dd><?= $val($pv('s_school')) ?></dd></div>
        <div class="wide"><dt>Centres d'intérêt</dt><dd><?= $pv('s_interests') ? '<div class="badges">' . implode('', array_map(fn($k) => '<span class="badge sage">' . e(STUDENT_INTERESTS[$k] ?? $k) . '</span>', (array)$pv('s_interests'))) . '</div>' : $val(null) ?></dd></div>
        <div class="wide"><dt>Pour retenir, je préfère…</dt><dd><?= $pv('s_memory') ? '<div class="badges">' . implode('', array_map(fn($k) => '<span class="badge">' . e(MEMORY_TYPES[$k] ?? $k) . '</span>', (array)$pv('s_memory'))) . '</div>' : $val(null) ?></dd></div>
      </dl>
      <p class="muted view"><small>Ces informations viennent aussi de ton questionnaire : si tu les changes ici, elles changent là-bas.</small></p>
      <form method="post" class="stack edit-form"><?= csrf_field() ?><input type="hidden" name="action" value="student_profile">
        <div class="form-grid">
          <label>Prénom<input name="s_first_name" maxlength="80" value="<?= e($pv('s_first_name') ?? '') ?>"></label>
          <label>Nom<input name="s_last_name" maxlength="80" value="<?= e($pv('s_last_name') ?? '') ?>"></label>
          <label>Âge<select name="s_age"><option value="">Choisir…</option><?php for ($i = 6; $i <= 19; $i++): ?><option value="<?= $i ?>" <?= (string)$pv('s_age') === (string)$i ? 'selected' : '' ?>><?= $i ?> ans</option><?php endfor ?></select></label>
          <label>Classe<select name="s_class"><option value="">Choisir…</option><?php foreach (CLASSES as $k => $l): ?><option value="<?= e($k) ?>" <?= $pv('s_class') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></label>
        </div>
        <label>Établissement<input name="s_school" maxlength="120" value="<?= e($pv('s_school') ?? '') ?>"></label>
        <div><span class="ql">Centres d'intérêt</span><?= $pills('s_interests', STUDENT_INTERESTS, $pv('s_interests')) ?></div>
        <div><span class="ql">Pour retenir, ce qui marche le mieux pour moi</span><?= $pills('s_memory', MEMORY_TYPES, $pv('s_memory')) ?></div>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
      </form>
    </div>
    <?php endif ?>

    <?php if ($isTutor): $aiKey = ai_api_key(); $aiFromConfig = (string)(config('anthropic_api_key') ?? '') !== ''; ?>
    <div class="card editable span-2">
      <div class="ed-head"><h2><?= icon('sparkles') ?> Intelligence artificielle</h2><?= $aiFromConfig ? '' : $editBtn($aiKey ? 'Changer la clé' : 'Ajouter une clé') ?></div>
      <div class="view">
        <p><?= $aiKey ? '<span class="badge ok">Activée</span> Clé enregistrée (se termine par <code>…' . e(substr($aiKey, -4)) . '</code>)' . ($aiFromConfig ? ', définie dans le fichier de configuration du serveur.' : '.') : '<span class="badge">Non configurée</span>' ?></p>
        <?php $aiWs = ai_workspace_id(); ?>
        <p class="muted"><small>Espace de travail : <?= $aiWs ? '<code>' . e($aiWs) . '</code>' : 'non indiqué (nécessaire seulement si la clé n\'est rattachée à aucun espace de travail)' ?></small></p>
        <p class="muted"><small>Sert à générer les quiz de révision (Fiche élève › Matières). Seuls la matière, le titre du chapitre et la classe sont envoyés au service d'IA (Anthropic), jamais le nom de l'élève ni ses réponses.</small></p>
      </div>
      <?php if (!$aiFromConfig): ?>
      <form method="post" class="stack edit-form"><?= csrf_field() ?><input type="hidden" name="action" value="ai_key">
        <label>Clé API Anthropic<input type="password" name="ai_key" autocomplete="off" spellcheck="false" placeholder="sk-ant-…"></label>
        <p class="muted"><small>À créer sur console.anthropic.com (rubrique API Keys). Elle est conservée sur le serveur, jamais affichée en entier.</small></p>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer</button><?php if ($aiKey): ?><button class="btn ghost sm" name="remove" value="1" formnovalidate data-confirm="Supprimer la clé API ?"><?= icon('trash-2') ?> Supprimer la clé</button><?php endif ?><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
      </form>
      <form method="post" class="stack edit-form ws-form"><?= csrf_field() ?><input type="hidden" name="action" value="ai_key"><input type="hidden" name="only" value="workspace">
        <label>Identifiant de l'espace de travail (facultatif)<input name="workspace_id" value="<?= e(ai_workspace_id() ?? '') ?>" autocomplete="off" spellcheck="false" placeholder="wrkspc_…"></label>
        <p class="muted"><small>Seulement si l'application vous le demande. Sur console.anthropic.com : Settings › Workspaces, puis ouvrez l'espace de travail pour copier son identifiant.</small></p>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer l'espace de travail</button></div>
      </form>
      <?php endif ?>
    </div>

    <div class="card editable span-2">
      <div class="ed-head"><h2><?= icon('target') ?> Tarifs</h2><?= $editBtn() ?></div>
      <div class="view">
        <dl class="fields">
          <div><dt>Présentiel</dt><dd><?= $full['rate_presentiel'] !== null ? e($money($full['rate_presentiel'])) . ' € / h' : $val(null) ?></dd></div>
          <div><dt>Visio</dt><dd><?= $full['rate_visio'] !== null ? e($money($full['rate_visio'])) . ' € / h' : $val(null) ?></dd></div>
        </dl>
        <h3 class="sub-h"><?= icon('sparkles') ?> Tarifs dégressifs et remises</h3>
        <?php if (!$discounts): ?><p class="muted">Aucune remise renseignée.</p><?php endif ?>
        <ul class="list"><?php foreach ($discounts as $d): ?><li><?= icon('sparkles') ?><div><span class="badge"><?= e($dTypes[$d['type']] ?? 'Autre') ?></span> <?= e($d['condition']) ?> → <strong><?= e($d['value']) ?></strong></div></li><?php endforeach ?></ul>
      </div>
      <form method="post" class="stack edit-form"><?= csrf_field() ?><input type="hidden" name="action" value="rates">
        <div class="form-grid">
          <label>Présentiel (€ / heure)<input name="rate_presentiel" inputmode="decimal" maxlength="10" value="<?= e($money($full['rate_presentiel'] ?? null)) ?>" placeholder="ex. 30"></label>
          <label>Visio (€ / heure)<input name="rate_visio" inputmode="decimal" maxlength="10" value="<?= e($money($full['rate_visio'] ?? null)) ?>" placeholder="ex. 25"></label>
        </div>
        <h3 class="sub-h"><?= icon('sparkles') ?> Tarifs dégressifs et remises</h3>
        <p class="muted"><small>Facultatif. Ex. : « 2e enfant » → « -10 % » ; « Pack de 10 h » → « 270 € ».</small></p>
        <div class="discounts" data-discounts>
          <?php foreach ($discounts ?: [['type' => 'children', 'condition' => '', 'value' => '']] as $d): ?>
            <div class="discount-row" data-discount-row>
              <label>Type<select name="d_type[]"><?php foreach ($dTypes as $k => $l): ?><option value="<?= $k ?>" <?= ($d['type'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></label>
              <label>Condition<input name="d_cond[]" maxlength="100" value="<?= e($d['condition'] ?? '') ?>" placeholder="ex. 2e enfant, pack de 10 h"></label>
              <label>Remise / prix<input name="d_value[]" maxlength="60" value="<?= e($d['value'] ?? '') ?>" placeholder="ex. -10 %, 270 €"></label>
              <button type="button" class="edit-btn" data-discount-del title="Supprimer cette ligne" aria-label="Supprimer cette ligne"><?= icon('trash-2') ?></button>
            </div>
          <?php endforeach ?>
        </div>
        <button type="button" class="btn ghost sm" data-discount-add><?= icon('plus') ?> Ajouter une remise</button>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Enregistrer les tarifs</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
      </form>
    </div>
    <?php endif ?>


    <?php $scope = deletion_scope($u); ?>
    <div class="card span-2 danger-card editable" id="suppression">
      <div class="ed-head"><h2><?= icon('trash-2') ?> Supprimer mon compte</h2><button type="button" class="btn ghost sm danger" data-edit>Supprimer mon compte…</button></div>
      <p class="muted view">Supprime définitivement votre compte et vos données personnelles. Cette action est irréversible.</p>
      <form method="post" class="stack edit-form" data-confirm="Supprimer définitivement votre compte ? Cette action est irréversible."><?= csrf_field() ?><input type="hidden" name="action" value="delete_account">
        <div class="alert warn"><?= icon('triangle-alert') ?> <div><strong>Ce qui sera supprimé définitivement :</strong>
          <ul class="del-list">
          <?php if ($u['role'] === 'student'): ?>
            <li>ton compte de connexion et ta photo ;</li><li>tes réponses au questionnaire, tes messages, ton journal, tes retours de séance et tes fichiers déposés.</li>
            <li class="muted">Ton dossier de suivi (séances, bilans) reste géré par ton parent et ta tutrice.</li>
          <?php elseif ($u['role'] === 'parent'): ?>
            <li>votre compte, votre photo, vos messages et vos réponses au questionnaire ;</li>
            <?php foreach ($scope['families'] as $f): ?><li><?= (int)$f['others'] === 0 ? 'le dossier « ' . e($f['name']) . ' » en entier (enfants, comptes élèves, séances, bilans, fichiers), car vous en êtes le seul responsable' : 'le dossier « ' . e($f['name']) . ' » est conservé pour l\'autre parent, sauf si vous cochez la case ci-dessous' ?></li><?php endforeach ?>
          <?php else: ?>
            <li>votre compte, votre photo et vos messages.</li>
            <?php if ($scope['solo']): ?><li>Vous êtes la seule tutrice : cochez la case ci-dessous pour supprimer aussi toutes les familles et leurs données.</li><?php endif ?>
          <?php endif ?>
          </ul></div></div>
        <?php if (($u['role'] === 'parent' && array_filter($scope['families'], fn($f) => (int)$f['others'] > 0)) || ($u['role'] === 'tutor' && $scope['solo'])): ?>
          <label class="check"><input type="checkbox" name="with_families" value="1"> <?= $u['role'] === 'tutor' ? 'Supprimer aussi toutes les familles, élèves et leurs données' : 'Supprimer aussi les dossiers partagés avec l\'autre parent' ?></label>
        <?php endif ?>
        <p class="muted"><small>Pensez à exporter ce que vous souhaitez garder avant de supprimer.</small></p>
        <div class="form-grid">
          <label>Mot de passe<input type="password" name="current_password" required autocomplete="current-password"></label>
          <label>Tapez SUPPRIMER pour confirmer<input name="confirm" required pattern="SUPPRIMER" autocomplete="off" placeholder="SUPPRIMER"></label>
        </div>
        <div class="row"><button class="btn danger sm"><?= icon('trash-2') ?> Supprimer définitivement</button><button type="button" class="btn ghost sm" data-edit>Annuler</button></div>
      </form>
    </div>
</div>
