<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php'; ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php'; ?>

<?php if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    header('Location: /login/?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (!isset($_GET['token'])) {
    $message = 'Token manquant';
}

try {
    $invitation = new Invitation(token: $_GET['token']);
} catch (Exception $e) {
    $message = 'Invitation non trouvée';
}

try {
    $organisation = new Organisation($invitation->organisation_id);
} catch (Exception $e) {
    $message = 'Organisation non trouvée';
}

if ($invitation->user_id != $user->id) {
    $message = 'Invitation non valide pour cet utilisateur';
}

if (in_array($user, $organisation->getMembers())) {
    $message = 'Vous êtes déjà membre de cette organisation';
}
?>


<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>

    <div class="container mt-4">
        <h1>Invitations</h1>
        <p>Gérez les invitations à votre organisation ici.</p>
        <?php if (isset($message)): ?>
            <div class="alert alert-danger" role="alert">
                <p><?= $message ?></p>
            </div>
            <a class="btn btn-secondary" href="/dashboard/">Retour au tableau de bord</a>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Invitation pour <?= htmlspecialchars($organisation->name) ?></h5>
                    <p class="card-text">Vous avez été invité à rejoindre l'organisation <?= htmlspecialchars($organisation->name) ?>.</p>
                    <button class="btn btn-primary" id="accept-btn">Accepter</button>
                    <button class="btn btn-secondary" id="decline-btn">Refuser</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="/scripts/functions.js"></script>
    <script>
        const acceptBtn = document.getElementById('accept-btn');
        const declineBtn = document.getElementById('decline-btn');

        acceptBtn.addEventListener('click', () => {
            respondToInvitation(true, '<?= $invitation->token ?>', '<?= $organisation->id ?>');
        });

        declineBtn.addEventListener('click', () => {
            respondToInvitation(false, '<?= $invitation->token ?>', '<?= $organisation->id ?>');
        });
    </script>
</body>

</html>