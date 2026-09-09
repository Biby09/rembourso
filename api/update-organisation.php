<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

header('Content-Type: application/json');

if (!userConnected()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['organisation_id'], $input['action']) || !is_numeric($input['organisation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
} catch (Exception $e) {
    log_server_exception($e, 'Organisation introuvable lors de sa mise à jour');
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Organisation non trouvée']);
    exit;
}

// Seul un administrateur peut renommer ou supprimer l'organisation
if (!$organisation->isAdmin($user->id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

try {
    switch ($input['action']) {
        case 'rename':
            if (!isset($input['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nom manquant']);
                exit;
            }
            $organisation->updateName($input['name']);
            echo json_encode(['success' => true, 'name' => htmlspecialchars($organisation->name, ENT_QUOTES, 'UTF-8')]);
            break;

        case 'delete':
            $organisation->delete();
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la mise à jour de l’organisation');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour de l’organisation']);
}
