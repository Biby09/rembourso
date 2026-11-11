<?php
// Personnes sans compte de l'organisation (page « À rembourser », caissiers)
// JSON : organisation_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/accountless.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'cashier');

json_success(['data' => array_map(
    fn(array $row) => accountless_person_data($row['user'], $row['last_claim_sent_at']),
    $organisationManager->getAccountlessMembers($organisation)
)]);
