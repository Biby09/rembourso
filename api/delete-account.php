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
    
    $confirmEmail = $_POST['confirmEmail'] ?? '';
    
    // Vérifier que l'email fourni correspond à celui de l'utilisateur
    if ($confirmEmail !== $user->email) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'L\'email ne correspond pas']);
        exit();
    }
        
    // Supprimer l'utilisateur de la base de données
    $user->delete();
    
    // Détruire la session
    session_destroy();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Votre compte a été supprimé',
        'redirect' => '/login'
    ]);
    
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la suppression du compte');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du compte']);
}
?>
