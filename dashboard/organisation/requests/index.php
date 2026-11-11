<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/organisation-page.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

/*
    « Mes demandes » (/dashboard/organisation/requests/?id=…) : demandes dont l'utilisateur est le bénéficiaire
    ou qu'il a saisies pour quelqu'un d'autre, par page de 10 (scripts/pages/organisation/requests.js).
    Une demande en attente peut être modifiée ou supprimée (modal chargé par scripts/components/repayment-edit-dialog.js).
*/

$requestCount = (new RepaymentRequestManager($mysqlClient, $organisation, $user))->countMyRequests();
$pageCount = (int) ceil($requestCount / RepaymentRequestManager::PAGE_SIZE);
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Mes demandes | ' . $organisation->getName();
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4" id="organisation-page" data-organisation-id="<?= $organisation->getId() ?>" data-organisation-name="<?= e($organisation->getName()) ?>">
        <?php
        $heading = 'Mes demandes';
        $headingAction = '<a class="btn btn-primary" href="' . e(organisation_url($organisation)) . '">+ Nouvelle demande</a>';
        include $_SERVER['DOCUMENT_ROOT'] . '/includes/page-heading.php';
        ?>

        <?php if ($requestCount > 0): ?>
            <!-- Rempli par scripts/pages/organisation/requests.js -->
            <div class="accordion" id="accordion-repayment"></div>
            <div class="d-flex justify-content-center gap-2 mt-4" id="repayment-history-pagination" data-page-count="<?= $pageCount ?>"></div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center text-muted py-5">Vous n'avez encore aucune demande de remboursement dans cette organisation.</div>
            </div>
        <?php endif; ?>

        <!-- Modal de modification d'une demande, injecté par scripts/components/repayment-edit-dialog.js -->
        <div id="repaymentEditDialog"></div>
    </main>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
    <script type="module" src="/scripts/pages/organisation/requests.js"></script>
</body>

</html>
