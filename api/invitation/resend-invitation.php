<?php
// Renvoi de l'e-mail d'une invitation (page Membres, administrateurs) : au plus une fois par jour
// JSON : invitation_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitationManager.php';

$input = api_json_input();
$invitationManager = new InvitationManager($mysqlClient, $user);
$invitation = api_find(fn() => $invitationManager->getInvitationById(api_id($input['invitation_id'] ?? null, 'Invitation non trouvée.')), 'Invitation non trouvée.');
api_organisation($organisationManager, $invitation->getOrganisationId(), 'admin');

if (!$invitation->isValid()) {
    json_error("Cette invitation n'est plus valable.");
}
if ($invitation->getLastMailedAt() && new DateTime($invitation->getLastMailedAt()) > new DateTime('-1 day')) {
    json_error("Dernier mail envoyé il y a moins d'un jour. Veuillez attendre avant de réessayer.", 429);
}

$member = api_find(fn() => $userManager->getUserById($invitation->getUserId()), 'Utilisateur invité introuvable.');
if (!$invitationManager->resendInvitation($invitation, $member)) {
    json_error("Erreur lors de la réexpédition de l'invitation.", 500);
}

json_success();
