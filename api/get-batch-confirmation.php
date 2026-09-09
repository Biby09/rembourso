<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';
require $_SERVER['DOCUMENT_ROOT'] . '/config/batch-confirmation.php';
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if ($_SERVER['SERVER_NAME'] === 'localhost') {
    putenv('PATH=' . getenv('PATH') . ':/opt/homebrew/bin:/usr/local/bin');
}

use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Fpdi;

if (!userConnected()) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized access."));
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized access."));
    exit;
}

if (!isset($_GET['batch_id'])) {
    http_response_code(400);
    echo json_encode(array("message" => "Batch ID is required."));
    exit;
}

try {
    $batch = new RepaymentBatch($_GET['batch_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(array("message" => "Batch not found."));
    exit;
}

try {
    $organisation = new Organisation($batch->organisation_id);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(array("message" => "Organisation not found."));
    exit;
}

if (!$organisation->isCashier($user->id)) {
    http_response_code(403);
    echo json_encode(array("message" => "You do not have permission to view this batch."));
    exit;
}

$repayments = $batch->getRepaymentRequests();

$logoPath = $_SERVER['DOCUMENT_ROOT'] . '/media/logo.svg';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
}

$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$header = getHeader();
$main = getMain($batch, $organisation);
$footer = getFooter();

$html = '<!DOCTYPE html>' . "\n";
$html .= '<html lang="fr">' . "\n";
$html .= '<head>' . "\n";
$html .= '    <meta charset="UTF-8">' . "\n";
$html .= '    <title>' . $batch->date->format('Y-m-d-H-i-s') . '-rembourso_confirmation</title>' . "\n";
$html .= '    <style>' . "\n";
$html .= getCommonCss() . "\n";
$html .= $header['css'] . "\n";
$html .= $main['css'] . "\n";
$html .= $footer['css'] . "\n";
$html .= '    </style>' . "\n";
$html .= '</head>' . "\n";
$html .= '<body>' . "\n";
$html .= $header['html'] . "\n";
$html .= $main['html'] . "\n";
$html .= $footer['html'] . "\n";
$html .= '</body>' . "\n";
$html .= '</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$pdfOutput = $dompdf->output();

$imageMimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp'
];

// Une seule liste, dans l'ordre des remboursements
$tempReceiptPdfs = [];

$index = 1;
foreach ($repayments as $repayment) {
    // Construire la liste des images à afficher pour cette quittance
    $imagesToRender = [];
    foreach ($repayment->getReceiptPaths() as $storedReceiptPath) {
        $receiptPath = $_SERVER['DOCUMENT_ROOT'] . $storedReceiptPath;
        if (!file_exists($receiptPath)) {
            continue;
        }
        $ext = strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION));
        if (isset($imageMimeTypes[$ext])) {
            $imagesToRender[] = ['data' => base64_encode(file_get_contents($receiptPath)), 'mime' => $imageMimeTypes[$ext]];
        }
    }
    if (empty($imagesToRender)) {
        $index++;
        continue;
    }

    // Générer le HTML de quittance avec le même header/footer que les images
    $header = getHeader();
    $footer = getFooter();
    $css = getCommonCss();

    $imagesHtml = '';
    foreach ($imagesToRender as $img) {
        $imagesHtml .= '<img src="data:' . $img['mime'] . ';base64,' . $img['data'] . '" class="receipt-image" alt="Quittance">';
    }

    $imagesHtml = '<div class="receipt-image-container">' . $imagesHtml . '</div>';

    $imageHtml = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quittance #' . $index . '</title>
    <style>
' . $css . '
        .receipt-image {
            max-width: 100%;
            max-height: 70%;
            display: inline-block;
            margin: 40px 0;
        }
        .receipt-image-container {
            width: 100%;
            text-align: center;
        }
    ' . $header['css'] . $footer['css'] . '
    </style>
</head>
<body>
    ' . $header['html'] . '
    <h2>Quittance N°' . $index . ': ' . htmlspecialchars($repayment->label) . '</h2>
    ' . $imagesHtml . '
    ' . $footer['html'] . '
</body>
</html>';

    $receiptDompdf = new Dompdf($options);
    $receiptDompdf->loadHtml($imageHtml);
    $receiptDompdf->setPaper('A4', 'portrait');
    $receiptDompdf->render();

    $tempFile = tempnam(sys_get_temp_dir(), 'receipt_');
    file_put_contents($tempFile, $receiptDompdf->output());
    $tempReceiptPdfs[] = $tempFile;

    $index++;
}

// Fusionner le PDF principal avec toutes les quittances, dans l'ordre
if (!empty($tempReceiptPdfs)) {
    $tempMainFile = tempnam(sys_get_temp_dir(), 'dompdf_');
    file_put_contents($tempMainFile, $pdfOutput);

    $filesToDelete = array_merge($tempReceiptPdfs, [$tempMainFile]);

    try {
        $fpdi = new Fpdi();

        // Pages du PDF principal (confirmation, paysage)
        $pageCount = $fpdi->setSourceFile($tempMainFile);
        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $fpdi->importPage($i);
            $size = $fpdi->getTemplateSize($templateId);
            $fpdi->addPage($size['orientation'] === 'L' ? 'L' : 'P', [$size['width'], $size['height']]);
            $fpdi->useTemplate($templateId);
        }

        // Pages des quittances, dans l'ordre d'origine
        foreach ($tempReceiptPdfs as $receiptFile) {
            $pageCount = $fpdi->setSourceFile($receiptFile);
            for ($i = 1; $i <= $pageCount; $i++) {
                $templateId = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->addPage($size['orientation'] === 'L' ? 'L' : 'P', [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }
        }

        $pdfOutput = $fpdi->output();
    } finally {
        foreach ($filesToDelete as $file) {
            @unlink($file);
        }
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $batch->date->format('Y-m-d-H-i-s') . '-rembourso_confirmation.pdf"');
echo $pdfOutput;