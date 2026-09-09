<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

verify_csrf_request(null, true);

if (!isset($_POST['email'], $_POST['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
    exit;
}

$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$password = $_POST['password'];

if (!$email || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email ou mot de passe invalide']);
    exit;
}

$emailIdentifier = strtolower(trim($email));
rate_limit_consume('login_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);
rate_limit_consume('login_email', $emailIdentifier, 5, 900);

$user = null;
try {
    $user = new User(null, $email);
} catch (Exception $exception) {
    log_server_exception($exception, 'Échec de connexion');
}

if (!$user || !$user->id || !$user->verifyPassword($password) || !$user->active) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
    exit;
}else {
    rate_limit_clear('login_email', $emailIdentifier);
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user->id;
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Connexion réussie', 'redirect' => isset($_POST['redirect']) ? $_POST['redirect'] : '/dashboard']);
    exit;
}