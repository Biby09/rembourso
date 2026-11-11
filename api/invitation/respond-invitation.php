<?php
// Réponse du compte invité (tableau de bord ou page /invitation)
// JSON : accept (booléen), token ou id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitationManager.php';

$input = api_json_input();

if ((empty($input['token']) && empty($input['id'])) || !isset($input['accept'])) {
    json_error('Token, identifiant ou réponse manquant.');
}

$invitationManager = new InvitationManager($mysqlClient, $user);
$invitation = api_find(
    fn() => !empty($input['token'])
        ? $invitationManager->getInvitationByToken((string) $input['token'])
        : $invitationManager->getInvitationById(api_id($input['id'], 'Invitation non trouvée.')),
    'Invitation non trouvée.'
);

if ($invitation->getUserId() !== $user->getId()) {
    json_error('Invitation non valide pour cet utilisateur.', 403);
}

$organisation = OrganisationManager::findById($mysqlClient, $invitation->getOrganisationId());
if ($organisation && $organisationManager->isMember($organisation)) {
    json_error('Vous êtes déjà membre de cette organisation.');
}

try {
    $input['accept'] ? $invitationManager->acceptInvitation($invitation) : $invitationManager->declineInvitation($invitation);
} catch (Exception $e) {
    json_error($e->getMessage());
}

json_success(['organisation_id' => $invitation->getOrganisationId()]);
