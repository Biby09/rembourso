<?php
// Suppression d'une demande en attente (page « Mes demandes »)
// JSON : repayment_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/repayment-api.php';

$input = api_json_input();
[, $repaymentRequestManager, $request] = repayment_load_editable($organisationManager, $mysqlClient, $user, $input['repayment_id'] ?? null);

try {
    $repaymentRequestManager->deleteRequest($request);
} catch (Exception $e) {
    log_server_exception($e, "Erreur lors de la suppression d'une demande de remboursement");
    json_error('Erreur lors de la suppression de la demande.', 500);
}

json_success();
