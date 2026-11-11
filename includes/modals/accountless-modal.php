<?php
/*
    Modal de modification d'une personne sans compte (page « À rembourser », scripts/pages/organisation/accountless.js).
*/
?>
<div class="modal fade" id="accountlessDialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 pb-0">
                <h5 class="modal-title fw-semibold mb-2">Modifier la personne sans compte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-4 text-start">
                <div id="accountless-dialog-message"></div>
                <form id="accountless-form">
                    <?php $personIdPrefix = 'edit-person'; $personFormId = null; include $_SERVER['DOCUMENT_ROOT'] . '/includes/accountless-fields.php'; ?>
                </form>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="accountless-form" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
