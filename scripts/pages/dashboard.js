/**
 * Tableau de bord : création d'organisation, réponses aux invitations, galerie des quittances.
 */
import { submitForm } from '../components/ajax-form.js';
import { initReceiptGallery } from '../components/receipt-gallery.js';
import { respondToInvitation } from '../components/invitation-response.js';
import { endpoint } from '../core/api.js';
import { $, dataNumber, delegate } from '../core/dom.js';
import { hideModal, setButtonLoading, showModal } from '../core/ui.js';

initReceiptGallery();

// --- Création d'une organisation
const createDialog = $('#create-organisation-dialog');
const createForm = $('#create-organisation-form');

$('#create-organisation-button')?.addEventListener('click', () => showModal(createDialog));

createForm?.addEventListener('submit', async event => {
    event.preventDefault();

    const result = await submitForm(createForm, endpoint('organisation/create-organisation'), {
        messageContainer: '#formMessage',
        loadingMessage: "Création de l'organisation en cours...",
        successMessage: 'Organisation créée avec succès !',
        errorMessage: "Erreur lors de la création de l'organisation. Veuillez réessayer.",
    });

    // En cas de succès, le serveur renvoie une redirection vers la nouvelle organisation
    if (result.success) hideModal(createDialog);
});

// --- Invitations en attente
delegate($('#invitations-list'), 'click', '[data-invitation-response]', async (event, button) => {
    setButtonLoading(button, true, '...');
    const result = await respondToInvitation({
        accept: button.dataset.invitationResponse === 'accept',
        id: dataNumber(button, 'invitationId'),
    });
    if (!result.success) setButtonLoading(button, false);
});
