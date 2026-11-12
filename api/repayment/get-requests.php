<?php
// Demandes en attente d'un membre (page « À rembourser », caissiers, scripts/pages/organisation/cashier.js).
// (« Mes demandes » est affichée en PHP : dashboard/organisation/requests/index.php)
// JSON : organisation_id, memberId

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'cashier');
$repaymentRequestManager = new RepaymentRequestManager($mysqlClient, $organisation, $user);

$requests = $repaymentRequestManager->getPendingRequestsOfMember(api_id($input['memberId'] ?? null, 'Membre non trouvé.'));

json_success([
    'data' => array_map(fn(RepaymentRequest $request) => $repaymentRequestManager->toArray($request), $requests),
    'currency' => $organisation->getCurrency(),
]);
