<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';


?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/header.php'; ?>
    <main class="d-flex align-items-center" style="min-height: calc(100vh - 200px);">
        <section class="container">
            <?php
            if (!isset($_GET['token'])) {
                echo "<div class='alert alert-danger'>Token manquant</div>";
            } else {
                    
                try {
                    $redirectData = getTokenObject($_GET['token']);

                    if (!$redirectData) {
                        throw new Exception("Token invalide");
                    }

                    if ($redirectData[1]->expires_at < new DateTime() || $redirectData[1]->used) {
                        throw new Exception("Lien déjà utilisé ou expiré");
                    }

                    if (isset($redirectData[0]) && $redirectData[0] === 'invitation') {
                        header('Location: /invitation/?token=' . $_GET['token']);
                        exit;
                    } elseif (isset($redirectData[0]) && $redirectData[0] === 'password_reset') {
                        header('Location: /login/reset-password.php?token=' . $_GET['token']);
                        exit;
                    } else {
                        throw new Exception("Token invalide");
                    }
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>".$e->getMessage()."</div></div>";
                }
            }
            ?>
        </section>
    </main>
    <?php include 'includes/footer.php'; ?>

</body>

</html>