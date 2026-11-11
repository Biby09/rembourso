<?php
// Utilisation d'un lien de création de compte (page /account-claim) :
// - action « create » : la personne sans compte choisit un mot de passe, sa fiche devient un compte ;
// - action « merge »  : la personne, connectée à son compte existant (même e-mail), y rattache ses demandes.
// POST : token, action, password et confirm_password (create)

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/accountClaim/accountClaimManager.php';

api_require_method('POST');
$input = api_form_input();

$token = $input['token'] ?? null;
if (!is_string($token) || $token === '') {
    json_error('Lien invalide.');
}

rate_limit_consume('account_claim_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);

$accountClaimManager = new AccountClaimManager($mysqlClient, $userManager);
$claim = api_find(fn() => $accountClaimManager->getClaimByToken($token), 'Lien invalide.');

if (!$claim->isValid()) {
    json_error('Ce lien a déjà été utilisé ou a expiré. Demandez au caissier de vous en renvoyer un.');
}

switch ($input['action'] ?? null) {
    case 'create':
        $password = (string) ($input['password'] ?? '');
        if ($password === '' || $password !== (string) ($input['confirm_password'] ?? '')) {
            json_error('Les mots de passe sont vides ou ne correspondent pas.');
        }
        if ($accountClaimManager->getExistingAccount($claim)) {
            json_error('Un compte existe déjà avec cette adresse e-mail : connectez-vous pour y rattacher vos demandes.');
        }

        try {
            $account = $accountClaimManager->complete($claim, $password);
        } catch (Exception $e) {
            log_server_exception($e, "Erreur lors de la création d'un compte depuis un lien");
            json_error('Impossible de créer le compte.', 500);
        }
        break;

    case 'merge':
        if (!isset($_SESSION['user_id'])) {
            json_error('Vous devez être connecté.', 401);
        }
        $account = api_find(fn() => $userManager->getUserById((int) $_SESSION['user_id']), 'Compte introuvable.');
        try {
            $accountClaimManager->mergeInto($claim, $account);
        } catch (Exception) {
            json_error("Impossible de rattacher les demandes à ce compte : connectez-vous avec l'adresse e-mail qui a reçu le lien.", 403);
        }
        break;

    default:
        json_error('Action invalide.');
}

session_regenerate_id(true);
$_SESSION['user_id'] = $account->getId();
json_success(['message' => 'Votre compte est prêt.', 'redirect' => '/dashboard/organisation/requests/?id=' . $claim->getOrganisationId()]);
