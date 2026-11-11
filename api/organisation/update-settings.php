<?php
// Active / désactive les catégories ('use_cat') ou les sous-catégories ('use_subcat') (page Paramètres)
// JSON : organisation_id, setting, value (booléen)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'admin');

$value = $input['value'] ?? null;
if (!is_bool($value)) {
    json_error('La valeur doit être un booléen.');
}

$organisation = match ($input['setting'] ?? null) {
    'use_cat' => $organisationManager->setCategoriesEnabled($organisation, $value),
    // Activer les sous-catégories active aussi les catégories
    'use_subcat' => $organisationManager->setSubcategoriesEnabled($organisation, $value),
    default => json_error('Paramètre invalide.'),
};

json_success([
    'categories' => $organisation->areCategoriesEnabled(),
    'subcategories' => $organisation->areSubcategoriesEnabled(),
]);
