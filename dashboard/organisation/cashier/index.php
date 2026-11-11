<?php
$requiredRole = 'cashier';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/organisation-page.php';

/*
    « À rembourser » (/dashboard/organisation/cashier/?id=…), caissiers et administrateurs :
    - membres ayant des demandes en attente : acceptation / refus, QR-facture puis validation du paiement
      (scripts/pages/organisation/cashier.js) ;
    - personnes sans compte : modification et envoi du lien de création de compte (scripts/pages/organisation/accountless.js).
*/
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'À rembourser | ' . $organisation->getName();
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4" id="organisation-page" data-organisation-id="<?= $organisation->getId() ?>" data-organisation-name="<?= e($organisation->getName()) ?>">
        <?php $heading = 'À rembourser'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/page-heading.php'; ?>

        <div class="card mb-5">
            <div class="card-body text-center">
                <!-- Rempli par scripts/pages/organisation/cashier.js -->
                <div id="cashier-table-container"></div>
            </div>
        </div>

        <section>
            <h2 class="fs-4">Personnes sans compte</h2>
            <p class="text-muted">Personnes pour lesquelles une demande a été saisie par un caissier.
                Envoyez-leur un lien pour qu'elles créent leur compte et suivent leurs remboursements.</p>
            <!-- Rempli par scripts/pages/organisation/accountless.js -->
            <div id="accountless-container"></div>
        </section>
    </main>

    <!-- Le .modal-dialog est injecté par scripts/pages/organisation/cashier.js -->
    <div class="modal fade" id="cashier-tab-dialog-container" tabindex="-1" aria-hidden="true"></div>
    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/accountless-modal.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php';
    ?>
    <script type="module" src="/scripts/pages/organisation/cashier.js"></script>
</body>

</html>
