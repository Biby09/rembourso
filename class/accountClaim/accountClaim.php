<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/token/tokenManager.php';

/**
 * Lien de création de compte envoyé à une personne « sans compte » (tables account_claims et tokens).
 */
class AccountClaim
{
    private int $userId;              // personne sans compte
    private int $organisationId;      // organisation qui a envoyé le lien
    private int $tokenId;
    private string $expiresAt;
    private bool $used;

    public function hydrate(array $ligne): void
    {
        $this->userId = (int) $ligne['idx_user'];
        $this->organisationId = (int) $ligne['idx_organisation'];
        $this->tokenId = (int) $ligne['idx_token'];
        $this->expiresAt = (string) $ligne['tok_expires_at'];
        $this->used = (bool) $ligne['tok_used'];
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function getTokenId(): int
    {
        return $this->tokenId;
    }

    /** Ni utilisé, ni expiré */
    public function isValid(): bool
    {
        return TokenManager::isUsable($this->used, $this->expiresAt);
    }
}
