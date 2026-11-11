<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/token/tokenManager.php';

/**
 * Demande de réinitialisation du mot de passe (tables password_reset_requests et tokens).
 */
class PasswordReset
{
    private int $userId;
    private int $tokenId;
    private string $expiresAt;
    private bool $used;

    public function hydrate(array $ligne): void
    {
        $this->userId = (int) $ligne['idx_user'];
        $this->tokenId = (int) $ligne['idx_token'];
        $this->expiresAt = (string) $ligne['tok_expires_at'];
        $this->used = (bool) $ligne['tok_used'];
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTokenId(): int
    {
        return $this->tokenId;
    }

    /** Ni utilisée, ni expirée */
    public function isValid(): bool
    {
        return TokenManager::isUsable($this->used, $this->expiresAt);
    }
}
