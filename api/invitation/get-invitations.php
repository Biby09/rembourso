<?php
// Invitations en cours envoyées par l'organisation (page Membres, administrateurs)
// JSON : organisation_id

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitationManager.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'admin');

$invitationManager = new InvitationManager($mysqlClient, $user);

json_success(['data' => array_map(fn(Invitation $invitation) => [
    'id' => $invitation->getId(),
    'name' => $invitation->getUserName(),
    'role' => $invitation->getRoleLabel(),
], $invitationManager->getPendingInvitationsOfOrganisation($organisation))]);
