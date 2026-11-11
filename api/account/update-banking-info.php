<?php
// Coordonnées bancaires et bénéficiaire des remboursements (page Mon compte, scripts/pages/account.js)
// POST : iban, refund_first_name, refund_last_name, refund_address, refund_postal_code, refund_city, refund_country

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

api_require_method('POST');
$input = api_form_input();

$iban = iban_to_machine_format((string) ($input['iban'] ?? ''));
if (!verify_iban($iban)) {
    json_error('IBAN invalide.');
}

// Infos du bénéficiaire (saisies, ou recopiées des données personnelles côté navigateur)
$refund = [];
foreach (['first_name', 'last_name', 'address', 'postal_code', 'city', 'country'] as $field) {
    $refund[$field] = trim((string) ($input['refund_' . $field] ?? ''));
    if ($refund[$field] === '') {
        json_error('Toutes les informations du bénéficiaire sont requises.');
    }
}

try {
    $userManager->updateBankingInfo($user, $iban, $refund);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la mise à jour des informations bancaires');
    json_error('Erreur lors de la mise à jour des informations bancaires.', 500);
}

json_success(['message' => 'Informations bancaires mises à jour']);
