<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

/*
    Tableau de bord (/dashboard) : dernières demandes de l'utilisateur (toutes organisations),
    ses organisations (+ création), son compte et ses invitations en attente (scripts/pages/dashboard.js).
*/

$latestRequests = RepaymentRequestManager::getLatestRequestsOfUser($mysqlClient, $user, 5);
$organisations = $organisationManager->getAllOrganisationsForUser();
$invitations = $invitationManager->getPendingInvitationsForUser();
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Tableau de bord | Rembourso';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4">
        <h1 class="fs-3 mb-1">Bonjour <?= e($user->getFirstName()) ?> !</h1>
        <p class="text-muted mb-4">Gérez vos demandes de remboursement et vos organisations.</p>

        <div class="row g-4">
            <!-- Dernières demandes -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title fs-4">Mes dernières demandes de remboursement</h2>
                        <?php if (!$latestRequests): ?>
                            <p class="card-text text-muted mb-0">Vous n'avez aucune demande de remboursement.</p>
                        <?php else: ?>
                            <div class="accordion mt-3" id="accordion-repayment">
                                <?php foreach ($latestRequests as $request): ?>
                                    <?php $collapseId = 'repayment-' . $request->getId() . '-collapse'; ?>
                                    <div class="accordion-item">
                                        <h3 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                                                <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/repayment-status-badge.php'; ?>
                                                <?= e($request->getLabel()) ?>
                                            </button>
                                        </h3>
                                        <div id="<?= $collapseId ?>" class="accordion-collapse collapse" data-bs-parent="#accordion-repayment">
                                            <div class="accordion-body container">
                                                <div class="row">
                                                    <div class="col-lg-6 mt-3">
                                                        <h4><?= e($request->getLabel()) ?></h4>
                                                        <h5 class="text-muted mb-3"><?= e($request->getOrganisationName()) ?></h5>
                                                        <p><strong>Montant :</strong> <?= e(format_amount($request->getAmount(), $request->getCurrency())) ?></p>
                                                        <?php if ($request->getCategoryName()): ?>
                                                            <p><strong>Catégorie :</strong> <?= e($request->getCategoryName()) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($request->getSubcategoryName()): ?>
                                                            <p><strong>Sous-catégorie :</strong> <?= e($request->getSubcategoryName()) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($request->getCreatedByName()): ?>
                                                            <p><strong>Saisie par :</strong> <?= e($request->getCreatedByName()) ?></p>
                                                        <?php endif; ?>
                                                        <p><strong>Date de l'achat :</strong> <?= e(format_date($request->getTransactionDate())) ?></p>
                                                        <p><strong>Date de dépôt de la demande :</strong> <?= e(format_date($request->getRequestedAt())) ?></p>
                                                        <?php if ($request->getBatchDate()): ?>
                                                            <p><strong>Date de traitement :</strong> <?= e(format_date($request->getBatchDate())) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-lg-6 d-flex justify-content-center">
                                                        <div class="text-center">
                                                            <?php if ($request->getReceiptCount() > 1): ?>
                                                                <button type="button" class="btn btn-link btn-sm p-0 mb-2 receipt-gallery-trigger"
                                                                    data-repayment-id="<?= $request->getId() ?>" data-receipt-count="<?= $request->getReceiptCount() ?>">Voir les autres quittances (<?= $request->getReceiptCount() ?>)</button>
                                                            <?php endif; ?>
                                                            <button type="button" class="receipt-gallery-trigger border-0 bg-transparent p-0"
                                                                data-repayment-id="<?= $request->getId() ?>" data-receipt-count="<?= $request->getReceiptCount() ?>">
                                                                <img class="receipt_image mw-100" style="max-height: 400px; object-fit: contain;"
                                                                    src="/api/repayment/get-receipt.php?repayment_id=<?= $request->getId() ?>&amp;receipt_index=0"
                                                                    alt="Quittance" loading="lazy">
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Organisations -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="card-title fs-4">Mes organisations</h2>
                        <?php if (!$organisations): ?>
                            <p class="card-text text-muted">Vous n'appartenez à aucune organisation.</p>
                        <?php endif; ?>
                        <?php foreach ($organisations as $organisationItem): ?>
                            <div class="card mb-3 border-start border-primary border-4">
                                <div class="card-body d-flex justify-content-between align-items-center gap-2">
                                    <p class="mb-0 fs-5 fw-bold text-primary text-break"><?= e($organisationItem->getName()) ?></p>
                                    <a href="/dashboard/organisation/?id=<?= $organisationItem->getId() ?>" class="btn btn-sm btn-outline-primary flex-shrink-0">Accéder →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn btn-primary" id="create-organisation-button">Créer une organisation</button>
                    </div>
                </div>
            </div>

            <div class="col-md-6 d-flex flex-column gap-4">
                <!-- Compte -->
                <div class="card flex-grow-1">
                    <div class="card-body">
                        <h2 class="card-title fs-4">Mon compte</h2>
                        <p class="card-text">Gérez vos informations personnelles et les paramètres de votre compte.</p>
                        <a href="/dashboard/account" class="btn btn-primary">Gérer mon compte →</a>
                    </div>
                </div>

                <!-- Invitations -->
                <div class="card flex-grow-1">
                    <div class="card-body">
                        <h2 class="card-title fs-4">Invitations</h2>
                        <?php if (!$invitations): ?>
                            <p class="card-text text-muted mb-0">Vous n'avez aucune invitation en attente.</p>
                        <?php else: ?>
                            <div id="invitations-list">
                                <?php foreach ($invitations as $invitation): ?>
                                    <div class="card mb-3 border-start border-secondary border-2">
                                        <div class="card-body d-flex justify-content-between align-items-center gap-2">
                                            <h3 class="card-title fs-5 mb-0 text-secondary text-break"><?= e($invitation->getOrganisationName()) ?></h3>
                                            <div class="flex-shrink-0">
                                                <button type="button" class="btn btn-sm btn-success me-2" data-invitation-response="accept" data-invitation-id="<?= $invitation->getId() ?>">Accepter</button>
                                                <button type="button" class="btn btn-sm btn-danger" data-invitation-response="decline" data-invitation-id="<?= $invitation->getId() ?>">Refuser</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/create-organisation-modal.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php';
    ?>
    <script type="module" src="/scripts/pages/dashboard.js"></script>
</body>

</html>
