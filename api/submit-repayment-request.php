<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if ($_SERVER['SERVER_NAME'] === 'localhost') {
    putenv('PATH=' . getenv('PATH') . ':/opt/homebrew/bin:/usr/local/bin');
}


if (!userConnected()) {
    header('Location: /login?redirect=/dashboard/organisation/?id=' . ($_GET['id'] ?? ''));
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    session_destroy();
    header('Location: /login?redirect=/dashboard/organisation' . ($_GET['id'] ?? ''));
    exit;
}

verify_csrf_request(null, true);

if (!isset($_POST['organisation-id']) || !is_numeric($_POST['organisation-id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid organisation ID']);
    exit;
}
try {
    $organisation = new Organisation($_POST['organisation-id']);
} catch (Exception $e) {
    log_server_exception($e, 'Organisation introuvable lors du dépôt d’un remboursement');
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Organisation non trouvée']);
    exit;
}

if (!$organisation->isMember($user->id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}
//Validation des données du formulaire
if (empty($_POST['label']) || empty($_POST['amount']) || !is_numeric($_POST['amount']) || empty($_POST['date']) || empty($_FILES['receipts'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields and upload a receipt']);
    exit;
}

// Vérification que la catégorie et la sous-catégorie appartiennent à l'organisation

if ($organisation->use_cat && count($organisation->getCategories()) > 0) {
    if (!isset($_POST['category']) || !is_numeric($_POST['category'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category']);
        exit;
    }

    try {
        $category = new Category($_POST['category']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category']);
        exit;
    }

    if ($category->organisation_id != $organisation->id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category']);
        exit;
    }

    if ($organisation->use_subcat && count($category->getSubcategories()) > 0) {
        try {
            $subcategory = new Subcategory($_POST['subcategory']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid subcategory']);
            exit;
        }

        if ($subcategory->category_id != $category->id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid subcategory']);
            exit;
        }
    } else {
        // Pas de sous-catégorie disponible pour cette catégorie
        $subcategory = null;
    }

} else {
    $category = null;
    $subcategory = null;
}

if ($_POST['date'] > date('Y-m-d')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date', 'message' => 'La date de la dépense ne peut pas être dans le futur.']);
    exit;
}

// Traitement des quittances
$receipts = [];

if (isset($_FILES['receipts'])) {

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/receipts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    foreach ($_FILES['receipts']['tmp_name'] as $index => $fileTmpPath) {
        if ($_FILES['receipts']['error'][$index] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to upload receipt']);
            exit;
        }

        if (!is_uploaded_file($fileTmpPath)) {
            die('Upload invalide');
        }

        if ($_FILES['receipts']['size'][$index] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB', 'message' => 'Chaque fichier est limité à 5MB.']);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileTmpPath);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($mime, $allowedMimeTypes, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid file type', 'message' => 'Type de fichier non autorisé. Acceptés: images (PNG, JPEG, WebP, GIF) et PDF']);
            exit;
        }

        if ($mime !== 'application/pdf' && @getimagesize($fileTmpPath) === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid image', 'message' => 'Le fichier image est invalide.']);
            exit;
        }

        $extension = $mime === 'application/pdf' ? 'pdf' : ($mime === 'image/png' ? 'png' : 'jpg');

    // Si le document est un pdf, on le convertit en image png

        if ($extension === 'pdf') {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($fileTmpPath);
            foreach ($imagick as $page) {
                $page->setImageFormat('png');
                $fileName = bin2hex(random_bytes(16)) . '.png';
                $filePath = $uploadDir . $fileName;
                $page->writeImage($filePath);
                $receipts[] = ['path' => '/uploads/receipts/' . $fileName, 'original_name' => $_FILES['receipts']['name'][$index] . ' - page ' . ($page->getIteratorIndex() + 1)];
            }
            $imagick->clear();
            $imagick->destroy();
        } else {
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($fileTmpPath, $filePath)) {
                $receipts[] = ['path' => '/uploads/receipts/' . $fileName, 'original_name' => $_FILES['receipts']['name'][$index]];
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to upload receipt']);
                exit;
            }
        }
    }
}

// Création de la demande de remboursement

if (!RepaymentRequest::create($_SESSION['user_id'], $_POST['organisation-id'], $_POST['label'], $_POST['amount'], $_POST['date'], $receipts, $category ? $category->id : null, $subcategory ? $subcategory->id : null)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create repayment request']);
    exit;
} else {
    http_response_code(200);
    echo json_encode(['success' => true]);
}