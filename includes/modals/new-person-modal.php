<?php
/*
    Modal « Nouvelle personne sans compte » du formulaire de demande (page Nouvelle demande, caissiers) :
    ouvert quand « Nouvelle personne… » est choisi comme bénéficiaire (scripts/pages/organisation/submit.js).
    Les champs appartiennent au formulaire #refund-form (attribut form), même s'ils sont placés dans le modal.
*/
?>
<div class="modal fade" id="newPersonDialog" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 pb-0">
                <div class="w-100">
                    <h5 class="modal-title fw-semibold mb-1">Nouvelle personne sans compte</h5>
                    <p class="text-muted small mb-2">Ces informations sont utilisées pour le remboursement.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-4 text-start">
                <fieldset data-new-person disabled>
                    <?php $personIdPrefix = 'new-person'; $personFormId = 'refund-form'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/accountless-fields.php'; ?>
                </fieldset>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" data-action="confirm-new-person">Valider</button>
            </div>
        </div>
    </div>
</div>
