<?php
/* Assistant de configuration, étape 1 : bienvenue et devise (inclus par dashboard/organisation/init-orga.php). */
?>
<h1 class="fs-3">Bienvenue dans votre organisation</h1>
<p class="lead">« <?= e($organisation->getName()) ?> » est créée. Quelques étapes vont vous permettre de la configurer ;
    vous pourrez tout modifier plus tard dans les paramètres de l'organisation.</p>

<p>Avec Rembourso, vos membres déposent leurs demandes de remboursement avec leurs quittances,
    et les caissiers les traitent par lots, avec une QR-facture prête à payer.</p>

<h2 class="fs-5 mt-4">Devise</h2>
<p class="text-muted">Dans quelle devise vos membres seront-ils remboursés ?</p>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/currency-setup.php'; ?>
