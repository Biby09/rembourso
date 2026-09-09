<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()){
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

try{
    $user = new User($_SESSION['user_id']);
}
catch(Exception $e){
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['organisation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de l\'organisation manquant']);
    exit;
}

if (!isset($input['page'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Numéro de page manquant']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Organisation non trouvée']);
    exit;
}

if (!$organisation->isCashier($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$batchesPerPage = 10;
$page = max(1, intval($input['page']));

try {
    $repaymentBatches = $organisation->getRepaymentBatches($batchesPerPage, $page);
    $totalBatches = $organisation->getNumberOfBatches($organisation->id);
    $totalPages = ceil($totalBatches / $batchesPerPage);

    $response = [];
    foreach ($repaymentBatches as $batch) {
        $response[] = [
            'id' => $batch->id,
            'user' => htmlspecialchars($batch->getBeneficiary()->first_name . ' ' . $batch->getBeneficiary()->last_name),
            'cashier' => htmlspecialchars($batch->cashier->first_name . ' ' . $batch->cashier->last_name),
            'date' => $batch->date->format('j.n.Y'),
            'totalAmount' => $batch->getTotalAmount()
        ];
    }
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'repaymentBatches' => $response,
            'totalPages' => $totalPages,
            'currency' => $organisation->currency
        ]
    ]);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur de récupération des lots de remboursement');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des lots de remboursement']);
    exit;
}