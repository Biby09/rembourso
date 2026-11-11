<?php
// Changement du mot de passe (page Mon compte)
// POST : currentPassword, newPassword, confirmPassword

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

api_require_method('POST');
$input = api_form_input();

$currentPassword = (string) ($input['currentPassword'] ?? '');
$newPassword = (string) ($input['newPassword'] ?? '');

if ($currentPassword === '' || $newPassword === '' || ($input['confirmPassword'] ?? '') === '') {
    json_error('Tous les champs sont requis.');
}
if ($newPassword !== $input['confirmPassword']) {
    json_error('Les mots de passe ne correspondent pas.');
}
if (!$user->verifyPassword($currentPassword)) {
    json_error('Le mot de passe actuel est incorrect.');
}

try {
    $userManager->updatePassword($user, $newPassword);
} catch (Exception $e) {
    log_server_exception($e, 'Erreur lors de la mise à jour du mot de passe');
    json_error('Erreur lors de la mise à jour du mot de passe.', 500);
}

json_success(['message' => 'Mot de passe mis à jour avec succès']);
