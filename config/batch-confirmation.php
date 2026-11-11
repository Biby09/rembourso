<?php
/**
 * Morceaux HTML / CSS du PDF de confirmation d'un lot de remboursement (api/batch/confirmation.php).
 * Chaque fonction renvoie ['css' => …, 'html' => …] (sauf getCommonCss).
 */

/**
 * CSS commun à toutes les pages du PDF.
 */
function getCommonCss(): string
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
 * En-tête avec le logo ($logoDataUri : image en data URI, vide si le logo est introuvable).
 */
function getHeader(string $logoDataUri): array
{
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

    return ['css' => $css, 'html' => '<header><img src="' . e($logoDataUri) . '" alt="Logo Rembourso"></header>'];
}

/**
 * Pied de page : qui a généré le document, et quand.
 */
function getFooter(User $author): array
{
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

    $html = '<footer><hr><p>Généré par ' . e($author->getFullName()) . ' sur Rembourso le ' . date('d.m.Y') . '</p></footer>';

    return ['css' => $css, 'html' => $html];
}

/**
 * Date et heure d'un lot (stockées en UTC) à l'heure suisse : « 11.11.2026 à 18:05 »
 */
function batch_local_datetime(string $utcDate): string
{
    $date = new DateTime($utcDate, new DateTimeZone('UTC'));
    return $date->setTimezone(new DateTimeZone('Europe/Zurich'))->format('d.m.Y \à H:i');
}

/**
 * Nom du pays en français à partir de son code (« CH » → « Suisse ») ; le code tel quel s'il est inconnu.
 */
function country_name(?string $code): string
{
    try {
        return $code ? \Symfony\Component\Intl\Countries::getName($code, 'fr') : '';
    } catch (Exception) {
        return (string) $code;
    }
}

/**
 * Bloc d'informations « libellé : valeur » (les valeurs vides sont omises).
 * $rows : [libellé => valeur en texte brut, échappée ici]
 */
function info_table(string $title, array $rows, string $note = ''): string
{
    $html = '<div class="info-block"><h3>' . e($title) . '</h3><table class="info-table">';
    foreach ($rows as $label => $value) {
        if ($value !== null && $value !== '') {
            $html .= '<tr><th>' . e($label) . '</th><td>' . nl2br(e($value)) . '</td></tr>';
        }
    }
    return $html . '</table>' . ($note ? '<p class="info-note">' . e($note) . '</p>' : '') . '</div>';
}

/**
 * Contenu principal : remboursement (lot), bénéficiaire du paiement et tableau des demandes.
 * $requests : RepaymentRequest[] du lot ; $member : membre à qui appartiennent les demandes (null s'il n'existe plus).
 */
function getMain(RepaymentBatch $batch, Organisation $organisation, array $requests, ?User $member): array
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

        .info-blocks {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .info-blocks td.info-cell {
            width: 50%;
            vertical-align: top;
            padding: 0 16px 0 0;
        }

        .info-block h3 {
            font-size: 15px;
            margin: 0 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 2px solid #367447;
            color: #367447;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .info-table th {
            width: 40%;
            text-align: left;
            font-weight: bold;
            padding: 4px 8px 4px 0;
            vertical-align: top;
        }

        .info-table td {
            padding: 4px 0;
        }

        .info-note {
            font-size: 11px;
            color: #888;
            margin: 6px 0 0 0;
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

    $currency = $organisation->getCurrency();
    $total = e(format_amount($batch->getTotalAmount(), $currency));

    // Coordonnées de paiement : celles enregistrées à la validation, sinon celles actuelles du membre (anciens lots)
    if ($batch->hasPayeeSnapshot()) {
        $payee = [$batch->getPayeeName(), $batch->getPayeeAddress(), $batch->getPayeePostalCode(), $batch->getPayeeCity(), $batch->getPayeeCountry(), $batch->getPayeeIban()];
        $payeeNote = '';
    } else {
        $payee = $member
            ? [$member->getRefundFullName(), $member->getRefundAddress(), $member->getRefundPostalCode(), $member->getRefundCity(), $member->getRefundCountry(), $member->getIban()]
            : [$batch->getBeneficiaryName(), '', '', '', '', ''];
        $payeeNote = "Lot validé avant l'enregistrement des coordonnées de paiement : coordonnées actuelles du membre.";
    }
    [$payeeName, $payeeAddress, $payeePostalCode, $payeeCity, $payeeCountry, $payeeIban] = $payee;

    $countLabel = $batch->getAcceptedCount() . ' acceptée' . ($batch->getAcceptedCount() > 1 ? 's' : '');
    if ($batch->getRejectedCount() > 0) {
        $countLabel .= ', ' . $batch->getRejectedCount() . ' refusée' . ($batch->getRejectedCount() > 1 ? 's' : '');
    }

    $repaymentBlock = info_table('Remboursement', [
        'N° de lot' => (string) $batch->getId(),
        'Date du remboursement' => batch_local_datetime($batch->getDate()),
        'Montant remboursé' => format_amount($batch->getTotalAmount(), $currency),
        'Demandes' => $countLabel,
        'Organisation' => $organisation->getName(),
        'Caissier' => $batch->getCashierName(),
    ]);

    $beneficiaryBlock = info_table('Bénéficiaire', [
        'Membre' => $member?->getFullName() ?? $batch->getBeneficiaryName(),
        'E-mail' => $member?->getEmail(),
        'Payé à' => $payeeName,
        'Adresse' => trim($payeeAddress . "\n" . trim($payeePostalCode . ' ' . $payeeCity) . "\n" . country_name($payeeCountry)),
        'IBAN' => $payeeIban ? iban_to_human_format($payeeIban) : '',
    ], $payeeNote);

    $html = '<main>';
    $html .= '<h1>Confirmation de remboursement</h1>';
    $html .= '<table class="info-blocks"><tr><td class="info-cell">' . $repaymentBlock . '</td><td class="info-cell">' . $beneficiaryBlock . '</td></tr></table>';

    $html .= '<h2>Détail des demandes</h2>';
    $html .= '<table class="repayment-table"><thead><tr>';
    foreach (['Index', 'Date de demande', "Date de l'achat", 'Libellé', 'Catégorie', 'Sous-catégorie', 'Montant', 'Statut'] as $title) {
        $html .= '<th>' . e($title) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach (array_values($requests) as $index => $request) {
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . date('d.m.Y', strtotime($request->getRequestedAt())) . '</td>';
        $html .= '<td>' . date('d.m.Y', strtotime($request->getTransactionDate())) . '</td>';
        $html .= '<td>' . e($request->getLabel()) . '</td>';
        $html .= '<td>' . e($request->getCategoryName() ?? '-') . '</td>';
        $html .= '<td>' . e($request->getSubcategoryName() ?? '-') . '</td>';
        $html .= '<td>' . e(format_amount($request->getAmount(), $currency)) . '</td>';
        $html .= '<td>' . e($request->getStatusLabel()) . '</td>';
        $html .= '</tr>';
    }

    $html .= '<tr><td colspan="6" style="text-align: right; font-weight: bold;">Total</td><td colspan="2" style="font-weight: bold;">' . $total . '</td></tr>';
    $html .= '</tbody></table></main>';

    return ['css' => $css, 'html' => $html];
}
