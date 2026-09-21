<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=/dashboard');
    exit;
}
try {
    $user = new User(id: $_SESSION['user_id']);
} catch (Exception $e) {
    // En cas d'erreur lors de la récupération de l'utilisateur, on détruit la session et on redirige vers la page de connexion
    session_destroy();
    header('Location: /login?redirect=/dashboard');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="container py-5">
        <h1 class="mb-4">Bienvenue sur votre tableau de bord, <?= htmlspecialchars($user->first_name) ?> !</h1>
        <p class="lead">Ici, vous pouvez gérer vos demandes de remboursement et organiser vos associations.</p>
        <div class="row g-4 mt-4">
            <div class="col-lg-12">
                <div class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <h2 class="card-title">Mes dernières demandes de remboursement</h2>
                        <?php
                        $userRepayments = $user->getRepaymentRequests(limit: 5);
                        if (!empty($userRepayments)) {
                            echo '<p class="card-text">Vous avez les demandes de remboursement suivantes :</p>'; ?>
                            <div class="accordion" id="accordion-repayment">
                                <?php foreach ($userRepayments as $repayment):
                                    $organisation = new Organisation($repayment->organisation_id) ?>

                                    <div id="accordionItem<?= $repayment->id ?>" class="accordion-item">
                                        <h3 class="accordion-header" id="heading<?= $repayment->id ?>">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#accordionItem<?= $repayment->id ?>Collapse"
                                                aria-expanded="true" aria-controls="accordionItem<?= $repayment->id ?>Collapse">
                                                <span
                                                    class="badge bg-<?= $repayment->status == '0' ? 'warning' : ($repayment->status == '1' ? 'success' : 'danger') ?> me-2">
                                                    <?= $repayment->status == '0' ? 'En attente' : ($repayment->status == '1' ? 'Acceptée' : 'Refusée') ?>

                                                </span>
                                                <?= htmlspecialchars($repayment->label) ?>
                                            </button>
                                        </h3>
                                        <div id="accordionItem<?= $repayment->id ?>Collapse" class="accordion-collapse collapse"
                                            data-bs-parent="#accordion-repayment">
                                            <div class="accordion-body container row">
                                                <div
                                                    class="col-lg-6 d-flex flex-column justify-content-start align-items-start mt-3">
                                                    <h4><?= htmlspecialchars($repayment->label) ?></h4>
                                                    <h5 class="text-muted mb-3"><?= htmlspecialchars($organisation->name) ?>
                                                    </h5>
                                                    <p><strong>Montant :</strong>
                                                        <?= number_format($repayment->amount, 2, ',', ' ') . $organisation->currency ?>
                                                    </p>
                                                    <?php if ($repayment->category): ?>
                                                        <p><strong>Categorie :</strong>
                                                            <?= htmlspecialchars($repayment->category ? $repayment->category->name : 'Non spécifiée') ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if ($repayment->subcategory): ?>
                                                        <p><strong>Sous-catégorie :</strong>
                                                            <?= htmlspecialchars($repayment->subcategory ? $repayment->subcategory->name : 'Non spécifiée') ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <p><strong>Date de l'achat :</strong>
                                                        <?= $repayment->transaction_date->format('d/m/Y') ?>
                                                    </p>
                                                    <p><strong>Date de dépot de la demande:</strong>
                                                        <?= $repayment->requested_at->format('d/m/Y') ?>
                                                    </p>
                                                    <?php if ($repayment->getBatch()): ?>
                                                        <p><strong>Date de traitement :</strong>
                                                            <?= $repayment->getBatch()->date->format('d/m/Y') ?>
                                                        </p>
                                                    <?php endif; ?>


                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="d-flex justify-content-center">
                                                        <?php
                                                        $receiptPaths = $repayment->getReceiptPaths();
                                                        $receiptCount = count($receiptPaths);
                                                        $receiptTypes = array_map(fn($path) => pathinfo($path, PATHINFO_EXTENSION), $receiptPaths);
                                                        $receiptTypesData = rawurlencode(json_encode($receiptTypes));
                                                        ?>
                                                        <div class="text-center">
                                                            <?php if ($receiptCount > 1): ?>
                                                                <button type="button" class="btn btn-link btn-sm p-0 mb-2 receipt-gallery-trigger"
                                                                    data-repayment-id="<?= $repayment->id ?>" data-receipt-count="<?= $receiptCount ?>"
                                                                    data-receipt-types="<?= htmlspecialchars($receiptTypesData, ENT_QUOTES, 'UTF-8') ?>">Voir les autres quittances (<?= $receiptCount ?>)</button>
                                                            <?php endif; ?>
                                                            <button type="button" class="receipt-gallery-trigger border-0 bg-transparent p-0"
                                                                data-repayment-id="<?= $repayment->id ?>" data-receipt-count="<?= $receiptCount ?>"
                                                                data-receipt-types="<?= htmlspecialchars($receiptTypesData, ENT_QUOTES, 'UTF-8') ?>">
                                                                <img
                                                                    class="receipt_image mw-100"
                                                                    style="max-height: 400px; object-fit: contain;"
                                                                    src="/api/get-receipt.php?repayment_id=<?= $repayment->id ?>&receipt_index=0"
                                                                    alt="Quittance">
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                <?php endforeach; ?>
                            </div>
                            <?php
                        } else {
                            echo '<p class="card-text">Vous n\'avez aucune demande de remboursement en cours.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-4">
            <div class="col-md-6 col-lg-6">
                <div class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <h2 class="card-title">Mes organisations</h2>
                        <?php
                        $userOrganizations = $user->getOrganisations();
                        if (!empty($userOrganizations)) {
                            echo '<p class="card-text">Vous appartenez aux organisations suivantes :</p>';
                            foreach ($userOrganizations as $org): ?>
                                <div class="card mb-3 border-start border-primary border-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center h-100">
                                            <p class="card-title mb-0 fs-5 fw-bold text-primary"><?= htmlspecialchars($org->name) ?></p>
                                            <a href="/dashboard/organisation/?id=<?= $org->id ?>"
                                                class="btn btn-sm btn-outline-primary">Accéder →</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;
                        } else {
                            echo '<p class="card-text">Vous n\'appartenez à aucune organisation.</p>';
                        }
                        ?>
                        <button class="btn btn-primary" id="create-organisation-button">Créer une
                            organisation</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-6 d-flex flex-column gap-4">
                <div class="card text-decoration-none text-dark flex-grow-1">
                    <div class="card-body text-center">
                        <h2 class="card-title">Mon compte</h2>
                        <p class="card-text">Gérez vos informations personnelles et les paramètres de votre compte.</p>
                        <a href="/dashboard/account" class="btn btn-primary">Gérer mon compte →</a>
                    </div>
                </div>
                <div class="card text-decoration-none text-dark flex-grow-1">
                    <div class="card-body text-center">
                        <h2 class="card-title">Invitations</h2>
                        <?php
                        $invitations = $user->getInvitations();
                        if (empty($invitations)) {
                            echo '<p class="card-text">Vous n\'avez aucune invitation en attente.</p>';
                        } else {
                            foreach ($invitations as $invitation) {
                                $org = new Organisation($invitation->organisation_id); ?>
                                <div class="card mb-3 border-start border-secondary border-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h3 class="card-title mb-0 text-secondary"> <?= htmlspecialchars($org->name) ?></h3>
                                            <div>
                                                <button class="btn btn-sm btn-success me-2 respond-invitation-accept" data-invitation-id= <?=$invitation->id ?>>Accepter</button>
                                                <button class="btn btn-sm btn-danger respond-invitation-reject" data-invitation-id= <?=$invitation->id ?>>Refuser</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="create-organisation-dialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">

                        <div class="modal-header bg-light border-0 pb-0">
                            <div class="w-100">
                                <h3 class="modal-title fw-600 mb-2">Créer une organisation</h3>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body px-4 py-4">
                            <div id="formMessage"></div>
                            <form id="create-organisation-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <div class="mb-3">
                                    <label for="org-name" class="form-label">Nom de l'organisation</label>
                                    <input type="text" class="form-control" id="org-name" name="org_name" required>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer bg-light border-top px-4 py-3">
                            <button type="button" class="btn btn-light border" id="cancel-create-org"
                                data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" form="create-organisation-form"
                                class="btn btn-primary">Créer</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
    <script src="/scripts/functions.js"></script>
    <script>
        const createOrgDialog = document.getElementById('create-organisation-dialog');
        const createOrgModalInstance = getBootstrapModal(createOrgDialog);

        document.getElementById('create-organisation-button').addEventListener('click', function () {
            createOrgModalInstance?.show();
        });

        const createOrgForm = document.getElementById('create-organisation-form');

        createOrgForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const orgName = document.getElementById('org-name').value.trim();

            if (!orgName) {
                return;
            }

            const result = await sendForm(createOrgForm, '/api/create-organisation.php', {
                messageContainer: "#formMessage",
                successMessage: "Organisation créée avec succès !",
                errorMessage: "Erreur lors de la création de l'organisation. Veuillez réessayer.",
                loadingMessage: "Création de l'organisation en cours..."
            });

            if (result.success) {
                hideBootstrapModal(createOrgDialog);
            }
        });
    </script>
<!-- Script pour gérer les réponses aux invitations -->
    <script>
        const acceptButtons = document.querySelectorAll('.respond-invitation-accept');
        const refuseButtons = document.querySelectorAll('.respond-invitation-reject');

        acceptButtons.forEach(button => {
            button.addEventListener('click', async function () {
                const invitationId = parseInt(this.getAttribute('data-invitation-id'));
                await respondToInvitation(true, null, invitationId);
                location.reload();
            });
        });

        refuseButtons.forEach(button => {
            button.addEventListener('click', async function () {
                const invitationId = parseInt(this.getAttribute('data-invitation-id'));
                await respondToInvitation(false, null, invitationId);
                location.reload();
            });
        });

    </script>

</body>

</html>