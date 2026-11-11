/**
 * Modal de modification d'une demande de remboursement.
 * Le HTML du modal est généré par /api/repayment/edit-dialog.php.
 */
import { submitForm } from './ajax-form.js';
import { appendPreparedReceipts } from './receipt-compressor.js';
import { validateReceiptFiles } from './receipt-files.js';
import { bindSubcategorySelect } from './subcategory-select.js';
import { api, endpoint } from '../core/api.js';
import { $, $$, delegate, html, raw, toElement } from '../core/dom.js';
import { errorMessage } from '../core/http.js';
import { getModal, hideModal, notify, showAlert, showModal } from '../core/ui.js';

/**
 * Gère la liste des quittances du formulaire : retrait des quittances existantes
 * (leur input caché retained_receipt_ids[] disparaît avec la ligne) et ajout de nouveaux fichiers.
 * Retourne une fonction donnant les nouveaux fichiers à envoyer.
 */
function initReceiptList(form) {
    const list = $('[data-receipt-list]', form);
    const fileInput = $('[data-receipt-input]', form);
    const newFiles = [];

    const renderNewFiles = () => {
        $$('[data-new-receipt]', list).forEach(item => item.remove());
        newFiles.forEach((file, index) => list.append(toElement(html`
            <li class="list-group-item d-flex justify-content-between align-items-center" data-new-receipt="${index}">
                <span>${file.name}</span>
                <button type="button" class="btn-close" data-receipt-remove aria-label="Supprimer"></button>
            </li>`)));
    };

    $('[data-add-receipts]', form).addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        const error = validateReceiptFiles(fileInput.files);
        if (error) {
            notify(error);
        } else {
            newFiles.push(...fileInput.files);
            renderNewFiles();
        }
        fileInput.value = '';
    });

    delegate(list, 'click', '[data-receipt-remove]', (event, button) => {
        const item = button.closest('li');
        if (item.dataset.newReceipt !== undefined) {
            newFiles.splice(Number(item.dataset.newReceipt), 1);
            renderNewFiles();
        } else {
            item.remove();
        }
    });

    return {
        newFiles: () => newFiles,
        count: () => $$('li', list).length,
    };
}

/**
 * Ouvre le modal de modification. `onSaved(updatedRepayment, currency)` est appelé après l'enregistrement.
 */
export async function openRepaymentEditDialog(repaymentId, { onSaved = () => {} } = {}) {
    const result = await api.getRepaymentEditDialog(repaymentId);
    if (!result.success || !result.html) {
        notify(errorMessage(result, 'Erreur lors du chargement du formulaire de modification. Veuillez réessayer.'));
        return;
    }

    const host = $('#repaymentEditDialog');
    const modal = toElement(raw(result.html)); // HTML généré et échappé côté serveur
    host.replaceChildren(modal);

    // Le modal est recréé à chaque ouverture : on le détruit à la fermeture
    modal.addEventListener('hidden.bs.modal', () => {
        getModal(modal)?.dispose();
        modal.remove();
    });

    const form = $('#edit-repayment-form', modal);
    const message = $('[data-edit-message]', modal);
    const receipts = initReceiptList(form);

    bindSubcategorySelect(form.elements.category, form.elements.subcategory, {
        container: $('[data-subcategory-container]', form),
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (receipts.count() === 0) {
            showAlert(message, 'danger', 'Au moins une quittance est requise.');
            return;
        }

        // Nouvelles quittances : réduites / converties dans le navigateur avant l'envoi
        const formData = new FormData(form);
        const ready = await appendPreparedReceipts(formData, receipts.newFiles(), {
            messageContainer: message,
            buttons: $$('button[type="submit"]', modal),
        });
        if (!ready) return;

        const saveResult = await submitForm(form, endpoint('repayment/edit-request'), {
            messageContainer: message,
            formData,
            loadingMessage: 'Enregistrement en cours...',
            successMessage: 'Demande modifiée avec succès.',
            errorMessage: "Erreur lors de l'enregistrement de la demande.",
            resetOnSuccess: false,
        });

        if (saveResult.success) {
            hideModal(modal);
            onSaved(saveResult.updated_repayment, saveResult.currency);
        }
    });

    showModal(modal);
}
