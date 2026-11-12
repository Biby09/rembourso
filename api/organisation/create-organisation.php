<?php
// Création d'une organisation (tableau de bord) : l'utilisateur en devient administrateur
// POST : org_name

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api-auth.php';

api_require_method('POST');
$input = api_form_input();

$name = trim((string) ($input['org_name'] ?? ''));
if ($name === '' || mb_strlen($name) > 100) {
    json_error("Le nom de l'organisation est requis (100 caractères maximum).");
}

try {
    $organisation = $organisationManager->createOrganisation($name);
} catch (Exception $e) {
    log_server_exception($e, "Erreur lors de la création d'une organisation");
    json_error("Erreur lors de la création de l'organisation.", 500);
}

json_success([
    'message' => 'Organisation créée avec succès',
    // Assistant de configuration de la nouvelle organisation
    'redirect' => '/dashboard/organisation/init-orga.php?id=' . $organisation->getId() . '&page=1',
]);
