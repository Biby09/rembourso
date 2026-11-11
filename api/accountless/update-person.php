<?php
// Modification des informations d'une personne sans compte (page « À rembourser », caissiers)
// JSON : organisation_id, person_id, person { first_name, last_name, address, postal_code, city, country, iban, email }

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/accountless.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'cashier');
$person = accountless_load($organisationManager, $userManager, $organisation, $input['person_id'] ?? null);

$data = accountless_person_input($input['person'] ?? null);
accountless_assert_email_usable($data['email'], $organisation, $organisationManager, $userManager, $person->getId());

try {
    $person = $userManager->updateAccountless($person, $data);
} catch (Exception $e) {
    log_server_exception($e, "Erreur lors de la modification d'une personne sans compte");
    json_error('Erreur lors de la modification.', 500);
}

json_success(['person' => accountless_person_data($person)]);
