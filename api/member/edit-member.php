<?php
// Changement de rôle ou retrait d'un membre (page Membres, administrateurs)
// JSON : organisation_id, member_id, action ('update' + new_role | 'delete')

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'admin');
$member = api_find(fn() => $userManager->getUserById(api_id($input['member_id'] ?? null, 'Membre non trouvé.')), 'Membre non trouvé.');

if (!$organisationManager->isMember($organisation, $member->getId())) {
    json_error("Ce membre n'appartient pas à l'organisation.", 404);
}
if ($member->getId() === $user->getId()) {
    json_error('Vous ne pouvez pas modifier votre propre rôle.', 403);
}

switch ($input['action'] ?? null) {
    case 'delete':
        $organisationManager->removeMember($organisation, $member->getId());
        json_success();

    case 'update':
        if (!$member->isRegistered()) {
            json_error("Le rôle d'une personne sans compte ne peut pas être modifié.");
        }
        $role = $input['new_role'] ?? null;
        if (!is_string($role) || !isset(Organisation::ROLE_LABELS[$role])) {
            json_error('Rôle invalide.');
        }
        $organisationManager->changeMemberRole($organisation, $member->getId(), $role);
        json_success(['role' => $role, 'role_label' => Organisation::roleLabel($role)]);

    default:
        json_error('Action invalide.');
}
