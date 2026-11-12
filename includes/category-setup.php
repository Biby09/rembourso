<?php
/*
    Gestion des catégories et sous-catégories (page Paramètres et assistant de création, étape 2) :
    activation, liste et bouton d'ajout (scripts/pages/organisation/categories.js).
    La page doit aussi inclure includes/modals/category-modals.php.
    Prérequis : $organisation.
*/
?>
<div class="d-flex flex-wrap gap-3 mb-3">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" name="use-cat" id="use-cat" <?= $organisation->areCategoriesEnabled() ? 'checked' : '' ?>>
        <label class="form-check-label" for="use-cat">Activer les catégories</label>
    </div>
    <div class="form-check form-switch<?= $organisation->areCategoriesEnabled() ? '' : ' d-none' ?>">
        <input class="form-check-input" type="checkbox" role="switch" name="use-subcat" id="use-subcat" <?= $organisation->areSubcategoriesEnabled() ? 'checked' : '' ?>>
        <label class="form-check-label" for="use-subcat">Activer les sous-catégories</label>
    </div>
</div>
<!-- Rempli par scripts/pages/organisation/categories.js -->
<div id="categories-management-container" class="mt-4 text-center"></div>
<button type="button" id="add-category-button" class="btn btn-primary">Ajouter une catégorie</button>
