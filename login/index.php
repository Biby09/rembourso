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

                    <h2 class="text-center mb-4">Connexion</h2>
                    <div id="formMessage"></div>
                    <form id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Votre adresse email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Votre mot de passe" required>
                        </div>
                        <input type="hidden" name="redirect" value="<?php echo isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '/dashboard'; ?>">
                        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                    </form>
                    <p class="text-center mt-3">
                        Pas encore de compte ? <a href="/register">Inscrivez-vous</a>
                    </p>
                    <p class="text-center mt-3">
                        <a href="forgot-password.php">Mot de passe oublié ?</a>
                    </p>

                </div>
            </div>
        </section>
    </main>
    <?php include '../includes/footer.php'; ?>
    <script src="/scripts/functions.js"></script>
    <script>
        document.getElementById("loginForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            await sendForm(e.target, "submit-login.php", {
                messageContainer: "#formMessage",
                successMessage: "Vous êtes connecté avec succès !",
                errorMessage: "Erreur de connexion. Veuillez vérifier vos identifiants et réessayer.",
                loadingMessage: "Connexion en cours..."
            });

        });

    </script>
</body>

</html>