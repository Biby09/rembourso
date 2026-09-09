<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (isset($_SESSION['user_id'])) {
    session_destroy();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

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
                    <?php if (!isset($_GET['token'])): ?>
                        <div class="alert alert-danger">Token manquant</div>
                    <?php else: ?>
                        <?php
                        try {
                            $resetPassword = new PasswordResetRequest($_GET['token']);
                        } catch (Exception $e) {
                            $resetPassword = null;
                        }
                        if (!$resetPassword): ?>
                            <div class="alert alert-danger">Token invalide</div>
                        <?php else: ?>
                            <?php if (!$resetPassword->isValid()): ?>
                                <div class="alert alert-danger">Token invalide ou expiré</div>
                            <?php endif; ?>
                            <h2 class="text-center mb-4">Réinitialisation du mot de passe</h2>
                            <div id="formMessage"></div>
                            <form id="resetPasswordForm">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" name="password" id="password"
                                        placeholder="Votre nouveau mot de passe" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password"
                                        placeholder="Confirmez votre nouveau mot de passe" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Réinitialiser le mot de passe</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <?php include '../includes/footer.php'; ?>
    <script src="/scripts/functions.js"></script>
    <script>
        document.getElementById("resetPasswordForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            if (document.getElementById("password").value !== document.getElementById("confirm_password").value) {
                document.getElementById("formMessage").innerHTML = '<div class="alert alert-danger">Les mots de passe ne correspondent pas</div>';
                return;
            }

            await sendForm(e.target, "submit-reset-password.php", {
                messageContainer: "#formMessage",
                successMessage: "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.",
                errorMessage: "Une erreur est survenue. Veuillez réessayer.",
                loadingMessage: "Réinitialisation en cours..."
            });

        });
    </script>
</body>

</html>