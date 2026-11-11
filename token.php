<?php
/**
 * Point d'entrée des liens envoyés par e-mail : redirige vers la page correspondant au type de jeton
 * (invitation, réinitialisation de mot de passe ou création de compte d'une personne sans compte).
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/token/tokenManager.php';

/**
 * Page vers laquelle rediriger pour ce jeton, '' s'il est utilisé ou expiré, null s'il est inconnu.
 * Les invitations stockent le jeton en clair, les autres liens son empreinte (TokenManager::hash).
 */
function token_target(PDO $pdo, string $token): ?string
{
    $checks = [
        '/invitation/?token=' => ['SELECT t.tok_used, t.tok_expires_at FROM invitations i JOIN tokens t ON t.id_token = i.idx_token WHERE t.tok_token = :token', $token],
        '/account-claim/?token=' => ['SELECT t.tok_used, t.tok_expires_at FROM account_claims a JOIN tokens t ON t.id_token = a.idx_token WHERE t.tok_token = :token', TokenManager::hash($token)],
        '/login/reset-password.php?token=' => ['SELECT t.tok_used, t.tok_expires_at FROM password_reset_requests p JOIN tokens t ON t.id_token = p.idx_token WHERE t.tok_token = :token', TokenManager::hash($token)],
    ];

    foreach ($checks as $target => [$sql, $value]) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['token' => $value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return TokenManager::isUsable((bool) $row['tok_used'], $row['tok_expires_at']) ? $target : '';
        }
    }
    return null;
}

$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : null;
$errorMessage = 'Token manquant';

if ($token !== null) {
    $target = token_target($mysqlClient, $token);
    if ($target === null) {
        $errorMessage = 'Token invalide';
    } elseif ($target === '') {
        $errorMessage = 'Lien déjà utilisé ou expiré';
    } else {
        header('Location: ' . $target . urlencode($token));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/includes/head.php'; ?>

<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="d-flex align-items-center" style="min-height: calc(100vh - 200px);">
        <section class="container">
            <div class="alert alert-danger"><?= e($errorMessage) ?></div>
        </section>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>
