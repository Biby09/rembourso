<?php
/*
    Modals de la page Membres (scripts/pages/organisation/members.js) :
    - #manageMemberDialog : rôle d'un membre et retrait de l'organisation ;
    - #addMemberDialog    : invitation d'un compte par son adresse e-mail.
*/
?>
<div class="modal fade" id="manageMemberDialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 pb-0">
                <h5 class="modal-title fw-semibold mb-2">Gestion des membres</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <p class="pb-3 mb-0"><strong id="member-name" class="fs-5"></strong></p>
                <label for="member-role" class="form-label mb-2">Modifier le rôle</label>
                <select id="member-role" class="form-select form-select-lg border-1">
                    <?php foreach (Organisation::ROLE_LABELS as $roleKey => $roleLabel): ?>
                        <option value="<?= $roleKey ?>"><?= e($roleLabel) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted mt-2 d-block">Le rôle définit les permissions de cet utilisateur dans l'organisation.</small>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" id="delete-member-btn" class="btn btn-outline-danger btn-sm me-auto">
                    <i class="bi bi-trash"></i> Retirer de l'organisation
                </button>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="save-member-btn" class="btn btn-primary">Sauvegarder</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addMemberDialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 pb-0">
                <h5 class="modal-title fw-semibold mb-2">Inviter un utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <form id="add-member-form">
                    <div class="mb-3">
                        <label for="user-email" class="form-label">Adresse e-mail de l'utilisateur</label>
                        <input type="email" class="form-control" id="user-email" name="email"
                            placeholder="Entrez l'adresse e-mail de l'utilisateur" required>
                    </div>
                    <div class="mb-3">
                        <label for="user-role" class="form-label">Rôle</label>
                        <select id="user-role" name="role" class="form-select border-1">
                            <?php foreach (Organisation::ROLE_LABELS as $roleKey => $roleLabel): ?>
                                <option value="<?= $roleKey ?>" <?= $roleKey === 'member' ? 'selected' : '' ?>><?= e($roleLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted mt-2 d-block">Le rôle définit les permissions de cet utilisateur dans l'organisation.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="add-member-form" class="btn btn-primary">Inviter le membre</button>
            </div>
        </div>
    </div>
</div>
