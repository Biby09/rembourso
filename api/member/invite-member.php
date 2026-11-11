<?php
// Invitation d'un compte dans l'organisation (page Membres, administrateurs)
// JSON : organisation_id, email, role

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitationManager.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'admin');

$role = $input['role'] ?? null;
if (!is_string($role) || !isset(Organisation::ROLE_LABELS[$role])) {
    json_error('Rôle invalide.');
}

// Seuls les comptes peuvent être invités (une personne sans compte ne peut pas se connecter)
$member = $userManager->findAccountByEmail(trim((string) ($input['email'] ?? '')));
if (!$member || !$member->isActive()) {
    json_error('Aucun utilisateur trouvé avec cette adresse e-mail.', 404);
}

$invitationManager = new InvitationManager($mysqlClient, $user);

if ($organisationManager->isMember($organisation, $member->getId())) {
    json_error("L'utilisateur est déjà membre de l'organisation.");
}
if ($invitationManager->hasPendingInvitation($organisation, $member->getId())) {
    json_error('Une invitation pour cet utilisateur est déjà en cours.');
}

try {
    $invitationManager->createInvitation($organisation, $member, $role);
} catch (Exception $e) {
    log_server_exception($e, "Erreur lors de l'invitation d'un membre");
    json_error("Impossible d'inviter ce membre.", 500);
}

json_success();
