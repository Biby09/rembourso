<?php
// Membres ayant des demandes en attente (page « À rembourser », caissiers)
// JSON : organisation_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'cashier');

json_success([
    'data' => (new RepaymentRequestManager($mysqlClient, $organisation, $user))->getMembersWithPendingRequests(),
    'currency' => $organisation->getCurrency(),
]);
