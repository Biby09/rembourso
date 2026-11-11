<?php
// Suppression du compte (page Mon compte) : l'utilisateur retape son e-mail pour confirmer
// POST : confirmEmail

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

api_require_method('POST');
$input = api_form_input();

if (($input['confirmEmail'] ?? '') !== $user->getEmail()) {
    json_error("L'e-mail ne correspond pas.");
}

try {
    $userManager->deleteUser($user);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la suppression du compte');
    json_error('Erreur lors de la suppression du compte.', 500);
}

session_destroy();

json_success(['message' => 'Votre compte a été supprimé', 'redirect' => '/login']);
