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
    log_server_exception($e, 'Utilisateur introuvable lors de la mise à jour des paramètres');
    session_destroy();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur interne']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['organisation_id'], $input['setting'], $input['value'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid organisation ID, setting or value']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
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

if (!in_array($input['setting'], ['use_cat', 'use_subcat'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid setting']);
    exit;
}

if (!is_bool($input['value'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Value must be a boolean']);
    exit;
}

if ($input['setting'] === 'use_subcat' && $input['value'] && !$organisation->use_cat) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cannot enable subcategories when categories are disabled']);
    exit;
}

if ($input['setting'] === 'use_cat') {
    if ($input['value']) {
        $organisation->enableCategories();
        http_response_code(200);
        echo json_encode(['success' => true, 'categories' => $organisation->use_cat, 'subcategories' => $organisation->use_subcat]);
    } else {
        $organisation->disableCategories();
        http_response_code(200);
        echo json_encode(['success' => true, 'categories' => $organisation->use_cat, 'subcategories' => $organisation->use_subcat]);
    }
} elseif ($input['setting'] === 'use_subcat') {
    if ($input['value']) {
        $organisation->enableSubcategories();
        http_response_code(200);
        echo json_encode(['success' => true, 'categories' => $organisation->use_cat, 'subcategories' => $organisation->use_subcat]);
    } else {
        $organisation->disableSubcategories();
        http_response_code(200);
        echo json_encode(['success' => true, 'categories' => $organisation->use_cat, 'subcategories' => $organisation->use_subcat]);
    }

}else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid setting']);
}