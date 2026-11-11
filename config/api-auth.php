<?php
/**
 * Amorçage des endpoints JSON réservés aux utilisateurs connectés (équivalent de config/auth.php pour /api) :
 * répond 401 au lieu de rediriger vers la connexion.
 * Fournit $user, $organisationManager et api_organisation().
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisationManager.php';

if (!isset($_SESSION['user_id'])) {
    json_error('Vous devez être connecté.', 401);
}

try {
    $user = $userManager->getUserById((int) $_SESSION['user_id']);
    if (!$user->isActive() || !$user->isRegistered()) {
        throw new Exception('Inactive account');
    }
} catch (Exception) {
    session_destroy();
    json_error('Session invalide. Veuillez vous reconnecter.', 401);
}

$organisationManager = new OrganisationManager($mysqlClient, $user);

/**
 * Organisation dont l'utilisateur connecté est membre avec au moins le rôle demandé
 * ('member' < 'cashier' < 'admin') ; sinon 404 (pas membre) ou 403 (rôle insuffisant).
 */
function api_organisation(OrganisationManager $organisationManager, mixed $id, string $role = 'member'): Organisation
{
    $organisation = api_find(
        fn() => $organisationManager->getOrganisationById(api_id($id, 'Organisation non trouvée.')),
        'Organisation non trouvée.'
    );

    if (!$organisationManager->hasRole($organisation, $role)) {
        json_error('Accès refusé.', 403);
    }
    return $organisation;
}
