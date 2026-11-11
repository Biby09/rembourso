<?php
/*
    Modals des catégories (page Paramètres, scripts/pages/organisation/categories.js) :
    - #editCategoryDialog : nom de la catégorie et ses sous-catégories ;
    - #addCategoryDialog  : nouvelle catégorie.
*/
?>
<div class="modal fade" id="editCategoryDialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 pb-0">
                <h5 class="modal-title fw-semibold mb-2">Modifier la catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <form id="edit-category-form">
                    <div class="mb-3">
                        <label for="category-name" class="col-form-label">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="category-name" placeholder="Entrez le nom de la catégorie" maxlength="64" required>
                    </div>
                    <fieldset class="mt-3" data-subcategories-editor>
                        <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                            <legend class="col-form-label">Sous-catégories</legend>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-subcategory">Ajouter</button>
                        </div>
                        <div class="d-flex flex-column align-items-center gap-3" data-subcategory-rows></div>
                    </fieldset>
                    <div class="alert alert-light border text-secondary mt-3 mb-0 d-none" role="alert" data-subcategories-disabled>
                        Les sous-catégories sont désactivées pour cette organisation et ne sont pas modifiables.
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="edit-category-form" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addCategoryDialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 pb-0">
                <h5 class="modal-title fw-semibold mb-2">Ajouter une catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <form id="add-category-form">
                    <div class="mb-3">
                        <label for="new-category-name" class="col-form-label">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="new-category-name" placeholder="Entrez le nom de la catégorie" maxlength="64" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="add-category-form" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </div>
</div>
