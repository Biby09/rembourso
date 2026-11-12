<?php
/* Assistant de configuration, étape 3 : invitation des membres (inclus par dashboard/organisation/init-orga.php). */
?>
<h1 class="fs-3">Inviter vos membres</h1>
<p>Invitez les personnes qui ont déjà un compte Rembourso, avec leur adresse e-mail. Choisissez leur rôle :</p>
<ul>
    <li><strong>Membre</strong> : dépose ses demandes de remboursement et suit leur traitement ;</li>
    <li><strong>Caissier</strong> : traite et rembourse aussi les demandes de tous les membres ;</li>
    <li><strong>Administrateur</strong> : gère aussi les membres et les paramètres de l'organisation.</li>
</ul>
<p class="text-muted">Une personne sans compte ? Un caissier pourra saisir ses demandes pour elle, puis lui envoyer
    un lien pour créer son compte (page « À rembourser »).</p>

<h2 class="fs-5 mt-4">Invitations envoyées</h2>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/invitation-setup.php'; ?>
