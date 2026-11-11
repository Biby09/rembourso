<?php
// Renommage ou suppression de l'organisation (page Paramètres, administrateurs)
// JSON : organisation_id, action ('rename' + name | 'delete')

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

$input = api_json_input();
$organisation = api_organisation($organisationManager, $input['organisation_id'] ?? null, 'admin');

try {
    switch ($input['action'] ?? null) {
        case 'rename':
            $name = trim((string) ($input['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                json_error("Le nom de l'organisation est invalide (1 à 100 caractères).");
            }
            $organisation = $organisationManager->renameOrganisation($organisation, $name);
            json_success(['name' => $organisation->getName()]);

        case 'delete':
            $organisationManager->deleteOrganisation($organisation);
            json_success();

        default:
            json_error('Action invalide.');
    }
} catch (Exception $e) {
    log_server_exception($e, "Erreur lors de la mise à jour de l'organisation");
    json_error("Erreur lors de la mise à jour de l'organisation.", 500);
}
