<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['organisation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de l\'organisation manquant']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Organisation non trouvée']);
    exit;
}

if (!$organisation->isAdmin($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

try {
    $response = [];
    $categories = $organisation->getCategories();

    foreach ($categories as $category) {
        $subcategories = $category->getSubcategories();
        $subcategoriesResponse = [];
        foreach ($subcategories as $subcategory) {
            $subcategoriesResponse[] = [
                'id' => $subcategory->id,
                'name' => htmlspecialchars($subcategory->name),
            ];
        }

        $response[] = [
            'id' => $category->id,
            'name' => htmlspecialchars($category->name),
            'subcategories' => $subcategoriesResponse,
        ];
    }
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des catégories']);
}