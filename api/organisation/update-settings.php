<?php
// Réglages de l'organisation (page Paramètres et assistant de création) :
// - 'use_cat' / 'use_subcat' : active ou désactive les catégories / sous-catégories (value : booléen) ;
// - 'currency' : devise des demandes (value : 'CHF' ou 'EUR').
// JSON : organisation_id, setting, value

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'admin');

$setting = $input['setting'] ?? null;
$value = $input['value'] ?? null;

if ($setting === 'currency') {
    if (!is_string($value) || !isset(Organisation::CURRENCIES[$value])) {
        json_error('Devise non prise en charge.');
    }
    $organisation = $organisationManager->setCurrency($organisation, $value);
    json_success(['currency' => $organisation->getCurrency()]);
}

if (!is_bool($value)) {
    json_error('La valeur doit être un booléen.');
}

$organisation = match ($setting) {
    'use_cat' => $organisationManager->setCategoriesEnabled($organisation, $value),
    // Activer les sous-catégories active aussi les catégories
    'use_subcat' => $organisationManager->setSubcategoriesEnabled($organisation, $value),
    default => json_error('Paramètre invalide.'),
};

json_success([
    'categories' => $organisation->areCategoriesEnabled(),
    'subcategories' => $organisation->areSubcategoriesEnabled(),
]);
