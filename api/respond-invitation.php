<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php'; ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php'; ?>

<?php

if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    header('Location: /login/?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);


if (!(isset($input['token']) || isset($input['id'])) || !isset($input['accept'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Token, ID ou statut manquant']);
    exit;
}

try {
    $invitation = new Invitation(token: $input['token'], id: $input['id']);
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

if ($invitation->user_id != $user->id) {
    http_response_code(403);
    echo json_encode(['error' => 'Invitation non valide pour cet utilisateur']);
    exit;
}

if ($organisation->isMember($user->id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Vous êtes déjà membre de cette organisation']);
    exit;
}


if ($input['accept']) {
    $invitation->accept();
    echo json_encode(['success' => true]);
} else {
    $invitation->decline();
    echo json_encode(['success' => true]);
}
?>