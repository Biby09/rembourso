<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

/*
    Page du lien d'invitation reçu par e-mail (/invitation/?token=…, via token.php) :
    accepter ou refuser l'invitation (scripts/pages/invitation.js).
*/

/**
 * Invitation valable pour l'utilisateur connecté, ou message d'erreur : [Invitation|null, message|null]
 */
function load_invitation(InvitationManager $invitationManager, OrganisationManager $organisationManager, PDO $pdo, User $user): array
{
    $token = $_GET['token'] ?? null;
    if (!is_string($token) || $token === '') {
        return [null, 'Token manquant'];
    }

    try {
        $invitation = $invitationManager->getInvitationByToken($token);
    } catch (Exception) {
        return [null, 'Invitation non trouvée'];
    }

    if ($invitation->getUserId() !== $user->getId()) {
        return [null, 'Invitation non valide pour cet utilisateur'];
    }
    $organisation = OrganisationManager::findById($pdo, $invitation->getOrganisationId());
    if ($organisation && $organisationManager->isMember($organisation)) {
        return [null, 'Vous êtes déjà membre de cette organisation'];
    }
    if (!$invitation->isValid()) {
        return [null, 'Cette invitation a déjà été utilisée ou a expiré'];
    }

    return [$invitation, null];
}

[$invitation, $errorMessage] = load_invitation($invitationManager, $organisationManager, $mysqlClient, $user);
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Invitation | Rembourso';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4">
        <h1 class="fs-3 mb-4">Invitation</h1>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
            <a class="btn btn-secondary" href="/dashboard/">Retour au tableau de bord</a>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title fs-5">Invitation pour <?= e($invitation->getOrganisationName()) ?></h2>
                    <p class="card-text">Vous avez été invité à rejoindre l'organisation <?= e($invitation->getOrganisationName()) ?>
                        en tant que <?= e(mb_strtolower($invitation->getRoleLabel())) ?>.</p>
                    <div data-invitation-token="<?= e($invitation->getToken()) ?>">
                        <button class="btn btn-primary" data-invitation-response="accept">Accepter</button>
                        <button class="btn btn-secondary" data-invitation-response="decline">Refuser</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
    <script type="module" src="<?= asset('/scripts/pages/invitation.js') ?>"></script>
</body>

</html>
