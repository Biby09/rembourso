<?php
/*
    Modal de suppression de l'organisation (page Paramètres, scripts/pages/organisation/settings.js) :
    il faut retaper le nom exact de l'organisation pour activer le bouton.
    Prérequis : $organisation.
*/
?>
<div class="modal fade" id="deleteOrganisationDialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-semibold"><i class="bi bi-exclamation-triangle"></i> Supprimer l'organisation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div class="alert alert-danger" role="alert">
                    Cette action est <strong>irréversible</strong>. Toutes les données de
                    <strong data-organisation-name-display><?= e($organisation->getName()) ?></strong> seront définitivement supprimées :
                    demandes de remboursement, reçus, catégories, membres et historique.
                </div>
                <div id="delete-org-message"></div>
                <label for="confirm-organisation-name" class="form-label">
                    Pour confirmer, tapez le nom de l'organisation :
                    <strong data-organisation-name-display><?= e($organisation->getName()) ?></strong>
                </label>
                <input type="text" class="form-control" id="confirm-organisation-name"
                    placeholder="<?= e($organisation->getName()) ?>" autocomplete="off">
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-organisation-btn" disabled>Supprimer définitivement</button>
            </div>
        </div>
    </div>
</div>
