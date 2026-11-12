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
                                <?php
                                $accordionId = 'accordion-repayment';
                                $currentUserId = $user->getId();
                                $showOrganisation = true;
                                foreach ($latestRequests as $request) {
                                    $currency = $request->getCurrency();
                                    include $_SERVER['DOCUMENT_ROOT'] . '/includes/repayment-item.php';
                                }
                                ?>
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
    <script type="module" src="<?= asset('/scripts/pages/dashboard.js') ?>"></script>
</body>

</html>
