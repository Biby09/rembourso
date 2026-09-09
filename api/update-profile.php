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
    
    // Mettre à jour les informations personnelles
    $user->first_name = $_POST['firstName'] ?? '';
    $user->last_name = $_POST['lastName'] ?? '';
    $user->email = $_POST['email'] ?? '';
    $user->phone = $_POST['phone'] ?? '';
    $user->address = $_POST['address'] ?? '';
    $user->postal_code = $_POST['postalCode'] ?? '';
    $user->city = $_POST['city'] ?? '';
    $user->country = $_POST['country'] ?? '';
    
    // Sauvegarder les modifications
    $user->update();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
    
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la mise à jour du profil');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du profil']);
}
?>
