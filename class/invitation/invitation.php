<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/token/tokenManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/organisation/organisation.php';

/**
 * Invitation d'un compte à rejoindre une organisation (tables invitations et tokens).
 */
class Invitation
{
    private int $id;
    private int $organisationId;
    private int $userId;              // compte invité
    private string $role;
    private int $tokenId;
    private string $token;            // jeton en clair (lien /token.php?token=…)
    private string $expiresAt;
    private bool $used;
    private ?string $lastMailedAt;    // dernier e-mail envoyé (limite les relances)
    private ?string $organisationName;
    private ?string $userName;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_invitation'];
        $this->organisationId = (int) $ligne['idx_organisation'];
        $this->userId = (int) $ligne['idx_user'];
        $this->role = $ligne['inv_role'];
        $this->tokenId = (int) $ligne['idx_token'];
        $this->token = $ligne['tok_token'];
        $this->expiresAt = (string) $ligne['tok_expires_at'];
        $this->used = (bool) $ligne['tok_used'];
        $this->lastMailedAt = $ligne['last_mailed_at'] ?? null;
        $this->organisationName = $ligne['org_name'] ?? null;
        $this->userName = $ligne['user_name'] ?? null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getRoleLabel(): string
    {
        return Organisation::roleLabel($this->role);
    }

    public function getTokenId(): int
    {
        return $this->tokenId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getLastMailedAt(): ?string
    {
        return $this->lastMailedAt;
    }

    public function getOrganisationName(): ?string
    {
        return $this->organisationName;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /** Ni utilisée, ni expirée */
    public function isValid(): bool
    {
        return TokenManager::isUsable($this->used, $this->expiresAt);
    }
}
