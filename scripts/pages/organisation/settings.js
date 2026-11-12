/**
 * Paramètres de l'organisation (admins) : renommage et suppression.
 */
import { api } from '../../core/api.js';
import { $, $$ } from '../../core/dom.js';
import { errorMessage } from '../../core/http.js';
import { clearAlert, showAlert, withButtonLoading } from '../../core/ui.js';
import { initCategories } from './categories.js';
import { organisationContext } from './context.js';
import { initCurrency } from './currency.js';

const MESSAGE_TIMEOUT = 4000;

export function initSettings(context) {
    const renameButton = $('#rename-organisation-button');
    if (!renameButton) return; // Pas admin

    const nameInput = $('#organisation-name');
    const renameMessage = $('#rename-org-message');

    // --- Renommage
    renameButton.addEventListener('click', () => {
        const newName = nameInput.value.trim();
        if (!newName) {
            showAlert(renameMessage, 'danger', 'Le nom ne peut pas être vide.', { timeout: MESSAGE_TIMEOUT });
            return;
        }
        if (newName === context.organisationName) {
            showAlert(renameMessage, 'info', "Le nom est identique à l'actuel.", { timeout: MESSAGE_TIMEOUT });
            return;
        }

        return withButtonLoading(renameButton, 'Renommage...', async () => {
            const result = await api.renameOrganisation(context.organisationId, newName);
            if (!result.success) {
                showAlert(renameMessage, 'danger', errorMessage(result, 'Erreur lors du renommage.'), { timeout: MESSAGE_TIMEOUT });
                return;
            }

            context.organisationName = result.name;
            nameInput.value = result.name;
            document.title = result.name;
            $$('[data-organisation-name-display]').forEach(element => { element.textContent = result.name; });
            $('#confirm-organisation-name').placeholder = result.name;
            showAlert(renameMessage, 'success', 'Organisation renommée avec succès.', { timeout: MESSAGE_TIMEOUT });
        });
    });

    // --- Suppression (l'utilisateur doit retaper le nom exact de l'organisation)
    const deleteDialog = $('#deleteOrganisationDialog');
    const confirmInput = $('#confirm-organisation-name', deleteDialog);
    const confirmButton = $('#confirm-delete-organisation-btn', deleteDialog);
    const deleteMessage = $('#delete-org-message', deleteDialog);

    deleteDialog.addEventListener('show.bs.modal', () => {
        confirmInput.value = '';
        confirmButton.disabled = true;
        clearAlert(deleteMessage);
    });

    confirmInput.addEventListener('input', () => {
        confirmButton.disabled = confirmInput.value.trim() !== context.organisationName;
    });

    confirmButton.addEventListener('click', () => withButtonLoading(confirmButton, 'Suppression...', async () => {
        const result = await api.deleteOrganisation(context.organisationId);
        if (result.success) {
            window.location.href = '/dashboard';
            return;
        }
        showAlert(deleteMessage, 'danger', errorMessage(result, 'Erreur lors de la suppression.'));
    }));
}

// --- Point d'entrée de la page « Paramètres de l'organisation »
const context = organisationContext();
initCategories(context);
initCurrency(context);
initSettings(context);
