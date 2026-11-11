<?php
// Annulation d'une invitation (bouton « Supprimer » de la page Membres, administrateurs)
// JSON : invitation_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitationManager.php';

$input = api_json_input();
$invitationManager = new InvitationManager($mysqlClient, $user);
$invitation = api_find(fn() => $invitationManager->getInvitationById(api_id($input['invitation_id'] ?? null, 'Invitation non trouvée.')), 'Invitation non trouvée.');
api_organisation($organisationManager, $invitation->getOrganisationId(), 'admin');

$invitationManager->cancelInvitation($invitation);

json_success();
