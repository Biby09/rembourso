<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try{
    $user = new User($_SESSION['user_id']);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['repayment_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid repayment ID']);
    exit;
}

try{
    $repayment = new RepaymentRequest($input['repayment_id']);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'Repayment request not found']);
    exit;
}

try{
    $organisation = new Organisation($repayment->organisation_id);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'Organisation not found']);
    exit;
}

//Controler que la personne soit dans l'organisation
if (!$organisation->isMember($user->id)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

//Controler que la personne soit le créateur de la demande

if (!$repayment->isEditable()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized', 'message' => 'Seule une demande de remboursement en attente peut être supprimée, et seulement par son créateur.']);
    exit;
}

if ($repayment->user_id !== $user->id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $repayment->delete();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to delete repayment request']);
}