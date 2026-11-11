<?php
/**
 * Page du lien de création de compte envoyé à une personne sans compte (/account-claim/?token=…, via token.php).
 * - Aucun compte avec cet e-mail : la personne choisit un mot de passe.
 * - Un compte existe déjà avec cet e-mail : elle s'y connecte puis rattache ses demandes.
 * Formulaires envoyés à api/account/claim-account.php (scripts/pages/ajax-forms.js).
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/accountClaim/accountClaimManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisationManager.php';

$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
$errorMessage = null;
$claim = $person = $organisation = $existingAccount = null;

try {
    $accountClaimManager = new AccountClaimManager($mysqlClient, $userManager);
    $claim = $accountClaimManager->getClaimByToken($token);
    $person = $accountClaimManager->getPerson($claim);
    $organisation = OrganisationManager::findById($mysqlClient, $claim->getOrganisationId()) ?? throw new Exception('Organisation not found');
    $existingAccount = $accountClaimManager->getExistingAccount($claim);
} catch (Exception) {
    $errorMessage = 'Ce lien est invalide.';
}

if (!$errorMessage && !$claim->isValid()) {
    $errorMessage = "Ce lien a déjà été utilisé ou a expiré. Demandez au caissier de l'organisation de vous en renvoyer un.";
}

$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$loginUrl = '/login?redirect=' . urlencode('/account-claim/?token=' . $token);
$page_title = 'Créer mon compte';
?>
<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="d-flex align-items-center py-5" style="min-height: calc(100vh - 200px);">
        <section class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
                    <?php else: ?>
                        <h1 class="h2 text-center mb-3">Vos demandes de remboursement</h1>
                        <p class="text-center text-muted mb-4">
                            <strong><?= e($organisation->getName()) ?></strong> a enregistré des demandes de remboursement à votre nom.
                        </p>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h2 class="h5 card-title">Informations enregistrées</h2>
                                <p class="mb-1"><?= e($person->getFullName()) ?></p>
                                <p class="mb-1 text-muted"><?= e($person->getAddress()) ?>, <?= e($person->getPostalCode() . ' ' . $person->getCity()) ?></p>
                                <p class="mb-0 text-muted">IBAN : <?= e(iban_to_human_format($person->getIban())) ?></p>
                                <small class="text-muted d-block mt-2">Vous pourrez modifier ces informations dans « Mon compte ».</small>
                            </div>
                        </div>

                        <div id="claimMessage"></div>

                        <?php if ($existingAccount): ?>
                            <?php if ($currentUserId === (int) $existingAccount->getId()): ?>
                                <p>Un compte existe déjà avec l'adresse <strong><?= e($person->getEmail()) ?></strong>. Rattachez ces demandes à votre compte :</p>
                                <form action="/api/account/claim-account.php" method="post" data-ajax-form
                                    data-message-target="#claimMessage"
                                    data-loading="Rattachement en cours..."
                                    data-success="Vos demandes ont été rattachées à votre compte."
                                    data-error="Une erreur est survenue. Veuillez réessayer.">
                                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                    <input type="hidden" name="token" value="<?= e($token) ?>">
                                    <input type="hidden" name="action" value="merge">
                                    <button type="submit" class="btn btn-primary w-100">Rattacher à mon compte</button>
                                </form>
                            <?php elseif ($currentUserId !== null): ?>
                                <div class="alert alert-warning">
                                    Vous êtes connecté avec un autre compte. Déconnectez-vous, puis connectez-vous avec l'adresse
                                    <strong><?= e($person->getEmail()) ?></strong> pour rattacher ces demandes.
                                </div>
                                <a href="/logout" class="btn btn-outline-secondary w-100">Se déconnecter</a>
                            <?php else: ?>
                                <p>Un compte existe déjà avec l'adresse <strong><?= e($person->getEmail()) ?></strong>.
                                    Connectez-vous pour y rattacher ces demandes.</p>
                                <a href="<?= e($loginUrl) ?>" class="btn btn-primary w-100">Se connecter</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <h2 class="h5">Créer mon compte</h2>
                            <p class="text-muted">Votre identifiant sera <strong><?= e($person->getEmail()) ?></strong>. Choisissez un mot de passe :</p>
                            <form action="/api/account/claim-account.php" method="post" data-ajax-form
                                data-message-target="#claimMessage"
                                data-loading="Création du compte..."
                                data-success="Votre compte a été créé."
                                data-error="Une erreur est survenue. Veuillez réessayer.">
                                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                                <input type="hidden" name="token" value="<?= e($token) ?>">
                                <input type="hidden" name="action" value="create">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mot de passe</label>
                                    <input type="password" class="form-control" name="password" id="password" autocomplete="new-password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" autocomplete="new-password"
                                        data-must-match="#password" data-mismatch="Les mots de passe ne correspondent pas" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Créer mon compte</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
    <script type="module" src="/scripts/pages/ajax-forms.js"></script>
</body>

</html>
