<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php'; ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php'; ?>

<?php if(!userConnected()) {
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

if (!isset($input['organisation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de l\'organisation manquant']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
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

$invitations = $organisation->getInvitations();

$response = [];

foreach ($invitations as $invitation) {
    try {
        $invitedUser = new User($invitation->user_id);
        $response[] = [
            'id' => $invitation->id,
            'name' => htmlspecialchars($invitedUser->first_name) . ' ' . htmlspecialchars($invitedUser->last_name),
            'role' => $invitation->getRole()
        ];
    } catch (Exception $e) {
        // Si l'utilisateur n'existe pas, on ignore cette invitation
        continue;
    }
}

http_response_code(200);
echo json_encode(['success' => true, 'data' => $response]);