<?php
$requiredRole = 'cashier';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/organisation-page.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/batch/repaymentBatchManager.php';

/*
    « Historique » (/dashboard/organisation/history/?id=…), caissiers et administrateurs :
    lots de remboursement déjà traités, par page de 10, avec leur PDF de confirmation
    (scripts/pages/organisation/history.js).
*/

$hasBatches = (new RepaymentBatchManager($mysqlClient, $organisation))->countBatches() > 0;
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Historique des remboursements | ' . $organisation->getName();
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4" id="organisation-page" data-organisation-id="<?= $organisation->getId() ?>" data-organisation-name="<?= e($organisation->getName()) ?>">
        <?php $heading = 'Historique des remboursements'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/page-heading.php'; ?>

        <div class="card">
            <div class="card-body">
                <?php if ($hasBatches): ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0" id="repayment-history-table">
                            <thead>
                                <tr>
                                    <th scope="col">Bénéficiaire</th>
                                    <th scope="col">Caissier</th>
                                    <th scope="col" class="text-center">Date</th>
                                    <th scope="col" class="text-end">Montant total</th>
                                    <th scope="col" class="text-center">Confirmation</th>
                                </tr>
                            </thead>
                            <!-- Rempli par scripts/pages/organisation/history.js -->
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center gap-2 mt-4" id="repayment-history-batches-pagination"></div>
                <?php else: ?>
                    <p class="text-muted text-center my-4">Aucun remboursement dans l'historique pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
    <script type="module" src="<?= asset('/scripts/pages/organisation/history.js') ?>"></script>
</body>

</html>
