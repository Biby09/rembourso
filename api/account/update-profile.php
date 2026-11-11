<?php
// Informations personnelles (page Mon compte, scripts/pages/account.js)
// POST : firstName, lastName, email, address, postalCode, city, country

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

api_require_method('POST');
$input = api_form_input();

$fields = [];
foreach (['firstName', 'lastName', 'email', 'address', 'postalCode', 'city', 'country'] as $field) {
    $fields[$field] = trim((string) ($input[$field] ?? ''));
    if ($fields[$field] === '') {
        json_error('Tous les champs sont requis.');
    }
}

if (filter_var($fields['email'], FILTER_VALIDATE_EMAIL) === false) {
    json_error('Adresse e-mail invalide.');
}
// Nouvelle adresse : elle ne doit pas appartenir à un autre compte
if (strcasecmp($fields['email'], (string) $user->getEmail()) !== 0 && !$userManager->isEmailAvailable($fields['email'])) {
    json_error('Cette adresse e-mail est déjà utilisée par un autre compte.');
}

try {
    $userManager->updateProfile($user, $fields['firstName'], $fields['lastName'], $fields['email'],
        $fields['address'], $fields['postalCode'], $fields['city'], $fields['country']);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la mise à jour du profil');
    json_error('Erreur lors de la mise à jour du profil.', 500);
}

json_success(['message' => 'Profil mis à jour avec succès']);
