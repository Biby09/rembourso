<?php
// Historique des lots de remboursement (page « Historique », caissiers)
// JSON : organisation_id, page

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/batch/repaymentBatchManager.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'cashier');
$repaymentBatchManager = new RepaymentBatchManager($mysqlClient, $organisation);

try {
    $batches = array_map(fn(RepaymentBatch $batch) => [
        'id' => $batch->getId(),
        'user' => $batch->getBeneficiaryName(),
        'cashier' => $batch->getCashierName(),
        'date' => substr($batch->getDate(), 0, 10),
        'totalAmount' => (float) $batch->getTotalAmount(),
    ], $repaymentBatchManager->getBatches(max(1, (int) ($input['page'] ?? 1))));
    $totalPages = (int) ceil($repaymentBatchManager->countBatches() / RepaymentBatchManager::PAGE_SIZE);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur de récupération des lots de remboursement');
    json_error('Erreur lors de la récupération des lots de remboursement.', 500);
}

json_success([
    'data' => [
        'repaymentBatches' => $batches,
        'totalPages' => $totalPages,
        'currency' => $organisation->getCurrency(),
    ],
]);
