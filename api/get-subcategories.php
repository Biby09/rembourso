<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur de récupération des sous-catégories');
    session_destroy();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur interne']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['categoryId']) || !is_numeric($input['categoryId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
    exit;
}
try{
    $category = new Category($input['categoryId']);
}catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Catégorie non trouvée']);
    exit;
}

try {
    $organisation = new Organisation($category->organisation_id);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Organisation non trouvée']);
    exit;
}

if (!$organisation->isMember($user->id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

try {
    $subcategories = $category->getSubcategories();

    $subcatArray = array_map(function ($subcat) {
        return ['id' => $subcat->id, 'name' => htmlspecialchars($subcat->name)];
    }, $subcategories);

    echo json_encode(['success' => true, 'subcategories' => $subcatArray, 'categoryName' => htmlspecialchars($category->name)]);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur de récupération des sous-catégories');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur interne']);
    exit;
}


