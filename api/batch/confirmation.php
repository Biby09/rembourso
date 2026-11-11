<?php
// PDF de confirmation d'un lot (page « Historique », caissiers) : récapitulatif, puis une page par quittance.
// GET : batch_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/batch-confirmation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/batch/repaymentBatchManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Fpdi;

$batchId = api_id($_GET['batch_id'] ?? null, 'Lot de remboursement non trouvé.');
$organisation = api_find(fn() => $organisationManager->getOrganisationOfBatch($batchId), 'Lot de remboursement non trouvé.');
api_organisation($organisationManager, $organisation->getId(), 'cashier');

$batch = api_find(fn() => (new RepaymentBatchManager($mysqlClient, $organisation))->getBatchById($batchId), 'Lot de remboursement non trouvé.');
$repaymentRequestManager = new RepaymentRequestManager($mysqlClient, $organisation, $user);
$requests = $repaymentRequestManager->getRequestsOfBatch($batch->getId());

$logoPath = $_SERVER['DOCUMENT_ROOT'] . '/media/logo.svg';
$logoDataUri = file_exists($logoPath) ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath)) : '';
$documentName = date('Y-m-d-H-i-s', strtotime($batch->getDate())) . '-rembourso_confirmation';

$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', false);

$header = getHeader($logoDataUri);
$footer = getFooter($user);

/**
 * Page HTML complète (en-tête, contenu, pied de page) rendue en PDF par Dompdf.
 */
function render_pdf(Options $options, string $title, string $css, string $body, string $orientation): string
{
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml('<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>' . e($title) . '</title>'
        . '<style>' . $css . '</style></head><body>' . $body . '</body></html>');
    $dompdf->setPaper('A4', $orientation);
    $dompdf->render();
    return $dompdf->output();
}

// Membre à qui appartiennent les demandes (null s'il a été supprimé entre-temps)
try {
    $member = $batch->getBeneficiaryId() ? $userManager->getUserById($batch->getBeneficiaryId()) : null;
} catch (Exception) {
    $member = null;
}

// Page de confirmation (paysage)
$main = getMain($batch, $organisation, $requests, $member);
$pdfOutput = render_pdf($options, $documentName, getCommonCss() . $header['css'] . $main['css'] . $footer['css'],
    $header['html'] . $main['html'] . $footer['html'], 'landscape');

// Une page par demande, avec ses quittances (images), dans l'ordre du tableau
$imageMimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
$receiptCss = '.receipt-image { max-width: 100%; max-height: 70%; display: inline-block; margin: 40px 0; }
    .receipt-image-container { width: 100%; text-align: center; }';
$tempReceiptPdfs = [];

foreach (array_values($requests) as $index => $request) {
    $imagesHtml = '';
    foreach ($repaymentRequestManager->getReceipts($request) as $receipt) {
        $receiptPath = $_SERVER['DOCUMENT_ROOT'] . $receipt['path'];
        $mime = $imageMimeTypes[strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION))] ?? null;
        if ($mime && file_exists($receiptPath)) {
            $imagesHtml .= '<img src="data:' . $mime . ';base64,' . base64_encode(file_get_contents($receiptPath)) . '" class="receipt-image" alt="Quittance">';
        }
    }
    if ($imagesHtml === '') {
        continue;
    }

    $number = $index + 1;
    $body = $header['html'] . '<h2>Quittance N°' . $number . ' : ' . e($request->getLabel()) . '</h2>'
        . '<div class="receipt-image-container">' . $imagesHtml . '</div>' . $footer['html'];

    $tempFile = tempnam(sys_get_temp_dir(), 'receipt_');
    file_put_contents($tempFile, render_pdf($options, 'Quittance #' . $number, getCommonCss() . $receiptCss . $header['css'] . $footer['css'], $body, 'portrait'));
    $tempReceiptPdfs[] = $tempFile;
}

// Fusion : page de confirmation puis quittances, dans l'ordre
if ($tempReceiptPdfs) {
    $tempMainFile = tempnam(sys_get_temp_dir(), 'dompdf_');
    file_put_contents($tempMainFile, $pdfOutput);

    try {
        $fpdi = new Fpdi();
        foreach (array_merge([$tempMainFile], $tempReceiptPdfs) as $file) {
            $pageCount = $fpdi->setSourceFile($file);
            for ($page = 1; $page <= $pageCount; $page++) {
                $templateId = $fpdi->importPage($page);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->addPage($size['orientation'] === 'L' ? 'L' : 'P', [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }
        }
        // 'S' : renvoie le PDF sous forme de texte, sans que Fpdi envoie ses propres en-têtes
        $pdfOutput = $fpdi->output('S');
    } finally {
        foreach (array_merge($tempReceiptPdfs, [$tempMainFile]) as $file) {
            @unlink($file);
        }
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $documentName . '.pdf"');
echo $pdfOutput;
