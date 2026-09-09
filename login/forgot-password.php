<?php
 
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
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

                    <h2 class="text-center mb-4">Mot de passe oublié</h2>
                    <div id="formMessage"></div>
                    <form id="forgotPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Votre adresse email" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Envoyer le lien de réinitialisation</button>
                    </form>
                    <p class="text-center mt-3">
                        Vous avez déjà un compte ? <a href="/login">Connectez-vous</a>
                    </p>

                </div>
            </div>
        </section>
    </main>
    <?php include '../includes/footer.php'; ?>
    <script src="/scripts/functions.js"></script>
    <script>
        document.getElementById("forgotPasswordForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            await sendForm(e.target, "submit-forgot-password.php", {
                messageContainer: "#formMessage",
                successMessage: "Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.",
                errorMessage: "Une erreur est survenue. Veuillez réessayer.",
                loadingMessage: "Envoi en cours..."
            });

        });
    </script>
    
</body>
</html>