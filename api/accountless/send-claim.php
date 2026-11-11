<?php
// Envoie à une personne sans compte le lien pour créer son compte (ou rattacher ses demandes à son compte existant).
// Page « À rembourser », caissiers. Au plus 5 envois par heure et par personne.
// JSON : organisation_id, person_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/accountless.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/accountClaim/accountClaimManager.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'cashier');
$person = accountless_load($organisationManager, $userManager, $organisation, $input['person_id'] ?? null);

if (!$person->getEmail()) {
    json_error("Renseignez d'abord l'adresse e-mail de cette personne.");
}

rate_limit_consume('account_claim_person', (string) $person->getId(), 5, 3600);

try {
    (new AccountClaimManager($mysqlClient, $userManager))->createClaim($person, $organisation);
} catch (Exception $e) {
    log_server_exception($e, "Erreur lors de l'envoi d'un lien de création de compte");
    json_error("Impossible d'envoyer l'e-mail.", 500);
}

json_success(['sent_at' => date('Y-m-d')]);
