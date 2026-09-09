<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['invitation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de l\'invitation manquant']);
    exit;
}

try {
    $invitation = new Invitation(id: $input['invitation_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Invitation non trouvée']);
    exit;
}

try {
    $organisation = new Organisation($invitation->organisation_id);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Organisation non trouvée']);
    exit;
}

if (!$organisation->isAdmin($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}


try {
    $invitation->decline();
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la suppression de l\'invitation']);
    exit;
}