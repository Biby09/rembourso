<?php

/**
 * Sous-catégorie d'une catégorie (table subcategories).
 */
class Subcategory
{
    private int $id;
    private string $name;
    private int $categoryId;
    private bool $active;

    public function hydrate(array $ligne): void
    {
        $this->id = (int) $ligne['id_subcategory'];
        $this->name = $ligne['sub_name'];
        $this->categoryId = (int) $ligne['idx_category'];
        $this->active = (bool) $ligne['sub_active'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
