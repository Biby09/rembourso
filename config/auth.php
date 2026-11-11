<?php
/**
 * Pages réservées aux utilisateurs connectés (à inclure après config/init.php).
 * Sans session valide : renvoi vers la connexion, puis retour à la page demandée.
 * Fournit $user, $organisationManager et $invitationManager.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisationManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/invitation/invitationManager.php';

$loginUrl = '/login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard');

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $loginUrl);
    exit;
}

try {
    $user = $userManager->getUserById((int) $_SESSION['user_id']);
    if (!$user->isActive() || !$user->isRegistered()) {
        throw new Exception('Inactive account');
    }
} catch (Exception) {
    // Compte supprimé entre-temps : on repart d'une session vierge
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

$organisationManager = new OrganisationManager($mysqlClient, $user);
$invitationManager = new InvitationManager($mysqlClient, $user);
