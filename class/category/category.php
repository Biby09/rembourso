<?php

/**
 * Catégorie de dépenses d'une organisation (table categories).
 * Une catégorie supprimée est seulement désactivée : les anciennes demandes gardent leur catégorie.
 */
class Category
{
    private int $id;
    private string $name;
    private int $organisationId;
    private bool $active;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_category'];
        $this->name = $ligne['cat_name'];
        $this->organisationId = (int) $ligne['idx_organisation'];
        $this->active = (bool) $ligne['cat_active'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
