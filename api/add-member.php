<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php'; ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php'; ?>

<?php

if (!userConnected()) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['organisation_id'], $input['email'], $input['role'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Organisation not found']);
    exit;
}

if (!$organisation->isAdmin($user->id)) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to add members to this organisation']);
    exit;
}

try {
    $member = new User($id = null, $input['email']);

    if (Invitation::allreadyexist($organisation->id, $member->id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Une invitation pour cet utilisateur est déjà en cours']);
        exit;
    }

    if ($organisation->isMember($member->id)) {
        http_response_code(400);
        echo json_encode(['error' => 'L\'utilisateur est déjà membre de l\'organisation']);
        exit;
    }


    
    $organisation->inviteMember($member->id, $input['role']);
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
   
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de l’ajout d’un membre');
    http_response_code(404);
    echo json_encode(['error' => 'Impossible d’ajouter ce membre']);
    exit;
}