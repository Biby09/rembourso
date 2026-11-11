<?php
// Fichier d'une quittance (image), après contrôle des droits. Erreurs en JSON, fichier en binaire.
// GET : repayment_id, receipt_index (0 par défaut), format=download (téléchargement)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

$requestId = api_id($_GET['repayment_id'] ?? null, 'Quittance non trouvée.');
$organisation = api_find(fn() => $organisationManager->getOrganisationOfRequest($requestId), 'Quittance non trouvée.');
$repaymentRequestManager = new RepaymentRequestManager($mysqlClient, $organisation, $user);
$request = api_find(fn() => $repaymentRequestManager->getRequestById($requestId), 'Quittance non trouvée.');

// Le bénéficiaire, l'auteur de la saisie et les caissiers / admins de l'organisation peuvent voir la quittance
$isOwner = $request->getUserId() === $user->getId() || $request->getCreatedBy() === $user->getId();
if (!$isOwner && !$organisationManager->isCashier($organisation)) {
    json_error('Accès refusé.', 403);
}

$receiptIndex = isset($_GET['receipt_index']) && ctype_digit((string) $_GET['receipt_index']) ? (int) $_GET['receipt_index'] : 0;
$receiptPath = $repaymentRequestManager->getReceipts($request)[$receiptIndex]['path'] ?? null;

// Le fichier doit se trouver dans le dossier des quittances (pas de « ../ » vers un autre fichier du serveur)
$uploadRoot = realpath($_SERVER['DOCUMENT_ROOT'] . RECEIPT_UPLOAD_DIR);
$filePath = $receiptPath ? realpath($_SERVER['DOCUMENT_ROOT'] . $receiptPath) : false;
if ($uploadRoot === false || $filePath === false || !str_starts_with($filePath, $uploadRoot . DIRECTORY_SEPARATOR) || !is_readable($filePath)) {
    json_error('Quittance non trouvée.', 404);
}

$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
    json_error('Type de quittance non supporté.', 415);
}

$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$filename = 'receipt_' . $request->getId() . ($extension ? '.' . $extension : '');
$disposition = ($_GET['format'] ?? '') === 'download' ? 'attachment' : 'inline';

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-store');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', filemtime($filePath)));
header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');

readfile($filePath);
