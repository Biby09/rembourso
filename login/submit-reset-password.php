<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';


if (userConnected()) {
    header('Location: /dashboard');
    exit;
}

verify_csrf_request(null, true);

// Vérification du token
if (!isset($_POST['token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token manquant']);
    exit;
}

rate_limit_consume('reset_token_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);
rate_limit_consume('reset_token', hash('sha256', $_POST['token']), 5, 3600);

try {
    $passwordReset = new PasswordResetRequest($_POST['token']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token invalide']);
    exit;
}

if (!$passwordReset->isValid()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token expiré']);
    exit;
}
// Verification du mdp et de sa confirmation

if (!isset($_POST['password']) || !isset($_POST['confirm_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}

if ($_POST['password'] !== $_POST['confirm_password']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
    exit;
}

// Récupération de l'utilisateur

try {
    $user = new User($passwordReset->user_id);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    exit;
}

// Mise à jour du mot de passe

if ($passwordReset->use($_POST['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user->id;
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Mot de passe réinitialisé avec succès', 'redirect' => '/dashboard']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe']);
}