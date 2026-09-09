<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

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

if (!isset($input['organisation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid organisation ID']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
} catch (Exception $e) {
    log_server_exception($e, 'Organisation introuvable lors de la récupération des remboursements');
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Organisation non trouvée']);
    exit;
}

if (!$organisation->isMember($user->id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

if (isset($input['page']) && is_numeric($input['page']) && $input['page'] >= 1) {

    $reponse = $organisation->getUserRepaymentRequests($input['page']);

} else if (isset($input['repaymentId'])) {

    if (!$organisation->isCashier($user->id)){
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    $reponse = new RepaymentRequest($input['repaymentId']);
    $response = $response->getSafeData();
} else if (isset($input['memberId'])) {

    if (!$organisation->isCashier($user->id)){
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    $reponseTmp = $organisation->getMemberRepaymentRequests($input['memberId'],[0]);

    foreach ($reponseTmp as $repaymentRequest) {
        $reponse[] = $repaymentRequest->getSafeData();
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request parameters']);
    exit;
}

echo json_encode(['success' => true, 'data' => $reponse, 'currency' => $organisation->currency]);