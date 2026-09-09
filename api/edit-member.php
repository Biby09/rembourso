<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

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

if (!isset($input['organisation_id'], $input['member_id'])) {
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
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $member = new User($input['member_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Member not found']);
    exit;
}

if (!$organisation->isMember($member->id)) {
    http_response_code(404);
    echo json_encode(['error' => 'Member not found in organisation']);
    exit;
}


if ($input['action'] === 'delete') {
    $organisation->removeMember($member->id);
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
} else if ($input['action'] === 'update') {
    if (!isset($input['new_role']) || !in_array($input['new_role'], ['admin', 'member', 'cashier'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role']);
        exit;
    }

    $organisation->changeMemberRole($member->id, $input['new_role']);
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}