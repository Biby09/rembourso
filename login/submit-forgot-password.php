<?php
// Demande de réinitialisation du mot de passe (page login/forgot-password.php)
// POST : email. Même réponse que le compte existe ou non (on ne révèle pas quels e-mails ont un compte).

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/passwordReset/passwordResetManager.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

verify_csrf_request(null, true);

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

rate_limit_consume('reset_request_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);
rate_limit_consume('reset_request_email', strtolower(trim($email)), 3, 3600);

$account = $userManager->findAccountByEmail($email);
if ($account && $account->isActive()) {
    try {
        (new PasswordResetManager($mysqlClient))->createReset($account);
    } catch (Exception $e) {
        log_server_exception($e, 'Erreur lors de la demande de réinitialisation du mot de passe');
    }
}

echo json_encode(['success' => true, 'message' => 'Si un compte existe avec cet email, vous recevrez un lien pour réinitialiser votre mot de passe']);
