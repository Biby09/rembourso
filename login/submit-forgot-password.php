<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

verify_csrf_request(null, true);

if (!isset($_POST['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email requis']);
    exit;
}

$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

rate_limit_consume('reset_request_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);
rate_limit_consume('reset_request_email', strtolower(trim($email)), 3, 3600);

if (User::isEmailAvailable($email)) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Si un compte existe avec cet email, vous recevrez un lien pour réinitialiser votre mot de passe']);
    exit;
}

$user = new User(null, $email);


$resetRequest = PasswordResetRequest::create($email);

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Si un compte existe avec cet email, vous recevrez un lien pour réinitialiser votre mot de passe']);
exit;