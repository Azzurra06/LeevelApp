<?php $title = "Centre d'aide"; $role = $u['role'];
$msgRoute = ['tutor' => 't_messages', 'parent' => 'p_messages', 'student' => 's_messages'][$role];
$faq = [
    'student' => [
        ['book-open', 'Où voir mes notes et mes chapitres ?', "Dans l'onglet « Matières » : choisis une matière en haut de la page pour voir ta moyenne, l'évolution de tes notes et le niveau de chaque chapitre."],
        ['plus', 'Comment ajouter une note ?', "Dans « Matières », carte « Mes notes », touche « Ajouter une note ». Tu peux supprimer une note que tu as ajoutée toi-même."],
        ['brain', 'Comment faire le quiz du chapitre ?', "Dans « Matières », carte « Je teste mes connaissances ». Réponds à toutes les questions puis valide : tu vois tout de suite tes erreurs et la bonne réponse. Tu peux recommencer autant que tu veux."],
        ['download', "Comment envoyer la photo d'un exercice ?", "Dans « Matières », carte « Mes fichiers » : touche « Envoyer une photo ou un PDF », puis prends la photo ou choisis-la dans ta galerie. Ta tutrice la verra."],
        ['circle-help', 'Je bloque sur quelque chose, je fais quoi ?', "Utilise « Là où je bloque » dans « Matières », ou écris directement à ta tutrice dans « Messages ». Pour une question rapide, l'onglet « Messages » est fait pour ça."],
        ['eye', 'Qui voit quoi ?', "Tes réponses au questionnaire ne sont vues que par ta tutrice. Tes messages avec ta tutrice peuvent être lus par ton parent. Tes notes et ton ressenti de séance sont visibles par ta tutrice et ton parent."],
    ],
    'parent' => [
        ['file-text', 'Où lire les bilans de séance ?', "Sur la page « Suivi » : la tutrice y publie un court bilan après chaque séance, avec le ressenti de votre enfant. Vous le recevez aussi dans « Échanges »."],
        ['eye', 'Puis-je lire les messages de mon enfant avec la tutrice ?', "Oui, dans « Échanges », onglet portant le prénom de votre enfant. Cette discussion est en lecture seule pour vous, et votre enfant sait que vous pouvez la lire."],
        ['flag', 'Comment signaler un contrôle à venir ?', "Sur la page « Suivi », carte « Contrôles à venir » : « Ajouter un contrôle ». La tutrice en tiendra compte pour préparer les séances."],
        ['shield', 'Comment gérer les autorisations de mon enfant ?', "Dans « Mon compte », carte « Autorisations » : vous choisissez s'il peut modifier ses coordonnées, sa photo, ou utiliser le bouton d'aide."],
        ['calendar', 'Où voir les prochaines séances ?', "Dans « Planning », en liste ou en calendrier. Pour une absence, prévenez la tutrice par message ou par téléphone."],
    ],
    'tutor' => [
        ['book-open', 'Ajouter des matières, chapitres et quiz', "Fiche élève > onglet « Matières » : ajoutez une matière, puis ses chapitres avec leur niveau. Cochez « En cours » sur le chapitre du moment : c'est lui que l'élève peut réviser avec le quiz. Les questions s'ajoutent dans le volet « Quiz » de chaque chapitre."],
        ['file-text', 'Rédiger et envoyer un bilan de séance', "Fiche élève > onglet « Séances » : après une séance, rédigez le bilan. « Envoyer » le publie dans le suivi du parent et dans « Échanges »."],
        ['message-circle', 'Deux discussions par famille', "Dans « Échanges », chaque famille a deux fils : « Parent » et celui de l'élève. Le parent peut lire la discussion de l'élève, sans y écrire."],
        ['lock', 'Faire passer un questionnaire sur un seul appareil', "Écran « Premier rendez-vous » : lancez la passation. Votre session se ferme, seul le questionnaire est accessible, puis vous vous reconnectez à la fin."],
        ['users', 'Inviter un parent ou un élève', "Page « Familles » : créez les comptes vous-même ou envoyez un lien d'invitation par e-mail."],
    ],
][$role];
$common = [
    ['lock', 'Changer mon mot de passe', "Menu « Mon compte » > carte « Identifiants de connexion » > « Modifier mon mot de passe »."],
    ['house', "Installer l'application sur mon téléphone", "Sur iPhone (Safari) : bouton Partager puis « Sur l'écran d'accueil ». Sur Android (Chrome) : menu ⋮ puis « Installer l'application ». Leevel s'ouvre alors en plein écran, comme une application."],
    ['shield', 'Mes données sont-elles protégées ?', "Les données restent sur le serveur de l'application et ne sont transmises à aucun tiers. Les fichiers déposés ne sont visibles que par la famille et la tutrice."],
]; ?>
<div class="page-head"><div><h1><?= icon('circle-help') ?> Centre d'aide</h1><p>Les réponses aux questions les plus fréquentes.</p></div></div>
<div class="grid-2">
  <div class="card">
    <h2><?= icon('lightbulb') ?> <?= $role === 'student' ? 'Utiliser ton espace' : ($role === 'parent' ? 'Suivre votre enfant' : 'Accompagner vos élèves') ?></h2>
    <div class="faq"><?php foreach ($faq as [$ic, $q, $a]): ?><details><summary><?= icon($ic) ?> <?= e($q) ?></summary><p><?= e($a) ?></p></details><?php endforeach ?></div>
  </div>
  <div class="card">
    <h2><?= icon('settings') ?> Compte et application</h2>
    <div class="faq"><?php foreach ($common as [$ic, $q, $a]): ?><details><summary><?= icon($ic) ?> <?= e($q) ?></summary><p><?= e($a) ?></p></details><?php endforeach ?></div>
  </div>
  <?php if ($role !== 'tutor'): ?>
  <div class="card tone-violet help-contact">
    <h2><?= icon('message-circle') ?> Tu n'as pas trouvé ?</h2>
    <p><?= $role === 'student' ? 'Écris directement à ta tutrice, elle te répondra.' : 'Écrivez directement à la tutrice depuis la messagerie.' ?></p>
    <a class="btn" href="<?= e(url($msgRoute)) ?>"><?= icon('send') ?> <?= $role === 'student' ? 'Écrire à ma tutrice' : 'Écrire à la tutrice' ?></a>
  </div>
  <?php endif ?>
</div>
