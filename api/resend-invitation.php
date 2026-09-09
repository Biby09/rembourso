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

if ($invitation->last_mailed_at > (new DateTime())->modify('-1 day')) {
    http_response_code(429);
    echo json_encode(['error' => 'Dernier mail envoyé il y a moins d\'un jour. Veuillez attendre avant de réessayer.']);
    exit;
}

try {
    if ($invitation->resendMail()) {
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la réexpédition de l\'invitation']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la réexpédition de l\'invitation']);
    exit;
}