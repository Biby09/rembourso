<?php
/**
 * Bibliothèque pour la génération de confirmation de lot de remboursement en PDF
 * Fournit des fonctions pour récupérer les composants HTML et CSS
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

/**
 * Récupère le CSS commun à tous les éléments
 */
function getCommonCss()
{
    return <<<CSS
        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            padding-top: 90px;
            display: flex;
            flex-direction: column;
        }

        h1 {
            font-size: 30px;
            margin-top: 2rem;
            text-align: center;
            page-break-after: avoid;
        }

        h2 {
            font-size: 20px;
            margin: 1rem;
            text-align: center;
            page-break-after: avoid;
        }
    CSS;
}

/**
 * Récupère le HTML et CSS du header
 * Retourne un array avec 'css' et 'html'
 */
function getHeader()
{
    global $logoBase64;
    $css = <<<CSS
        header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 40px;
            padding: 20px;
            background-color: #ffffff;
        }

        header img {
            height: 25px;
            display: block;
            margin: 40px;
        }
    CSS;

    $html = '<header><img src="' . htmlspecialchars($logoBase64) . '" alt="Logo Rembourso"></header>';

    return [
        'css' => $css,
        'html' => $html
    ];
}

/**
 * Récupère le HTML et CSS du footer
 */
function getFooter()
{
    global $user;
    $css = <<<CSS
        footer {
            margin-top: 2rem;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
            background-color: #ffffff;
            page-break-after: avoid;
        }
    CSS;

    $html = '<footer>';
    $html .= '<hr>';
    $html .= '<p>Genéré par ' . htmlspecialchars($user->first_name . ' ' . $user->last_name) . ' sur Rembourso le ' . date('d.m.Y') . '</p>';
    $html .= '</footer>';

    return [
        'css' => $css,
        'html' => $html
    ];
}

/**
 * Récupère le HTML et CSS du contenu principal (tableau de remboursements)
 */
function getMain($batch, $organisation)
{
    $css = <<<CSS
        main {
            padding-top: 0;
            margin: 0;
        }

        h1 {
            font-size: 30px;
            margin-top: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
            page-break-after: avoid;
        }

        h2 {
            font-size: 20px;
            margin-top: 2rem;
            margin-bottom: 1rem;
            page-break-after: avoid;
        }

        ul {
            font-size: 14px;
            margin: 0 0 2rem 0;
            padding-left: 20px;
            page-break-after: avoid;
        }

        .repayment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            page-break-inside: avoid;
            margin-top: 2rem;
        }

        .repayment-table thead th {
            background-color: #f0f0f0;
            border-bottom: 3px solid #333;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
        }

        .repayment-table td {
            padding: 10px;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        .repayment-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .repayment-table tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .repayment-table td:last-child {
            border-right: none;
        }
    CSS;

    $html = '<main>';
    $html .= '<h1>Confirmation de remboursement</h1>';
    $html .= '<ul>';
    $html .= '<li>N° de lot : ' . htmlspecialchars($batch->id) . '</li>';
    $html .= '<li>Date : ' . $batch->date->format('d.m.Y') . '</li>';
    $html .= '<li>Montant total : ' . htmlspecialchars($batch->getTotalAmount() . ' ' . $organisation->currency) . '</li>';
    $html .= '<li>Organisation : ' . htmlspecialchars($organisation->name) . '</li>';
    $html .= '<li>Caissier : ' . htmlspecialchars($batch->cashier->first_name . ' ' . $batch->cashier->last_name) . '</li>';
    $html .= '<li>Bénéficiaire : ' . htmlspecialchars($batch->getBeneficiary()->first_name . ' ' . $batch->getBeneficiary()->last_name) . '</li>';
    $html .= '</ul>';

    $repaymentRequests = $batch->getRepaymentRequests();
    $html .= '<h2>Remboursement' . (count($repaymentRequests) > 1 ? 's' : '') . '</h2>';
    $html .= '<table class="repayment-table">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Index</th>';
    $html .= '<th>Date de demande</th>';
    $html .= '<th>Date de l\'achat</th>';
    $html .= '<th>Libellé</th>';
    $html .= '<th>Catégorie</th>';
    $html .= '<th>Sous-catégorie</th>';
    $html .= '<th>Montant</th>';
    $html .= '<th>Statut</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    $index = 1;
    foreach ($repaymentRequests as $repayment) {
        $html .= '<tr>';
        $html .= '<td>' . $index . '</td>';
        $html .= '<td>' . $repayment->requested_at->format('Y-m-d') . '</td>';
        $html .= '<td>' . $repayment->transaction_date->format('Y-m-d') . '</td>';
        $html .= '<td>' . htmlspecialchars($repayment->label) . '</td>';
        $html .= '<td>' . htmlspecialchars($repayment->category->name ?? '-') . '</td>';
        $html .= '<td>' . htmlspecialchars($repayment->subcategory->name ?? '-') . '</td>';
        $html .= '<td>' . htmlspecialchars($repayment->amount . ' ' . $organisation->currency) . '</td>';
        $html .= '<td>' . htmlspecialchars($repayment->getStatus()) . '</td>';
        $html .= '</tr>';
        $index++;
    }

    $html .='<tr><td colspan="6" style="text-align: right; font-weight: bold;">Total</td><td colspan="2" style="font-weight: bold;">' . htmlspecialchars($batch->getTotalAmount() . ' ' . $organisation->currency) . '</td></tr>';

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</main>';

    return [
        'css' => $css,
        'html' => $html
    ];
}

/**
 * Fonction principale pour générer le HTML complet de confirmation
 * (appelée par get-batch-confirmation.php)
 */
function getFullConfirmationHtml()
{
    global $user;
    
    if (!userConnected()) {
        die('Unauthorized');
    }

    $user = new User($_SESSION['user_id']);
    if (!$user) {
        die('User not found');
    }

    if (!isset($_GET['batch_id'])) {
        die('Batch ID is required');
    }

    $batch = new RepaymentBatch($_GET['batch_id']);
    if (!$batch) {
        die('Batch not found');
    }

    $organisation = new Organisation($batch->organisation_id);
    if (!$organisation) {
        die('Organisation not found');
    }

    // Convertir le logo en base64
    $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/media/logo.svg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
    }


    // Récupérer les composants
    $header = getHeader();
    $main = getMain($batch, $organisation);
    $footer = getFooter();

    // Construire le HTML complet
    $html = '<!DOCTYPE html>' . "\n";
    $html .= '<html lang="fr">' . "\n";
    $html .= '<head>' . "\n";
    $html .= '    <meta charset="UTF-8">' . "\n";
    $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    $html .= '    <title>' . $batch->date->format('Y-m-d-H-i-s') . '-rembourso_confirmation</title>' . "\n";
    $html .= '    <style>' . "\n";
    $html .= $header['css'] . "\n";
    $html .= '    </style>' . "\n";
    $html .= '</head>' . "\n";
    $html .= '<body>' . "\n";
    $html .= $header['html'] . "\n";
    $html .= $main['html'] . "\n";
    $html .= $footer['html'] . "\n";
    $html .= '</body>' . "\n";
    $html .= '</html>';

    return $html;
}

// Si ce fichier est inclus, on retourne simplement les fonctions 
// (utilisées par get-batch-confirmation.php)
?>