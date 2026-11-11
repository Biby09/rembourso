<?php
// Sous-catégories d'une catégorie (formulaires de demande, scripts/components/subcategory-select.js)
// JSON : categoryId

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

$input = api_json_input();
$categoryId = api_id($input['categoryId'] ?? null, 'Catégorie non trouvée.');

// Vérifie que l'utilisateur est membre de l'organisation de la catégorie
$organisation = api_find(fn() => $organisationManager->getOrganisationOfCategory($categoryId), 'Catégorie non trouvée.');
$categoryManager = new CategoryManager($mysqlClient, $organisation);
$category = api_find(fn() => $categoryManager->getCategoryById($categoryId), 'Catégorie non trouvée.');

json_success([
    'subcategories' => array_map(
        fn(Subcategory $subcategory) => ['id' => $subcategory->getId(), 'name' => $subcategory->getName()],
        $categoryManager->getSubcategories($category)
    ),
    'categoryName' => $category->getName(),
]);
