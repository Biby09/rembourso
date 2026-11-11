<?php
// Catégories et sous-catégories de l'organisation (page Paramètres, administrateurs)
// JSON : organisation_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'admin');

try {
    $categories = (new CategoryManager($mysqlClient, $organisation))->getCategoriesWithSubcategories();
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la récupération des catégories');
    json_error('Erreur lors de la récupération des catégories.', 500);
}

json_success(['data' => $categories]);
