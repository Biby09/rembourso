<?php
// Nouveau mot de passe choisi depuis le lien de réinitialisation (page login/reset-password.php)
// POST : token, password, confirm_password

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/passwordReset/passwordResetManager.php';

header('Content-Type: application/json; charset=utf-8');

/** Réponse d'erreur JSON (les messages s'affichent dans le formulaire) */
function reset_error(string $message, int $status = 400): never
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if (isset($_SESSION['user_id'])) {
    reset_error('Vous êtes déjà connecté.', 403);
}

verify_csrf_request(null, true);

$token = $_POST['token'] ?? null;
if (!is_string($token) || $token === '') {
    reset_error('Token manquant');
}

rate_limit_consume('reset_token_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);
rate_limit_consume('reset_token', hash('sha256', $token), 5, 3600);

$passwordResetManager = new PasswordResetManager($mysqlClient);
try {
    $reset = $passwordResetManager->getResetByToken($token);
} catch (Exception) {
    reset_error('Token invalide');
}

if (!$reset->isValid()) {
    reset_error('Token expiré');
}

$password = (string) ($_POST['password'] ?? '');
if ($password === '' || $password !== ($_POST['confirm_password'] ?? null)) {
    reset_error('Les mots de passe sont vides ou ne correspondent pas');
}

try {
    $user = $passwordResetManager->resetPassword($reset, $userManager, $password);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la réinitialisation du mot de passe');
    reset_error('Erreur lors de la mise à jour du mot de passe', 500);
}

session_regenerate_id(true);
$_SESSION['user_id'] = $user->getId();
echo json_encode(['success' => true, 'message' => 'Mot de passe réinitialisé avec succès', 'redirect' => '/dashboard']);
