<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

verify_csrf_request(null, true);

try {
    $user = new User($_SESSION['user_id']);
    
    // Mettre à jour les informations bancaires
    $user->iban = $_POST['iban'] ?? '';
    
    // Mettre à jour les infos du bénéficiaire (qu'elles viennent des champs refund_ ou des données perso copiées)
    $user->refund_first_name = $_POST['refund_first_name'] ?? '';
    $user->refund_last_name = $_POST['refund_last_name'] ?? '';
    $user->refund_address = $_POST['refund_address'] ?? '';
    $user->refund_postal_code = $_POST['refund_postal_code'] ?? '';
    $user->refund_city = $_POST['refund_city'] ?? '';
    $user->refund_country = $_POST['refund_country'] ?? '';
    
    // Sauvegarder les modifications
    $user->update();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Informations bancaires mises à jour']);
    
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la mise à jour des informations bancaires');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour des informations bancaires']);
}
?>
