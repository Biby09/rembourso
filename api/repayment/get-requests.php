<?php
// Liste de demandes de remboursement :
// - { organisation_id, page }     : « Mes demandes » (paginées, scripts/pages/organisation/requests.js)
// - { organisation_id, memberId } : demandes en attente d'un membre (caissiers, scripts/pages/organisation/cashier.js)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null);
$repaymentRequestManager = new RepaymentRequestManager($mysqlClient, $organisation, $user);

if (isset($input['page']) && is_numeric($input['page']) && $input['page'] >= 1) {
    $requests = $repaymentRequestManager->getMyRequests((int) $input['page']);
} elseif (isset($input['memberId']) && is_numeric($input['memberId'])) {
    if (!$organisationManager->isCashier($organisation)) {
        json_error('Accès refusé.', 403);
    }
    $requests = $repaymentRequestManager->getPendingRequestsOfMember((int) $input['memberId']);
} else {
    json_error('Paramètres de requête invalides.');
}

json_success([
    'data' => array_map(fn(RepaymentRequest $request) => $repaymentRequestManager->toArray($request), $requests),
    'currency' => $organisation->getCurrency(),
]);
