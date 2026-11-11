<?php
/*
    Modal de création d'une organisation (tableau de bord, scripts/pages/dashboard.js).
*/
?>
<div class="modal fade" id="create-organisation-dialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 pb-0">
                <h3 class="modal-title fs-5 fw-semibold mb-2">Créer une organisation</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div id="formMessage"></div>
                <form id="create-organisation-form">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <div class="mb-3">
                        <label for="org-name" class="form-label">Nom de l'organisation</label>
                        <input type="text" class="form-control" id="org-name" name="org_name" maxlength="100" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="create-organisation-form" class="btn btn-primary">Créer</button>
            </div>
        </div>
    </div>
</div>
