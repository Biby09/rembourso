<?php
// Traitement des demandes d'un membre par un caissier (page « À rembourser », scripts/pages/organisation/cashier.js) :
// - action 'get_qr'          : calcule le montant accepté et renvoie la QR-facture de paiement (Swiss QR-Bill) ;
// - action 'confirm_payment' : crée un lot et enregistre le statut des demandes.
// JSON : organisation_id, action, updates : [{ repayment_id, status: 'accepted' | 'rejected' }]

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/repayment/repaymentRequestManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/batch/repaymentBatchManager.php';

use Sprain\SwissQrBill as QrBill;

const REPAYMENT_STATUS_CODES = ['accepted' => RepaymentRequest::STATUS_ACCEPTED, 'rejected' => RepaymentRequest::STATUS_REJECTED];

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'cashier');
$repaymentRequestManager = new RepaymentRequestManager($mysqlClient, $organisation, $user);

$action = $input['action'] ?? null;
if (!in_array($action, ['get_qr', 'confirm_payment'], true)) {
    json_error('Action non autorisée.');
}
if (empty($input['updates']) || !is_array($input['updates'])) {
    json_error('Aucun remboursement à traiter.');
}

// Validation de toutes les décisions avant tout traitement
$beneficiaryId = null;
$amount = 0.0;
$decisions = [];
foreach ($input['updates'] as $update) {
    $status = is_array($update) ? ($update['status'] ?? null) : null;
    if (!isset(REPAYMENT_STATUS_CODES[$status])) {
        json_error('Données de mise à jour invalides.');
    }

    $request = api_find(fn() => $repaymentRequestManager->getRequestById(api_id($update['repayment_id'] ?? null, 'Demande de remboursement non trouvée.')),
        'Demande de remboursement non trouvée.');
    if (!$request->isPending()) {
        json_error('Cette demande a déjà été traitée.');
    }

    $beneficiaryId ??= $request->getUserId();
    if ($request->getUserId() !== $beneficiaryId) {
        json_error('Les demandes de remboursement doivent toutes appartenir au même membre.');
    }

    if ($status === 'accepted') {
        $amount += (float) $request->getAmount();
    }
    $decisions[] = [$request, REPAYMENT_STATUS_CODES[$status]];
}

$beneficiary = api_find(fn() => $userManager->getUserById($beneficiaryId), 'Bénéficiaire non trouvé.');

if ($action === 'confirm_payment') {
    try {
        (new RepaymentBatchManager($mysqlClient, $organisation))->createBatch($user, $beneficiary, $decisions);
    } catch (Exception $e) {
        log_server_exception($e, 'Erreur lors de la validation des remboursements');
        json_error('Erreur lors de la mise à jour des statuts.', 500);
    }

    json_success(['message' => 'Statuts mis à jour avec succès']);
}

// --- action 'get_qr' : QR-facture au nom du bénéficiaire des remboursements
$currency = $organisation->getCurrency();

$qrBill = QrBill\QrBill::create();
$qrBill->setCreditor(
    QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
        $beneficiary->getRefundFullName(),
        $beneficiary->getRefundAddress(),
        '', // numéro (vide : il fait partie de la rue)
        $beneficiary->getRefundPostalCode(),
        $beneficiary->getRefundCity(),
        $beneficiary->getRefundCountry()
    )
);
$qrBill->setCreditorInformation(QrBill\DataGroup\Element\CreditorInformation::create($beneficiary->getIban()));
$qrBill->setPaymentReference(QrBill\DataGroup\Element\PaymentReference::create(QrBill\DataGroup\Element\PaymentReference::TYPE_NON));
$qrBill->setPaymentAmountInformation(QrBill\DataGroup\Element\PaymentAmountInformation::create($currency, $amount));

try {
    $qrCodeDataUri = $qrBill->getQrCode()->getDataUri();
} catch (Exception $e) {
    $violations = array_map(fn($violation) => $violation->getMessage(), iterator_to_array($qrBill->getViolations()));
    log_server_exception($e, 'Erreur lors de la génération du QR Code pour le remboursement');
    json_error('Données invalides pour la facture QR (vérifiez les coordonnées bancaires du membre).', 400, ['violations' => $violations]);
}

// ob_start / ob_get_clean : le HTML de l'include est récupéré dans une variable au lieu d'être affiché
ob_start();
include $_SERVER['DOCUMENT_ROOT'] . '/includes/payment-summary.php';
json_success(['modalContent' => ob_get_clean()]);
