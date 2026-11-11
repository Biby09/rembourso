<?php

/**
 * Organisation (table organisations) et ses réglages.
 */
class Organisation
{
    // Rôles d'un membre (table users_organisations, colonne org_role) : 'member' < 'cashier' < 'admin'
    public const ROLE_LABELS = [
        'admin' => 'Administrateur',
        'cashier' => 'Caissier',
        'member' => 'Membre',
    ];

    private int $id;
    private string $name;
    private bool $categoriesEnabled;      // les demandes sont classées par catégorie
    private bool $subcategoriesEnabled;   // … et par sous-catégorie
    private string $currency;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_organisation'];
        $this->name = $ligne['org_name'];
        $this->categoriesEnabled = (bool) ($ligne['org_use_cat'] ?? false);
        $this->subcategoriesEnabled = (bool) ($ligne['org_use_subcat'] ?? false);
        $this->currency = $ligne['org_currency'] ?? 'CHF';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function areCategoriesEnabled(): bool
    {
        return $this->categoriesEnabled;
    }

    public function areSubcategoriesEnabled(): bool
    {
        return $this->subcategoriesEnabled;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public static function roleLabel(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? 'Rôle inconnu';
    }
}
