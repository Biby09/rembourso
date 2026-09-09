<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    header('Location: /login?redirect=/dashboard');
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    // En cas d'erreur lors de la récupération de l'utilisateur, on détruit la session et on redirige vers la page de connexion
    session_destroy();
    header('Location: /login?redirect=/dashboard');
    exit;
}

// Récupérer le POST

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_request(null, true);

    if (!isset($_POST['org_name']) || empty(trim($_POST['org_name']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le nom de l\'organisation est requis']);
        exit;
    }

    $orgName = trim($_POST['org_name']);

    try {
        $organisation = Organisation::create($orgName);
        echo json_encode(['success' => true, 'message' => 'Organisation créée avec succès', 'redirect' => '/dashboard/organisation/?id=' . $organisation->id]);
    } catch (Exception $e) {
        log_server_exception($e, 'Erreur lors de la création d’une organisation');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de l’organisation']);
    }
    exit;
}
