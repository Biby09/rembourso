<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/organisation-page.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

/*
    « Mes demandes » (/dashboard/organisation/requests/?id=…&page=N) : demandes dont l'utilisateur est le bénéficiaire
    ou qu'il a saisies pour quelqu'un d'autre, par page de 10, affichées en PHP (includes/repayment-item.php).
    Une demande en attente peut être modifiée ou supprimée (scripts/pages/organisation/requests.js).
*/

$repaymentRequestManager = new RepaymentRequestManager($mysqlClient, $organisation, $user);
$requestCount = $repaymentRequestManager->countMyRequests();
$pageCount = max(1, (int) ceil($requestCount / RepaymentRequestManager::PAGE_SIZE));
// Page demandée, ramenée entre 1 et la dernière (ex. après la suppression de la seule demande de la dernière page)
$page = min($pageCount, max(1, (int) ($_GET['page'] ?? 1)));
$requests = $repaymentRequestManager->getMyRequests($page);

$pageUrl = fn(int $number) => organisation_url($organisation, 'requests/') . '&page=' . $number;
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

        <?php if ($requests): ?>
            <div class="accordion" id="accordion-repayment">
                <?php
                $accordionId = 'accordion-repayment';
                $currency = $organisation->getCurrency();
                $currentUserId = $user->getId();
                foreach ($requests as $request) {
                    $editable = $repaymentRequestManager->isEditable($request);
                    include $_SERVER['DOCUMENT_ROOT'] . '/includes/repayment-item.php';
                }
                ?>
            </div>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/pagination.php'; ?>
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
