<?php
/**
 * Amorçage commun des pages d'une organisation : /dashboard/organisation/…/?id=…
 *
 * Optionnel, à définir AVANT l'inclusion : $requiredRole = 'cashier' ou 'admin' (par défaut 'member').
 * Fournit, en plus de config/init.php et config/auth.php : $organisation, $role, $isCashier, $isAdmin.
 *
 * - organisation inexistante ou utilisateur non membre : retour au tableau de bord ;
 * - rôle insuffisant : retour à la page « Nouvelle demande » de l'organisation.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

$organisationId = $_GET['id'] ?? '';
try {
    if (!ctype_digit((string) $organisationId)) {
        throw new Exception('Invalid organisation ID');
    }
    // Lève une exception si l'organisation n'existe pas ou si l'utilisateur n'en est pas membre
    $organisation = $organisationManager->getOrganisationById((int) $organisationId);
} catch (Exception) {
    header('Location: /dashboard');
    exit;
}

$role = $organisationManager->getRole($organisation);
$isCashier = $organisationManager->isCashier($organisation);
$isAdmin = $organisationManager->isAdmin($organisation);

if (!$organisationManager->hasRole($organisation, $requiredRole ?? 'member')) {
    header('Location: /dashboard/organisation/?id=' . $organisation->getId());
    exit;
}

/** Adresse d'une page de l'organisation courante : organisation_url('requests/') → /dashboard/organisation/requests/?id=3 */
function organisation_url(Organisation $organisation, string $page = ''): string
{
    return '/dashboard/organisation/' . $page . '?id=' . $organisation->getId();
}
