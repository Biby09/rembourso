<?php
/**
 * Gestion des quittances uploadées (validation, conversion PDF -> PNG, stockage).
 * Utilisé par le dépôt et la modification d'une demande de remboursement.
 */

const RECEIPT_MAX_SIZE = 5 * 1024 * 1024; // Doit rester aligné avec scripts/components/receipt-files.js
const RECEIPT_UPLOAD_DIR = '/uploads/receipts/';
const RECEIPT_ALLOWED_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/pdf' => 'pdf',
];

class ReceiptUploadException extends Exception
{
}

/**
 * Transforme la structure $_FILES['x'] (tableaux parallèles) en une liste de fichiers.
 * Les entrées vides (aucun fichier sélectionné) sont ignorées.
 */
function normalize_uploaded_files(?array $files): array
{
    if (empty($files) || !isset($files['tmp_name'])) {
        return [];
    }

    $normalized = [];
    foreach ((array) $files['tmp_name'] as $index => $tmpName) {
        $error = ((array) $files['error'])[$index] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $normalized[] = [
            'name' => ((array) $files['name'])[$index] ?? 'quittance',
            'tmp_name' => $tmpName,
            'error' => $error,
            'size' => ((array) $files['size'])[$index] ?? 0,
        ];
    }

    return $normalized;
}

/**
 * Valide et enregistre les quittances uploadées.
 * Les PDF sont convertis en une image PNG par page.
 *
 * @return array<int, array{path: string, original_name: string}>
 * @throws ReceiptUploadException avec un message affichable à l'utilisateur
 */
function store_uploaded_receipts(?array $files): array
{
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . RECEIPT_UPLOAD_DIR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $stored = [];

    try {
        foreach (normalize_uploaded_files($files) as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                throw new ReceiptUploadException("L'envoi d'une quittance a échoué.");
            }

            if ($file['size'] > RECEIPT_MAX_SIZE) {
                throw new ReceiptUploadException('Chaque fichier est limité à 5 Mo.');
            }

            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $extension = RECEIPT_ALLOWED_TYPES[$mimeType] ?? null;
            if ($extension === null) {
                throw new ReceiptUploadException('Type de fichier non autorisé. Formats acceptés : PNG, JPEG et PDF.');
            }

            if ($extension !== 'pdf' && @getimagesize($file['tmp_name']) === false) {
                throw new ReceiptUploadException('Le fichier image est invalide.');
            }

            if ($extension === 'pdf') {
                array_push($stored, ...convert_pdf_receipt($file, $uploadDir));
                continue;
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                throw new ReceiptUploadException("L'enregistrement d'une quittance a échoué.");
            }
            $stored[] = ['path' => RECEIPT_UPLOAD_DIR . $fileName, 'original_name' => $file['name']];
        }
    } catch (Throwable $exception) {
        // Ne pas laisser de fichiers orphelins si un fichier du lot est refusé.
        delete_receipt_files(array_column($stored, 'path'));

        if ($exception instanceof ReceiptUploadException) {
            throw $exception;
        }
        log_server_exception($exception, "Erreur lors de l'enregistrement d'une quittance");
        throw new ReceiptUploadException("L'enregistrement des quittances a échoué.");
    }

    return $stored;
}

/**
 * Convertit chaque page d'un PDF en PNG.
 */
function convert_pdf_receipt(array $file, string $uploadDir): array
{
    if (!extension_loaded('imagick')) {
        error_log("Conversion PDF impossible : l'extension PHP imagick n'est pas chargée.");
        throw new ReceiptUploadException("Les PDF ne peuvent pas être traités pour le moment. Envoyez la quittance en PNG ou JPEG.");
    }

    $receipts = [];
    $imagick = new Imagick();
    $imagick->setResolution(150, 150);
    $imagick->readImage($file['tmp_name']);

    foreach ($imagick as $page) {
        $page->setImageFormat('png');
        $fileName = bin2hex(random_bytes(16)) . '.png';
        $page->writeImage($uploadDir . $fileName);
        $receipts[] = [
            'path' => RECEIPT_UPLOAD_DIR . $fileName,
            'original_name' => $file['name'] . ' - page ' . ($page->getIteratorIndex() + 1),
        ];
    }

    $imagick->clear();
    $imagick->destroy();

    return $receipts;
}

/**
 * Supprime des fichiers de quittance (chemins relatifs à la racine du site).
 */
function delete_receipt_files(array $paths): void
{
    foreach (array_unique(array_filter($paths)) as $path) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
