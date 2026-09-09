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


if (!in_array($input['action'], ['update', 'delete', 'add'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Action invalide']);
    exit;
}

if (in_array($input['action'], ['update', 'delete']) && !isset($input['category_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de la catégorie manquant pour l\'action ' . $input['action']]);
    exit;
} elseif (isset($input['category_id'])) {
    try {
        $category = new Category($input['category_id']);
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode(['error' => 'Catégorie non trouvée']);
        exit;
    }

}

try {
    if (isset($category)) {
        $organisation = new Organisation($category->organisation_id);
    } elseif (isset($input['organisation_id'])) {
        $organisation = new Organisation($input['organisation_id']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID de l\'organisation manquant pour l\'action ' . $input['action']]);
        exit;
    }
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
    if ($input['action'] === 'update') {
        if (!isset($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nom de la catégorie manquant']);
            exit;
        }

        if ($category->updateName($input['name'])) {
            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la mise à jour de la catégorie']);
        }
    } elseif ($input['action'] === 'delete') {
        if ($category->delete()) {
            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression de la catégorie']);
        }

    } elseif ($input['action'] === 'add') {
        if (!isset($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nom de la catégorie manquant']);
            exit;
        }
$category = $organisation->addCategory($input['name']);
        if ($category) {
            http_response_code(200);
            echo json_encode(['success' => true, 'category_id' => $category->id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'ajout de la catégorie']);
        }


    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Action invalide']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la mise à jour de la catégorie']);
    exit;
}