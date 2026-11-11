<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/passwordReset/passwordResetManager.php';

// Page ouverte depuis le lien reçu par e-mail : on déconnecte l'éventuel utilisateur
// (sans détruire la session, qui garde le jeton CSRF du formulaire)
if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
$errorMessage = null;
if ($token === '') {
    $errorMessage = 'Token manquant';
} else {
    try {
        if (!(new PasswordResetManager($mysqlClient))->getResetByToken($token)->isValid()) {
            $errorMessage = 'Token invalide ou expiré';
        }
    } catch (Exception) {
        $errorMessage = 'Token invalide';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include '../includes/head.php'; ?>

<body>
    <?php include '../includes/header.php'; ?>
    <main class="d-flex align-items-center" style="min-height: calc(100vh - 200px);">
        <section class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
                    <?php else: ?>
                            <h2 class="text-center mb-4">Réinitialisation du mot de passe</h2>
                            <div id="formMessage"></div>
                            <form id="resetPasswordForm" action="submit-reset-password.php" method="post" data-ajax-form
                                data-message-target="#formMessage"
                                data-loading="Réinitialisation en cours..."
                                data-success="Votre mot de passe a été réinitialisé avec succès."
                                data-error="Une erreur est survenue. Veuillez réessayer.">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="token" value="<?= e($token) ?>">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" name="password" id="password"
                                        placeholder="Votre nouveau mot de passe" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password"
                                        data-must-match="#password" data-mismatch="Les mots de passe ne correspondent pas"
                                        placeholder="Confirmez votre nouveau mot de passe" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Réinitialiser le mot de passe</button>
                            </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <?php include '../includes/footer.php'; ?>
    <script type="module" src="/scripts/pages/ajax-forms.js"></script>
</body>

</html>