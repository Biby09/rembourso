<?php
// Met à jour le nom d'une catégorie et applique une liste de modifications de sous-catégories
// (page Paramètres, administrateurs). Tout ou rien.
// JSON : category_id, name, subcategories : [{ action: 'add', name }, { action: 'update', id, name }, { action: 'delete', id }]

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

$input = api_json_input();
$categoryId = api_id($input['category_id'] ?? null, 'Catégorie non trouvée.');
$organisation = api_find(fn() => $organisationManager->getOrganisationOfCategory($categoryId), 'Catégorie non trouvée.');
api_organisation($organisationManager, $organisation->getId(), 'admin');

$categoryManager = new CategoryManager($mysqlClient, $organisation);
$category = api_find(fn() => $categoryManager->getCategoryById($categoryId), 'Catégorie non trouvée.');

$name = trim((string) ($input['name'] ?? ''));
$changes = $input['subcategories'] ?? [];

if ($name === '' || mb_strlen($name) > 64) {
    json_error('Le nom de la catégorie est requis (64 caractères maximum).');
}
if (!is_array($changes)) {
    json_error('Liste de sous-catégories invalide.');
}

/**
 * Sous-catégorie de la catégorie modifiée (sinon 403 : on ne touche pas aux sous-catégories d'une autre catégorie).
 */
function owned_subcategory(CategoryManager $categoryManager, Category $category, array $change): Subcategory
{
    $subcategory = api_find(fn() => $categoryManager->getSubcategoryById(api_id($change['id'] ?? null, 'Sous-catégorie non trouvée.')), 'Sous-catégorie non trouvée.');
    if ($subcategory->getCategoryId() !== $category->getId()) {
        json_error("Cette sous-catégorie n'appartient pas à la catégorie.", 403);
    }
    return $subcategory;
}

function subcategory_name(array $change): string
{
    $name = trim((string) ($change['name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 64) {
        json_error("Le nom d'une sous-catégorie est requis (64 caractères maximum).");
    }
    return $name;
}

// Validation de toutes les modifications avant d'enregistrer quoi que ce soit
// (fn() => … : fonction exécutée plus tard, dans la transaction)
$operations = [];
foreach ($changes as $change) {
    $change = is_array($change) ? $change : [];
    switch ($change['action'] ?? null) {
        case 'add':
            $newName = subcategory_name($change);
            $operations[] = fn() => $categoryManager->createSubcategory($category, $newName);
            break;
        case 'update':
            $subcategory = owned_subcategory($categoryManager, $category, $change);
            $newName = subcategory_name($change);
            $operations[] = fn() => $categoryManager->renameSubcategory($subcategory, $newName);
            break;
        case 'delete':
            $subcategory = owned_subcategory($categoryManager, $category, $change);
            $operations[] = fn() => $categoryManager->deleteSubcategory($subcategory);
            break;
        default:
            json_error('Action de sous-catégorie invalide.');
    }
}

try {
    $mysqlClient->beginTransaction();
    if ($category->getName() !== $name) {
        $categoryManager->renameCategory($category, $name);
    }
    foreach ($operations as $operation) {
        $operation();
    }
    $mysqlClient->commit();
} catch (Exception $e) {
    $mysqlClient->rollBack();
    log_server_exception($e, 'Erreur de mise à jour de catégorie');
    json_error('Erreur lors de la mise à jour de la catégorie.', 500);
}

json_success();
