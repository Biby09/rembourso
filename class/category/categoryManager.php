<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/category.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/subcategory.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database/transactional.php';

/**
 * Catégories et sous-catégories d'UNE organisation : toutes les lectures sont limitées à cette organisation
 * (une catégorie d'une autre organisation n'est jamais renvoyée).
 */
class CategoryManager
{
    use Transactional;

    private PDO $pdo;
    private Organisation $organisation;

    public function __construct(PDO $pdo, Organisation $organisation)
    {
        $this->pdo = $pdo;
        $this->organisation = $organisation;
    }

    // ---- Catégories ----

    /**
     * Catégories actives, par nom : Category[]
     */
    public function getCategories(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE idx_organisation = :organisation AND cat_active = 1 ORDER BY cat_name');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        $categories = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $category = new Category();
            $category->hydrate($ligne);
            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * Catégorie active de l'organisation (exception sinon).
     */
    public function getCategoryById(int $id): Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id_category = :id AND idx_organisation = :organisation AND cat_active = 1');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception('Category not found with ID: ' . $id);
        }

        $category = new Category();
        $category->hydrate($ligne);
        return $category;
    }

    /**
     * Catégories actives avec leurs sous-catégories (page Paramètres) :
     * [['id' => …, 'name' => …, 'subcategories' => [['id' => …, 'name' => …], ...]], ...]
     */
    public function getCategoriesWithSubcategories(): array
    {
        return array_map(fn(Category $category) => [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'subcategories' => array_map(
                fn(Subcategory $subcategory) => ['id' => $subcategory->getId(), 'name' => $subcategory->getName()],
                $this->getSubcategories($category)
            ),
        ], $this->getCategories());
    }

    public function createCategory(string $name): Category
    {
        $stmt = $this->pdo->prepare('INSERT INTO categories (cat_name, idx_organisation) VALUES (:name, :organisation)');
        $stmt->execute(['name' => $name, 'organisation' => $this->organisation->getId()]);

        return $this->getCategoryById((int) $this->pdo->lastInsertId());
    }

    public function renameCategory(Category $category, string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE categories SET cat_name = :name WHERE id_category = :id AND idx_organisation = :organisation');
        $stmt->execute(['name' => $name, 'id' => $category->getId(), 'organisation' => $this->organisation->getId()]);
    }

    /**
     * Désactive la catégorie et ses sous-catégories (les demandes existantes les gardent). Tout ou rien.
     */
    public function deleteCategory(Category $category): void
    {
        $this->transaction(function () use ($category) {
            $this->pdo->prepare('UPDATE subcategories SET sub_active = 0 WHERE idx_category = :id')->execute(['id' => $category->getId()]);
            $this->pdo->prepare('UPDATE categories SET cat_active = 0 WHERE id_category = :id AND idx_organisation = :organisation')
                ->execute(['id' => $category->getId(), 'organisation' => $this->organisation->getId()]);
        });
    }

    // ---- Sous-catégories ----

    /**
     * Sous-catégories actives d'une catégorie, par nom : Subcategory[]
     */
    public function getSubcategories(Category $category): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subcategories WHERE idx_category = :category AND sub_active = 1 ORDER BY sub_name');
        $stmt->execute(['category' => $category->getId()]);
        $subcategories = [];

        while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $subcategory = new Subcategory();
            $subcategory->hydrate($ligne);
            $subcategories[] = $subcategory;
        }

        return $subcategories;
    }

    /**
     * Sous-catégorie active d'une catégorie active de l'organisation (exception sinon).
     */
    public function getSubcategoryById(int $id): Subcategory
    {
        $stmt = $this->pdo->prepare('SELECT subcategories.* FROM subcategories
            JOIN categories ON categories.id_category = subcategories.idx_category
            WHERE subcategories.id_subcategory = :id AND categories.idx_organisation = :organisation
                AND subcategories.sub_active = 1 AND categories.cat_active = 1');
        $stmt->execute(['id' => $id, 'organisation' => $this->organisation->getId()]);
        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ligne) {
            throw new Exception('Subcategory not found with ID: ' . $id);
        }

        $subcategory = new Subcategory();
        $subcategory->hydrate($ligne);
        return $subcategory;
    }

    /**
     * Nombre de sous-catégories actives de l'organisation (le choix de sous-catégorie n'est affiché que s'il y en a)
     */
    public function countSubcategories(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM subcategories
            JOIN categories ON categories.id_category = subcategories.idx_category
            WHERE categories.idx_organisation = :organisation AND subcategories.sub_active = 1 AND categories.cat_active = 1');
        $stmt->execute(['organisation' => $this->organisation->getId()]);
        return (int) $stmt->fetchColumn();
    }

    public function createSubcategory(Category $category, string $name): Subcategory
    {
        $stmt = $this->pdo->prepare('INSERT INTO subcategories (sub_name, idx_category) VALUES (:name, :category)');
        $stmt->execute(['name' => $name, 'category' => $category->getId()]);

        return $this->getSubcategoryById((int) $this->pdo->lastInsertId());
    }

    public function renameSubcategory(Subcategory $subcategory, string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE subcategories SET sub_name = :name WHERE id_subcategory = :id');
        $stmt->execute(['name' => $name, 'id' => $subcategory->getId()]);
    }

    /**
     * Supprime la sous-catégorie, ou la désactive seulement si des demandes l'utilisent.
     */
    public function deleteSubcategory(Subcategory $subcategory): void
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM repayment_requests WHERE idx_subcategory = :id');
        $stmt->execute(['id' => $subcategory->getId()]);

        $sql = (int) $stmt->fetchColumn() > 0
            ? 'UPDATE subcategories SET sub_active = 0 WHERE id_subcategory = :id'
            : 'DELETE FROM subcategories WHERE id_subcategory = :id';
        $this->pdo->prepare($sql)->execute(['id' => $subcategory->getId()]);
    }
}
