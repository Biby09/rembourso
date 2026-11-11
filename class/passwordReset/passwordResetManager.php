<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/passwordReset/passwordReset.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/token/tokenManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database/transactional.php';

/**
 * Réinitialisation du mot de passe : envoi du lien (valable une heure) et choix du nouveau mot de passe.
 */
class PasswordResetManager
{
    use Transactional;

    private const VALIDITY = '+1 hour';

    private PDO $pdo;
    private TokenManager $tokenManager;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->tokenManager = new TokenManager($pdo);
    }

    /**
     * Demande liée au jeton du lien (exception si le jeton est inconnu).
     */
    public function getResetByToken(string $token): PasswordReset
    {
        $stmt = $this->pdo->prepare('SELECT p.*, t.tok_expires_at, t.tok_used FROM password_reset_requests p
            JOIN tokens t ON t.id_token = p.idx_token WHERE t.tok_token = :token_hash');
        $stmt->execute(['token_hash' => TokenManager::hash($token)]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception('Password reset request not found');
        }

        $reset = new PasswordReset();
        $reset->hydrate($ligne);
        return $reset;
    }

    /**
     * Envoie un lien de réinitialisation au compte.
     */
    public function createReset(User $account): void
    {
        $token = $this->transaction(function () use ($account) {
            [$tokenId, $token] = $this->tokenManager->createToken($account->getId(), self::VALIDITY, true);
            $stmt = $this->pdo->prepare('INSERT INTO password_reset_requests (idx_user, idx_token) VALUES (:user, :token)');
            $stmt->execute(['user' => $account->getId(), 'token' => $tokenId]);
            return $token;
        });

        $message = "Vous avez demandé à réinitialiser votre mot de passe. Pour ce faire, veuillez cliquer sur le lien suivant :\n" . token_link($token);
        sendMail($account->getEmail(), 'Réinitialisation de mot de passe', $message, token: $token, loggedMessage: str_replace($token, '[REDACTED]', $message));
    }

    /**
     * Enregistre le nouveau mot de passe et invalide le lien.
     */
    public function resetPassword(PasswordReset $reset, UserManager $userManager, string $password): User
    {
        if (!$reset->isValid()) {
            throw new Exception('Token invalide ou expiré');
        }

        $user = $userManager->getUserById($reset->getUserId());
        $this->transaction(function () use ($reset, $userManager, $user, $password) {
            $userManager->updatePassword($user, $password);
            $this->tokenManager->markUsed($reset->getTokenId());
        });
        return $user;
    }
}
