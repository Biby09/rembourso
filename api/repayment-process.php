<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Sprain\SwissQrBill as QrBill;

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    header('Location: /login?redirect=/dashboard');
    exit;
}

try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    // En cas d'erreur lors de la récupération de l'utilisateur, on détruit la session et on redirige vers la page de connexion
    session_destroy();
    header('Location: /login?redirect=/dashboard');
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_request($input);

if (!isset($input['updates']) || !isset($input['action']) || !isset($input['organisation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

if (!is_array($input['updates']) || empty($input['updates'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucun remboursement à traiter']);
    exit;
}

try {
    $organisation = new Organisation($input['organisation_id']);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Organisation non trouvée']);
    exit;
}

if (!$organisation->isMember($user->id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

if (!$organisation->isCashier($user->id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

if (!in_array($input['action'], ['get_qr', 'confirm_payment'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action non autoriséee']);
    exit;
}


$amount = 0;
foreach ($input['updates'] as $update) {

    if (!isset($update['repayment_id']) || !isset($update['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Données d\'update manquantes']);
        exit;
    }

    try {
        $repayment = new RepaymentRequest($update['repayment_id']);
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Demande de remboursement non trouvée']);
        exit;
    }

    if ($repayment->organisation_id != $organisation->id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }

    if (!isset($client)) {
        try {
            $client = new User($repayment->user_id);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
            exit;
        }
    }

    if ($repayment->user_id != $client->id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Les demandes de remboursement doivent toutes appartenir au même client']);
        exit;
    }

    if ($update['status'] === 'accepted') {
        $amount += $repayment->amount;
    }
}

if ($input['action'] === 'get_qr') {

    $qrBill = QrBill\QrBill::create();

    // 2. Créancier — directement avec StructuredAddress
    $qrBill->setCreditor(
        QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
            $client->refund_first_name . ' ' . $client->refund_last_name, // nom
            $client->refund_address,    // rue
            '',                         // numéro (vide si non séparé)
            $client->refund_postal_code,
            $client->refund_city,
            $client->refund_country
        )
    );

    // 3. IBAN
    $qrBill->setCreditorInformation(
        QrBill\DataGroup\Element\CreditorInformation::create(
            $client->iban
        )
    );

    // 4. Référence de paiement (obligatoire)
    $qrBill->setPaymentReference(
        QrBill\DataGroup\Element\PaymentReference::create(
            QrBill\DataGroup\Element\PaymentReference::TYPE_NON
        )
    );

    // 5. Montant
    $qrBill->setPaymentAmountInformation(
        QrBill\DataGroup\Element\PaymentAmountInformation::create(
            $organisation->currency ?? 'CHF',
            $amount
        )
    );

    // 6. Générer le SVG
    try {
        $qrCodeSvg = $qrBill->getQrCode()->getDataUri();

    } catch (\Exception $e) {
        $violations = [];
        foreach ($qrBill->getViolations() as $violation) {
            $violations[] = $violation->getMessage();
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Données invalides pour la facture QR',
            'violations' => $violations
        ]);
        exit;
    }

    // Construire le contenu du modal
    $modalContent = '<div class="container-fluid">';
    $modalContent .= '    <div class="row">';
    $modalContent .= '        <div class="col-md-6">';
    $modalContent .= '            <h6 class="fw-bold mb-3">Client</h6>';
    $modalContent .= '            <p class="mb-0">' . htmlspecialchars($client->refund_first_name . ' ' . $client->refund_last_name) . '</p>';
    $modalContent .= '            <small class="text-muted">' . htmlspecialchars($client->refund_address) . '</small><br>';
    $modalContent .= '            <small class="text-muted">' . htmlspecialchars($client->refund_postal_code) . ' ' . htmlspecialchars($client->refund_city) . '</small><br>';
    $modalContent .= '            <small class="text-muted">' . htmlspecialchars($client->iban) . '</small>';
    $modalContent .= '            <div class="mt-4 pt-3 border-top">';
    $modalContent .= '                <h6 class="fw-bold">Montant à rembourser</h6>';
    $modalContent .= '                <h4 class="text-primary">CHF ' . number_format($amount, 2, '.', '\'') . '</h4>';
    $modalContent .= '            </div>';
    $modalContent .= '        </div>';
    $modalContent .= '        <div class="col-md-6 text-center">';
    $modalContent .= '            <h6 class="fw-bold mb-3">Code QR</h6>';
    $modalContent .= '            <img src="' . $qrCodeSvg . '" alt="QR Code de paiement" class="img-fluid" style="max-width: 200px;">';
    $modalContent .= '        </div>';
    $modalContent .= '    </div>';
    $modalContent .= '</div>';


    http_response_code(200);
    echo json_encode(['success' => true, 'modalContent' => $modalContent]);
    exit;

} else if ($input['action'] === 'confirm_payment') {

    $batch = RepaymentBatch::create($organisation->id);

    foreach ($input['updates'] as $update) {
        $repayment = new RepaymentRequest($update['repayment_id']);

        if ($update['status'] === 'accepted') {
            $repayment->updateStatus(1, $batch->id);
        }
        else if ($update['status'] === 'rejected') {
            $repayment->updateStatus(2, $batch->id);
        }


    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Statuts mis à jour avec succès']);
    exit;
}