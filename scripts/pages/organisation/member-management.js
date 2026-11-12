/**
 * Gestion des membres (admins) : rôles, retrait, invitations.
 * Module utilisé par la page « Membres » (members.js) et par l'assistant de création (init-orga.js).
 */
import { api } from '../../core/api.js';
import { $, dataNumber, delegate, html, setHtml, setVisible } from '../../core/dom.js';
import { errorMessage } from '../../core/http.js';
import { confirmAction, hideModal, notify, showModal, withButtonLoading } from '../../core/ui.js';

const ROLE_LABELS = { member: 'Membre', cashier: 'Caissier', admin: 'Administrateur' };

// ---------------------------------------------------------------------------
// Modal de modification d'un membre
// ---------------------------------------------------------------------------

function initMemberDialog(organisationId) {
    const table = $('#members-table');
    const dialog = $('#manageMemberDialog');
    if (!table || !dialog) return;

    const roleSelect = $('#member-role', dialog);
    const saveButton = $('#save-member-btn', dialog);
    let currentRow = null; // Ligne du tableau du membre en cours de modification

    delegate(table, 'click', '.change-role-btn', (event, button) => {
        currentRow = button.closest('tr');
        // Une personne sans compte ne peut pas se connecter : son rôle reste « Membre »
        const registered = currentRow.dataset.registered !== '0';
        $('#member-name', dialog).textContent = currentRow.dataset.memberName;
        roleSelect.value = currentRow.dataset.role;
        roleSelect.disabled = !registered;
        setVisible(saveButton, registered);
        showModal(dialog);
    });

    saveButton.addEventListener('click', event => withButtonLoading(event.currentTarget, 'Enregistrement...', async () => {
        const role = roleSelect.value;
        const result = await api.updateMemberRole(organisationId, dataNumber(currentRow, 'memberId'), role);

        if (!result.success) {
            notify(errorMessage(result, 'Une erreur est survenue lors de la mise à jour du rôle du membre.'));
            return;
        }

        currentRow.dataset.role = role;
        $('[data-role-label]', currentRow).textContent = ROLE_LABELS[role];
        hideModal(dialog);
    }));

    $('#delete-member-btn', dialog).addEventListener('click', async () => {
        if (!confirmAction("Êtes-vous sûr de vouloir retirer ce membre de l'organisation ?")) return;

        const result = await api.removeMember(organisationId, dataNumber(currentRow, 'memberId'));
        if (!result.success) {
            notify(errorMessage(result, 'Une erreur est survenue lors de la suppression du membre.'));
            return;
        }

        currentRow.remove();
        hideModal(dialog);
    });
}

// ---------------------------------------------------------------------------
// Invitations
// ---------------------------------------------------------------------------

function invitationsHtml(result) {
    if (!result.success) {
        return html`<p class="text-center text-danger">${errorMessage(result, 'Erreur lors du chargement des invitations.')}</p>`;
    }
    if (!result.data.length) {
        return html`<p>Aucune invitation en cours.</p>`;
    }

    return html`
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="width: 30%;" class="text-center">Nom</th>
                    <th style="width: 40%;" class="text-center">Rôle</th>
                    <th style="width: 30%;" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                ${result.data.map(invitation => html`
                    <tr data-invitation-id="${invitation.id}">
                        <td class="text-center">${invitation.name}</td>
                        <td class="text-center">${invitation.role}</td>
                        <td class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-action="resend-invitation">Renvoyer le mail</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-invitation">Supprimer</button>
                        </td>
                    </tr>`)}
            </tbody>
        </table>`;
}

function initInvitations(organisationId) {
    const container = $('#invitations-container');
    if (!container) return null;

    const reload = async () => setHtml(container, invitationsHtml(await api.getInvitations(organisationId)));

    delegate(container, 'click', '[data-action="resend-invitation"]', (event, button) => withButtonLoading(button, 'Envoi...', async () => {
        const result = await api.resendInvitation(dataNumber(button.closest('tr'), 'invitationId'));
        notify(result.success ? 'Email renvoyé avec succès !' : errorMessage(result, "Impossible de renvoyer l'email."));
    }));

    delegate(container, 'click', '[data-action="delete-invitation"]', async (event, button) => {
        if (!confirmAction('Êtes-vous sûr de vouloir supprimer cette invitation ?')) return;

        const row = button.closest('tr');
        const result = await api.deleteInvitation(dataNumber(row, 'invitationId'));
        if (result.success) {
            row.remove();
        } else {
            notify(errorMessage(result, "Impossible de supprimer l'invitation."));
        }
    });

    reload();
    return reload;
}

// ---------------------------------------------------------------------------
// Invitation d'un nouveau membre
// ---------------------------------------------------------------------------

function initAddMemberDialog(organisationId, reloadInvitations) {
    const dialog = $('#addMemberDialog');
    const form = $('#add-member-form');
    if (!dialog || !form) return;

    dialog.addEventListener('show.bs.modal', () => form.reset());
    dialog.addEventListener('shown.bs.modal', () => form.elements.email.focus());

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const submitButton = $('button[type="submit"]', dialog);

        await withButtonLoading(submitButton, 'Envoi...', async () => {
            const result = await api.inviteMember(organisationId, form.elements.email.value.trim(), form.elements.role.value);
            if (!result.success) {
                notify(errorMessage(result, "Une erreur est survenue lors de l'envoi de l'invitation."));
                return;
            }

            hideModal(dialog);
            notify('Invitation envoyée avec succès !');
            reloadInvitations?.();
        });
    });
}

export function initMembers({ organisationId }) {
    initMemberDialog(organisationId);
    const reloadInvitations = initInvitations(organisationId);
    initAddMemberDialog(organisationId, reloadInvitations);
}
