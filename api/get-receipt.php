<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

//Controler que l'utilisateur a le droit de voir cette quittance

if (!userConnected()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
$user = new User($_SESSION['user_id']);

//Contoler que la demande de remboursement existe et que le get fonctionne

if (!isset($_GET['repayment_id']) || !ctype_digit((string) $_GET['repayment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request: repayment_id is required']);
    exit();
}
try {
    $repayment = new RepaymentRequest((int) $_GET['repayment_id']);
    $organisation = new Organisation($repayment->organisation_id);
} catch (Throwable $exception) {
    log_server_exception($exception, 'Impossible de charger le justificatif');
    http_response_code(404);
    echo json_encode(['error' => 'Receipt not found']);
    exit();
}

//Controler que l'utilisateur a le droit de voir cette demande de remboursement

if (!($repayment->user_id == $user->id || $organisation->isAdmin($user->id) || $organisation->isCashier($user->id))) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Retourner la quittance demandée, par ordre d'ajout
$receiptIndex = isset($_GET['receipt_index']) && ctype_digit($_GET['receipt_index']) ? (int) $_GET['receipt_index'] : 0;
$receiptPaths = $repayment->getReceiptPaths();
if (!isset($receiptPaths[$receiptIndex])) {
    http_response_code(404);
    echo json_encode(['error' => 'Receipt not found']);
    exit();
}

$receiptPath = $receiptPaths[$receiptIndex];
$uploadRoot = realpath($_SERVER['DOCUMENT_ROOT'] . '/uploads/receipts');
$filePath = realpath($_SERVER['DOCUMENT_ROOT'] . $receiptPath);
if ($uploadRoot === false || $filePath === false || !str_starts_with($filePath, $uploadRoot . DIRECTORY_SEPARATOR) || !is_readable($filePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Receipt not found']);
    exit();
}

$fileSize = filesize($filePath);
if ($fileSize === false) {
    http_response_code(404);
    echo json_encode(['error' => 'Receipt not found']);
    exit();
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
unset($finfo);

$allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
if ($mimeType === false || !in_array($mimeType, $allowedMimeTypes, true)) {
    http_response_code(415);
    echo json_encode(['error' => 'Unsupported receipt type']);
    exit();
}

$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$filename = 'receipt_' . $repayment->id . ($extension ? '.' . $extension : '');

// Définir les en-têtes HTTP
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Cache-Control: private, no-store');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', filemtime($filePath)));
header('X-Content-Type-Options: nosniff');

// Ouvre le document directement dans le navigateur, sauf si une demande explicite de téléchargement est faite
if (isset($_GET['format']) && $_GET['format'] === 'download') {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Disposition: inline; filename="' . $filename . '"');
}

readfile($filePath);