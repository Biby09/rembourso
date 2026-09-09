<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['organisation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing organisation_id']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Organisation not found']);
    exit;
}

if (!in_array($user, $organisation->getMembers())) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if (!$organisation->isCashier($user->id)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}



$response = $organisation->getUsersWithPendingRequests();

http_response_code(200);
echo json_encode(['success' => true, 'data' => $response, 'currency' => $organisation->currency]);