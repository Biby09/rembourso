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
    log_server_exception($e, 'Utilisateur introuvable lors de la mise à jour d’une catégorie');
    session_destroy();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur interne']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['category_id'],$input['name'],$input['subcategories'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid category ID, name or subcategories']);
    exit;
}

try{
    $category = new Category($input['category_id']);
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

if (!$organisation->isAdmin($user->id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

if (empty($input['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Category name cannot be empty']);
    exit;
}

if (!is_array($input['subcategories'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Subcategories must be an array']);
    exit;
}

if ($category->name !== $input['name']) {
    try {
        $category->updateName($input['name']);
    } catch (Exception $e) {
        log_server_exception($e, 'Erreur de mise à jour de catégorie');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour de la catégorie']);
        exit;
    }
}

foreach ($input['subcategories'] as $subcat) {
    if (!isset($subcat['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Subcategory action is required']);
        exit;
    }

    if ($subcat['action'] === 'update') {

        if (!isset($subcat['id']) || !is_numeric($subcat['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid subcategory ID for update']);
            exit;
        }

        if (!isset($subcat['name']) || empty($subcat['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Subcategory name cannot be empty for update']);
            exit;
        }

        try{
            $subcategory = new Subcategory($subcat['id']);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Sous-catégorie non trouvée']);
            exit;
        }

        if ((int) $subcategory->category_id !== (int) $category->id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Subcategory does not belong to this category']);
            exit;
        }

        try {
            $subcategory->updateName($subcat['name']);
        } catch (Exception $e) {
            log_server_exception($e, 'Erreur de mise à jour de sous-catégorie');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour de la sous-catégorie']);
            exit;
        }

    }elseif ($subcat['action'] === 'add') {

        if (!isset($subcat['name']) || empty($subcat['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Subcategory name cannot be empty for add']);
            exit;
        }

        try {
            $category->addSubcategory($subcat['name']);
        } catch (Exception $e) {
            log_server_exception($e, 'Erreur de création de sous-catégorie');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la création de la sous-catégorie']);
            exit;
        }
        
    }elseif ($subcat['action'] === 'delete') {

        if (!isset($subcat['id']) || !is_numeric($subcat['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid subcategory ID for delete']);
            exit;
        }

        try{
            $subcategory = new Subcategory($subcat['id']);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Sous-catégorie non trouvée']);
            exit;
        }

        if ((int) $subcategory->category_id !== (int) $category->id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Subcategory does not belong to this category']);
            exit;
        }

        try {
            $subcategory->delete();
        } catch (Exception $e) {
            log_server_exception($e, 'Erreur de suppression de sous-catégorie');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression de la sous-catégorie']);
            exit;
        }
       
       
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid subcategory action']);
        exit;
    }
}

http_response_code(200);
echo json_encode(['success' => true]);