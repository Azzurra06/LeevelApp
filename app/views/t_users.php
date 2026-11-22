<?php $title = 'Familles'; $old = $_SESSION['old'] ?? []; unset($_SESSION['old']); ?>
<div class="page-head"><div><h1><?= icon('users') ?> Familles et dossiers</h1><p>Chaque dossier relie un parent, un élève et vous.</p></div>
  <div class="row"><?php if (!array_filter($families, fn($f) => $f['is_demo'])): ?><form method="post" action="<?= e(url('t_demo')) ?>"><?= csrf_field() ?><button class="btn ghost"><?= icon('sparkles') ?> Créer le dossier démo</button></form><?php endif ?>
  <button class="btn" data-toggle="newfam"><?= icon('plus') ?> Nouveau dossier</button></div></div>
<div class="card" id="newfam" <?= $families && !$old ? 'hidden' : '' ?>>
  <h2>Créer un dossier d'accompagnement</h2>
  <form method="post" class="stack"><?= csrf_field() ?>
    <div class="form-grid">
      <label>Nom du dossier<input name="family_name" required maxlength="120" value="<?= e($old['family_name'] ?? '') ?>" placeholder="Famille Martin"></label>
      <label>Prénom de l'élève<input name="student_first_name" required maxlength="80" value="<?= e($old['student_first_name'] ?? '') ?>"></label>
      <label>Classe<?= grade_select('grade', $old['grade'] ?? '3e') ?></label>
      <label>Établissement (facultatif)<input name="school" maxlength="120"></label>
    </div>
    <?php $pm = $old['parent_mode'] ?? 'later'; $sm = $old['student_mode'] ?? 'later';
    $modeSwitch = function (string $who, string $cur): void { ?>
      <div class="seg seg-tight" role="radiogroup">
        <label class="seg-opt"><input type="radio" name="<?= $who ?>_mode" value="create" data-switch="<?= $who ?>" <?= $cur === 'create' ? 'checked' : '' ?>><span><?= icon('lock') ?> Créer le compte moi-même</span></label>
        <label class="seg-opt"><input type="radio" name="<?= $who ?>_mode" value="invite" data-switch="<?= $who ?>" <?= $cur === 'invite' ? 'checked' : '' ?>><span><?= icon('message-circle') ?> Inviter par e-mail</span></label>
        <label class="seg-opt"><input type="radio" name="<?= $who ?>_mode" value="later" data-switch="<?= $who ?>" <?= $cur === 'later' ? 'checked' : '' ?>><span><?= icon('clock') ?> Plus tard</span></label>
      </div>
    <?php }; ?>
    <fieldset><legend>Compte parent</legend>
      <?php $modeSwitch('parent', $pm) ?>
      <div data-switch-pane="parent:create" <?= $pm === 'create' ? '' : 'hidden' ?>>
        <div class="form-grid"><label>Nom affiché<input name="parent_name" required maxlength="100" value="<?= e($old['parent_name'] ?? '') ?>" placeholder="Mme Martin"></label></div>
        <div class="form-grid">
        <label>Identifiant<input name="parent_username" required pattern="[a-z0-9._\-]{3,60}" autocapitalize="none" value="<?= e($old['parent_username'] ?? '') ?>"></label>
        <label>Mot de passe initial<input name="parent_password" type="password" required minlength="10" autocomplete="new-password"></label></div></div>
      <div data-switch-pane="parent:invite" <?= $pm === 'invite' ? '' : 'hidden' ?>>
        <label>E-mail du parent</label>
        <div class="copy-row"><input type="email" name="parent_email" required maxlength="120" value="<?= e($old['parent_email'] ?? '') ?>" placeholder="prenom.nom@exemple.fr" aria-label="E-mail du parent"><button class="btn sm" name="quick_invite" value="parent" formnovalidate data-quick="parent_email"><?= icon('send') ?> Envoyer</button></div>
        <p class="muted"><small>Le parent recevra un lien (valable 7 jours) pour choisir son identifiant et son mot de passe, et valider l'autorisation de représentant légal.</small></p></div>
      <p class="muted later-note" data-switch-pane="parent:later" <?= $pm === 'later' ? '' : 'hidden' ?>><small>Le dossier est créé sans compte parent : vous pourrez préparer les tarifs, le rythme et le planning, puis inviter le parent quand vous le souhaitez depuis cette page.</small></p>
    </fieldset>
    <fieldset><legend>Compte élève</legend>
      <?php $modeSwitch('student', $sm) ?>
      <div class="form-grid" data-switch-pane="student:create" <?= $sm === 'create' ? '' : 'hidden' ?>>
        <label>Identifiant<input name="student_username" required pattern="[a-z0-9._\-]{3,60}" autocapitalize="none" value="<?= e($old['student_username'] ?? '') ?>"></label>
        <label>Mot de passe initial<input name="student_password" type="password" required minlength="10" autocomplete="new-password"></label></div>
      <div data-switch-pane="student:invite" <?= $sm === 'invite' ? '' : 'hidden' ?>>
        <label>E-mail de l'élève</label>
        <div class="copy-row"><input type="email" name="student_email" required maxlength="120" value="<?= e($old['student_email'] ?? '') ?>" aria-label="E-mail de l'élève"><button class="btn sm" name="quick_invite" value="student" formnovalidate data-quick="student_email"><?= icon('send') ?> Envoyer</button></div>
        <p class="muted"><small>L'élève créera son compte via le lien. Son espace s'ouvrira une fois l'autorisation du parent validée.</small></p></div>
      <p class="muted later-note" data-switch-pane="student:later" <?= $sm === 'later' ? '' : 'hidden' ?>><small>Le compte élève pourra être ajouté plus tard depuis cette page.</small></p>
    </fieldset>
    <p class="muted"><small>Mots de passe : 10 caractères minimum avec lettres et chiffres. Si l'e-mail ne part pas, le lien d'invitation s'affiche pour que vous puissiez l'envoyer vous-même (SMS, WhatsApp…).</small></p>
    <button class="btn"><?= icon('save') ?> Créer le dossier</button>
  </form>
</div><br>
<?php foreach ($families as $f): ?>
<details class="card fam"<?= (int)($_GET['open'] ?? 0) === (int)$f['id'] ? ' open' : '' ?>>
  <summary class="row between"><h2><?= icon('chevron-right', 'chev') ?> <?= e($f['name']) ?> <?php if ($f['is_demo']): ?><span class="badge demo">DÉMO - données fictives</span><?php endif ?></h2>
    <div class="row"><?php foreach ($f['students'] as $st): ?><a class="btn ghost sm" href="<?= e(url('t', ['sid' => $st['id']])) ?>"><?= icon('user') ?> <?= e($st['first_name']) ?></a><a class="btn ghost sm" href="<?= e(url('t_export', ['sid' => $st['id']])) ?>"><?= icon('download') ?> Export</a><?php endforeach ?></div></summary>
  <p class="muted"><small>Autorisation du représentant légal : <?= $f['guardian_consent_at'] ? 'validée le ' . e(fdate($f['guardian_consent_at'])) : 'en attente' ?></small></p>
  <div class="table-wrap"><table><tr><th>Nom</th><th>Identifiant</th><th>Rôle</th><th>Dernière connexion</th><th>Actions</th></tr>
  <?php foreach ($f['users'] as $x): ?><tr><td><?= e($x['display_name']) ?></td><td><code><?= e($x['username']) ?></code></td><td><?= e(ROLE_LABELS[$x['role']]) ?><?= $x['is_active'] ? '' : ' <span class="badge err">désactivé</span>' ?></td><td><?= e(fdate($x['last_login_at'], 'd/m/Y H:i')) ?></td>
    <td><?php if ($x['role'] !== 'tutor'): ?><form method="post" action="<?= e(url('t_user_update')) ?>" class="row"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>">
      <input type="password" name="password" placeholder="Nouveau mot de passe" minlength="10" autocomplete="new-password" aria-label="Nouveau mot de passe"><button class="btn soft sm">Définir</button>
      <button class="btn ghost sm" name="toggle_active" value="1"><?= $x['is_active'] ? 'Désactiver' : 'Activer' ?></button></form><?php endif ?></td></tr><?php endforeach ?>
  </table></div>
  <?php foreach ($f['students'] as $st):
    $hasParent = (bool)array_filter($f['users'], fn($x) => $x['role'] === 'parent') || (bool)array_filter($f['invites'], fn($x) => $x['role'] === 'parent' && strtotime($x['expires_at']) > time());
    $hasStudent = $st['user_id'] || array_filter($f['invites'], fn($x) => $x['role'] === 'student' && strtotime($x['expires_at']) > time());
    foreach (['parent' => !$hasParent, 'student' => !$hasStudent] as $role => $missing): if (!$missing) continue; ?>
    <?php $sw = 'm' . (int)$st['id'] . $role; ?>
    <form method="post" action="<?= e(url('t_invite_new')) ?>" class="missing-acc"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$st['id'] ?>"><input type="hidden" name="role" value="<?= $role ?>">
      <span class="missing-h"><?= icon('user') ?> Compte <?= $role === 'parent' ? 'parent' : 'élève' ?> à créer</span>
      <div class="seg seg-tight" role="radiogroup">
        <label class="seg-opt"><input type="radio" name="mode" value="invite" data-switch="<?= $sw ?>" checked><span><?= icon('send') ?> Inviter par e-mail</span></label>
        <label class="seg-opt"><input type="radio" name="mode" value="link" data-switch="<?= $sw ?>"><span><?= icon('external-link') ?> Lien à copier</span></label>
        <label class="seg-opt"><input type="radio" name="mode" value="create" data-switch="<?= $sw ?>"><span><?= icon('lock') ?> Créer moi-même</span></label>
      </div>
      <div data-switch-pane="<?= $sw ?>:invite"><div class="copy-row"><input type="email" name="email" required maxlength="120" placeholder="adresse@exemple.fr" aria-label="E-mail"><button class="btn sm"><?= icon('send') ?> Envoyer</button></div>
        <p class="muted"><small>Un e-mail avec le lien d'inscription est envoyé (valable 7 jours).</small></p></div>
      <div data-switch-pane="<?= $sw ?>:link" hidden><input type="hidden" name="link_only" value="1" disabled>
        <div class="row"><button class="btn sm"><?= icon('external-link') ?> Générer le lien d'invitation</button></div>
        <p class="muted"><small>Le lien s'affiche en haut de la page : copiez-le et transmettez-le vous-même (SMS, WhatsApp…).</small></p></div>
      <div data-switch-pane="<?= $sw ?>:create" hidden>
        <div class="form-grid"><?php if ($role === 'parent'): ?><label>Nom affiché<input name="display_name" required maxlength="100" placeholder="Mme Martin"></label><?php endif ?>
          <label>Identifiant<input name="username" required pattern="[a-z0-9._\-]{3,60}" autocapitalize="none" autocomplete="off"></label>
          <label>Mot de passe initial<input name="password" type="password" required minlength="10" autocomplete="new-password"></label></div>
        <div class="row"><button class="btn sm"><?= icon('save') ?> Créer le compte</button></div>
        <p class="muted"><small>10 caractères minimum avec lettres et chiffres. Communiquez ensuite l'identifiant et le mot de passe à la famille.</small></p></div>
    </form>
  <?php endforeach; endforeach ?>
  <?php if ($f['invites']): ?><h3 class="sub-h"><?= icon('message-circle') ?> Invitations en attente</h3>
  <ul class="list"><?php foreach ($f['invites'] as $iv): $expired = strtotime($iv['expires_at']) < time(); ?>
    <li><?= icon($iv['role'] === 'student' ? 'smile' : 'user') ?><div><?= e($iv['display_name'] ?: ROLE_LABELS[$iv['role']]) ?><?= $iv['display_name'] ? ' · ' . e(ROLE_LABELS[$iv['role']]) : '' ?><?= $iv['email'] !== '' ? ' · <code>' . e($iv['email']) . '</code>' : ' · <span class="muted">lien transmis par vous</span>' ?><br>
      <small class="muted"><?= $expired ? 'Lien expiré' : 'Valable jusqu\'au ' . e(fdate($iv['expires_at'])) ?> · <?= $iv['mail_sent'] ? 'e-mail envoyé' : 'e-mail non envoyé (lien à transmettre)' ?></small></div>
      <form method="post" action="<?= e(url('t_invite')) ?>" class="row inv-actions"><?= csrf_field() ?><input type="hidden" name="invite_id" value="<?= (int)$iv['id'] ?>">
        <button class="btn soft sm" name="do" value="resend"><?= icon('refresh-cw') ?> Nouveau lien</button>
        <button class="btn ghost sm" name="do" value="cancel" data-confirm="Annuler cette invitation ?"><?= icon('x') ?></button></form></li>
  <?php endforeach ?></ul><?php endif ?>
  <?php foreach ($f['students'] as $st): ?>
  <details class="del-family"><summary><?= icon('trash-2') ?> Supprimer définitivement ce dossier</summary>
    <form method="post" action="<?= e(url('t_delete_family')) ?>" class="del-family-form" data-confirm="Suppression définitive et irréversible de toutes les données de ce dossier. Continuer ?"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$st['id'] ?>">
      <label>Tapez SUPPRIMER pour confirmer<input name="confirm" required pattern="SUPPRIMER" autocomplete="off" placeholder="SUPPRIMER"></label><button class="btn danger"><?= icon('trash-2') ?> Supprimer</button></form>
    <p class="muted"><small>Exportez les données avant si nécessaire. Les comptes parent et élève sont aussi supprimés.</small></p></details>
  <?php endforeach ?>
</details><br>
<?php endforeach ?>
