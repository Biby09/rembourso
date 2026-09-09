<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

if (!userConnected()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

verify_csrf_request(null, true);

if (empty($_POST['currentPassword']) || empty($_POST['newPassword']) || empty($_POST['confirmPassword'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit();
}


try {
    $user = new User($_SESSION['user_id']);
    
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Vérifier que les deux nouveaux mots de passe sont identiques
    if ($newPassword !== $confirmPassword) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
        exit();
    }
    
    // Vérifier que le mot de passe actuel est correct
    if (!$user->verifyPassword($currentPassword)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Le mot de passe actuel est incorrect']);
        exit();
    }
    
    // Mettre à jour le mot de passe
    $user->updatePassword($newPassword);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès']);
    
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la mise à jour du mot de passe');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe']);
}
?>
