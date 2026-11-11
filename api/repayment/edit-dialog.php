<?php
// Renvoie le HTML du modal de modification d'une demande (includes/modals/edit-repayment-modal.php)
// JSON : repayment_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/repayment-api.php';

$input = api_json_input();
[$organisation, $repaymentRequestManager, $request] = repayment_load_editable($organisationManager, $mysqlClient, $user, $input['repayment_id'] ?? null);

$categoryManager = new CategoryManager($mysqlClient, $organisation);
$categories = $organisation->areCategoriesEnabled() ? $categoryManager->getCategories() : [];
$subcategories = [];
if ($categories && $request->getCategoryId()) {
    try {
        $subcategories = $categoryManager->getSubcategories($categoryManager->getCategoryById($request->getCategoryId()));
    } catch (Exception) {
        // Catégorie désactivée depuis : la personne en choisira une autre
    }
}
$receipts = $repaymentRequestManager->getReceipts($request);

header('Content-Type: text/html; charset=utf-8');
include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/edit-repayment-modal.php';
