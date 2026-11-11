<?php
// Ajout ('add') ou suppression ('delete') d'une catégorie (page Paramètres, administrateurs).
// Le nom et les sous-catégories se modifient avec update-category.php.
// JSON : action, organisation_id + name (add) | category_id (delete)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

$input = api_json_input();

switch ($input['action'] ?? null) {
    case 'add':
        $organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'admin');
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 64) {
            json_error('Le nom de la catégorie est requis (64 caractères maximum).');
        }

        try {
            $category = (new CategoryManager($mysqlClient, $organisation))->createCategory($name);
        } catch (Exception $e) {
            log_server_exception($e, "Erreur lors de l'ajout d'une catégorie");
            json_error("Erreur lors de l'ajout de la catégorie.", 500);
        }
        json_success(['category_id' => $category->getId()]);

    case 'delete':
        $categoryId = api_id($input['category_id'] ?? null, 'Catégorie non trouvée.');
        $organisation = api_find(fn() => $organisationManager->getOrganisationOfCategory($categoryId), 'Catégorie non trouvée.');
        api_organisation($organisationManager, $organisation->getId(), 'admin');

        $categoryManager = new CategoryManager($mysqlClient, $organisation);
        $category = api_find(fn() => $categoryManager->getCategoryById($categoryId), 'Catégorie non trouvée.');

        try {
            $categoryManager->deleteCategory($category);
        } catch (Exception $e) {
            log_server_exception($e, "Erreur lors de la suppression d'une catégorie");
            json_error('Erreur lors de la suppression de la catégorie.', 500);
        }
        json_success();

    default:
        json_error('Action invalide.');
}
