<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/accountClaim/accountClaim.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/token/tokenManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user/userManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database/transactional.php';

/**
 * Création de compte d'une personne « sans compte » à partir du lien reçu par e-mail.
 * Le jeton prouve que la personne possède l'adresse e-mail :
 * - aucun compte avec cet e-mail : elle choisit un mot de passe et sa fiche devient un compte (complete) ;
 * - un compte existe déjà avec cet e-mail : elle s'y connecte et ses demandes y sont rattachées (mergeInto).
 */
class AccountClaimManager
{
    use Transactional;

    private const VALIDITY = '+30 days';

    private PDO $pdo;
    private UserManager $userManager;
    private TokenManager $tokenManager;

    public function __construct(PDO $pdo, UserManager $userManager)
    {
        $this->pdo = $pdo;
        $this->userManager = $userManager;
        $this->tokenManager = new TokenManager($pdo);
    }

    public function getClaimByToken(string $token): AccountClaim
    {
        $stmt = $this->pdo->prepare('SELECT ac.*, t.tok_expires_at, t.tok_used FROM account_claims ac
            JOIN tokens t ON t.id_token = ac.idx_token WHERE t.tok_token = :token_hash');
        $stmt->execute(['token_hash' => TokenManager::hash($token)]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception('Account claim not found');
        }

        $claim = new AccountClaim();
        $claim->hydrate($ligne);
        return $claim;
    }

    /**
     * Crée un nouveau lien (les précédents de cette personne sont invalidés) et l'envoie par e-mail.
     */
    public function createClaim(User $person, Organisation $organisation): void
    {
        if ($person->isRegistered()) {
            throw new Exception('This person already has an account');
        }
        if (!$person->getEmail()) {
            throw new Exception('No e-mail address for this person');
        }

        $token = $this->transaction(function () use ($person, $organisation) {
            $this->invalidateAll($person->getId());
            [$tokenId, $token] = $this->tokenManager->createToken($person->getId(), self::VALIDITY, true, 32);
            $stmt = $this->pdo->prepare('INSERT INTO account_claims (idx_user, idx_token, idx_organisation) VALUES (:user, :token, :organisation)');
            $stmt->execute(['user' => $person->getId(), 'token' => $tokenId, 'organisation' => $organisation->getId()]);
            return $token;
        });

        $subject = 'Vos demandes de remboursement – ' . $organisation->getName();
        $message = $organisation->getName() . " a enregistré une ou plusieurs demandes de remboursement à votre nom sur Rembourso."
            . "\n\nPour créer votre compte et suivre vos remboursements, cliquez sur le lien suivant (valable 30 jours) :\n" . token_link($token)
            . "\n\nSi vous avez déjà un compte Rembourso avec cette adresse e-mail, ce lien vous permettra d'y rattacher ces demandes.";

        if (!sendMail($person->getEmail(), $subject, $message, token: $token, loggedMessage: str_replace($token, '[REDACTED]', $message))) {
            throw new Exception("The e-mail could not be sent");
        }
    }

    public function getPerson(AccountClaim $claim): User
    {
        return $this->userManager->getUserById($claim->getUserId());
    }

    /**
     * Compte actif ayant le même e-mail que la personne, ou null.
     */
    public function getExistingAccount(AccountClaim $claim): ?User
    {
        $email = $this->getPerson($claim)->getEmail();
        $account = $email ? $this->userManager->findAccountByEmail($email) : null;
        return $account?->isActive() ? $account : null;
    }

    /**
     * Transforme la fiche en compte avec le mot de passe choisi.
     */
    public function complete(AccountClaim $claim, string $password): User
    {
        $this->assertUsable($claim);
        if ($this->getExistingAccount($claim)) {
            throw new Exception('An account already exists with this e-mail');
        }

        $this->transaction(function () use ($claim, $password) {
            $stmt = $this->pdo->prepare('UPDATE users SET use_password = :password, use_registered = 1 WHERE id_user = :id AND use_registered = 0');
            $stmt->execute(['password' => password_hash($password, PASSWORD_BCRYPT), 'id' => $claim->getUserId()]);
            if ($stmt->rowCount() !== 1) {
                throw new Exception('This account has already been created');
            }
            $this->invalidateAll($claim->getUserId());
        });

        return $this->userManager->getUserById($claim->getUserId());
    }

    /**
     * Rattache les demandes et les organisations de la personne au compte existant (même e-mail),
     * puis désactive la fiche sans compte.
     */
    public function mergeInto(AccountClaim $claim, User $account): void
    {
        $this->assertUsable($claim);
        $person = $this->getPerson($claim);
        if (!$account->isRegistered() || !$account->isActive() || !$person->getEmail()
            || strcasecmp(trim((string) $account->getEmail()), trim($person->getEmail())) !== 0) {
            throw new Exception('This link does not belong to this account');
        }

        $this->transaction(function () use ($person, $account) {
            $params = ['account' => $account->getId(), 'person' => $person->getId()];
            $this->pdo->prepare('UPDATE repayment_requests SET idx_user = :account WHERE idx_user = :person')->execute($params);
            $this->pdo->prepare("INSERT IGNORE INTO users_organisations (id_idx_user, id_idx_organisation, org_role)
                SELECT :account, id_idx_organisation, 'member' FROM users_organisations WHERE id_idx_user = :person")->execute($params);
            $this->pdo->prepare('DELETE FROM users_organisations WHERE id_idx_user = :person')->execute(['person' => $person->getId()]);
            $this->pdo->prepare('UPDATE users SET use_active = 0 WHERE id_user = :person AND use_registered = 0')->execute(['person' => $person->getId()]);
            $this->invalidateAll($person->getId());
        });
    }

    private function assertUsable(AccountClaim $claim): void
    {
        if (!$claim->isValid()) {
            throw new Exception('Lien déjà utilisé ou expiré');
        }
    }

    /** Invalide tous les liens de création de compte d'une personne. */
    private function invalidateAll(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE tokens SET tok_used = 1 WHERE id_token IN (SELECT idx_token FROM account_claims WHERE idx_user = :user)');
        $stmt->execute(['user' => $userId]);
    }
}
