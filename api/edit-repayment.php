<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

verify_csrf_request(null, true);

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try{
    $user = new User($_SESSION['user_id']);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

if (!isset($_POST['repayment_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid repayment ID']);
    exit;
}

try{
    $repayment = new RepaymentRequest($_POST['repayment_id']);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'Repayment request not found']);
    exit;
}

try{
    $organisation = new Organisation($repayment->organisation_id);
}catch(Exception $e){
    echo json_encode(['success' => false, 'error' => 'Organisation not found']);
    exit;
}

//Controler que la personne soit dans l'organisation
if (!$organisation->isMember($user->id)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
//Controler que la personne soit le créateur de la demande
if (!$repayment->isEditable()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized', 'message' => 'Seule une demande de remboursement en attente peut être modifiée, et seulement par son créateur.']);
    exit;
}

//Controler les données reçues
if (!isset($_POST['label'],$_POST['amount'],$_POST['date'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

if ($repayment->category !== null) {
    if (!isset($_POST['category'])) {
        echo json_encode(['success' => false, 'error' => 'Category is required']);
        exit;
    }
    $category = null;
    try{
        $category = new Category($_POST['category']);
    }catch(Exception $e){
        echo json_encode(['success' => false, 'error' => 'Category not found']);
        exit;
    }
    if ((int) $category->organisation_id !== (int) $organisation->id) {
        echo json_encode(['success' => false, 'error' => 'Category does not belong to organisation']);
        exit;
    }
    if (count($category->getSubcategories()) > 0) {
        if (empty($_POST['subcategory'])) {
            echo json_encode(['success' => false, 'error' => 'Subcategory is required']);
            exit;
        }
        $subcategory = null;
        try{
            $subcategory = new Subcategory($_POST['subcategory']);
        }catch(Exception $e){
            echo json_encode(['success' => false, 'error' => 'Subcategory not found']);
            exit;
        }
        if ($subcategory->category_id != $category->id) {
            echo json_encode(['success' => false, 'error' => 'Subcategory does not belong to category']);
            exit;
        }
    }
}

$receipts = [];
$retainedReceiptIds = $_POST['retained_receipt_ids'] ?? [];
foreach ($repayment->getReceipts() as $receipt) {
    $receiptId = $receipt['id'] === null ? 'legacy' : (string) $receipt['id'];
    if (in_array($receiptId, $retainedReceiptIds, true)) {
        $receipts[] = ['path' => $receipt['path'], 'original_name' => $receipt['original_name']];
    }
}

if (isset($_FILES['receipts']) && !empty($_FILES['receipts']['name'][0])) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/receipts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    foreach ($_FILES['receipts']['tmp_name'] as $index => $fileTmpPath) {
        if ($_FILES['receipts']['error'][$index] !== UPLOAD_ERR_OK || !is_uploaded_file($fileTmpPath)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload receipt']);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpPath);
        unset($finfo);
        if ($_FILES['receipts']['size'][$index] > 5 * 1024 * 1024 || !in_array($mimeType, $allowedMimeTypes, true) || ($mimeType !== 'application/pdf' && @getimagesize($fileTmpPath) === false)) {
            echo json_encode(['success' => false, 'error' => 'Invalid receipt file']);
            exit;
        }

        $extension = $mimeType === 'application/pdf' ? 'pdf' : ($mimeType === 'image/png' ? 'png' : 'jpg');

        if ($extension === 'pdf') {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($fileTmpPath);
            foreach ($imagick as $page) {
                $page->setImageFormat('png');
                $fileName = bin2hex(random_bytes(16)) . '.png';
                $page->writeImage($uploadDir . $fileName);
                $receipts[] = ['path' => '/uploads/receipts/' . $fileName, 'original_name' => $_FILES['receipts']['name'][$index] . ' - page ' . ($page->getIteratorIndex() + 1)];
            }
            $imagick->clear();
            $imagick->destroy();
        } else {
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            if (!move_uploaded_file($fileTmpPath, $uploadDir . $fileName)) {
                echo json_encode(['success' => false, 'error' => 'Failed to upload receipt']);
                exit;
            }
            $receipts[] = ['path' => '/uploads/receipts/' . $fileName, 'original_name' => $_FILES['receipts']['name'][$index]];
        }
    }
}

if (empty($receipts)) {
    echo json_encode(['success' => false, 'error' => 'At least one receipt is required']);
    exit;
}

if (isset($_POST['retained_receipt_ids']) || (isset($_FILES['receipts']) && !empty($_FILES['receipts']['name'][0]))) {
    $repayment->replaceReceipts($receipts);
}

$repayment->label = $_POST['label'];
$repayment->amount = $_POST['amount'];
$repayment->transaction_date = new DateTime($_POST['date']);
if ($repayment->category !== null) {
    $repayment->category = new Category($_POST['category']);
    $repayment->subcategory = (count($repayment->category->getSubcategories()) > 0 && !empty($_POST['subcategory']))
        ? new Subcategory($_POST['subcategory'])
        : null;
}

$repayment->amount = (int) $repayment->amount;
$repayment->transaction_date = new DateTime($_POST['date']);

try {
    $repayment->save();
    echo json_encode(['success' => true, 'updated_repayment' => $repayment->getSafeData(), 'currency' => $organisation->currency]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to save repayment request']);
}
