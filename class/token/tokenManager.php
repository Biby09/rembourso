<?php

/**
 * Jetons des liens envoyés par e-mail (table tokens) : invitations, réinitialisation de mot de passe,
 * création de compte d'une personne sans compte. Le lien mène à /token.php?token=…
 *
 * Un jeton « haché » n'est stocké que sous forme d'empreinte SHA-256 : même avec un accès à la base,
 * on ne peut pas reconstituer le lien.
 */
class TokenManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crée un jeton valable $validity (ex. '+30 days') pour l'utilisateur.
     * Renvoie [identifiant du jeton, jeton en clair à mettre dans le lien].
     */
    public function createToken(int $userId, string $validity, bool $hashed, int $bytes = 16): array
    {
        $token = bin2hex(random_bytes($bytes));
        $stmt = $this->pdo->prepare('INSERT INTO tokens (tok_token, tok_user, tok_expires_at) VALUES (:token, :user, :expires_at)');
        $stmt->execute([
            'token' => $hashed ? self::hash($token) : $token,
            'user' => $userId,
            'expires_at' => (new DateTime())->modify($validity)->format('Y-m-d H:i:s'),
        ]);

        return [(int) $this->pdo->lastInsertId(), $token];
    }

    public function markUsed(int $tokenId): void
    {
        $this->pdo->prepare('UPDATE tokens SET tok_used = 1 WHERE id_token = :id')->execute(['id' => $tokenId]);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Un jeton est utilisable s'il n'a pas servi et n'a pas expiré.
     */
    public static function isUsable(bool $used, string $expiresAt): bool
    {
        return !$used && new DateTime($expiresAt) > new DateTime();
    }
}
