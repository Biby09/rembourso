/**
 * Aperçu des quittances et galerie plein écran (modal).
 *
 * Tout élément `.receipt-gallery-trigger` portant data-repayment-id,
 * data-receipt-count et (optionnel) data-receipt-index ouvre la galerie au clic.
 */
import { api } from '../core/api.js';
import { $, html, setHtml, toElement } from '../core/dom.js';
import { showModal } from '../core/ui.js';

const MODAL_ID = 'receipt-gallery-modal';

/** HTML de l'aperçu de la première quittance d'une demande. */
export function receiptPreviewHtml(repayment, maxHeight = 400) {
    const count = Number(repayment.receipt_count) || 1;
    const trigger = (classes, content) => html`
        <button type="button" class="receipt-gallery-trigger ${classes}"
            data-repayment-id="${repayment.id}" data-receipt-count="${count}">${content}</button>`;

    return html`
        <div class="text-center">
            ${count > 1 ? html`<div class="mb-2">${trigger('btn btn-link btn-sm p-0', `Voir les autres quittances (${count})`)}</div>` : ''}
            ${trigger('border-0 bg-transparent p-0', html`
                <img class="receipt_image mw-100" style="max-height: ${maxHeight}px; object-fit: contain;"
                    src="${api.receiptUrl(repayment.id)}" alt="Quittance">`)}
        </div>`;
}

function getGalleryModal() {
    let modal = document.getElementById(MODAL_ID);
    if (modal) return modal;

    modal = toElement(html`
        <div id="${MODAL_ID}" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Quittance <span data-receipt-position></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body text-center" data-receipt-content></div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-receipt-step="-1">Précédente</button>
                        <button type="button" class="btn btn-outline-secondary" data-receipt-step="1">Suivante</button>
                    </div>
                </div>
            </div>
        </div>`);
    document.body.appendChild(modal);

    modal.addEventListener('click', event => {
        const stepButton = event.target.closest('[data-receipt-step]');
        if (!stepButton) return;
        const { state } = modal;
        state.index = Math.min(Math.max(state.index + Number(stepButton.dataset.receiptStep), 0), state.count - 1);
        renderGallery(modal);
    });

    return modal;
}

function renderGallery(modal) {
    const { repaymentId, index, count } = modal.state;

    setHtml($('[data-receipt-content]', modal), html`
        <img src="${api.receiptUrl(repaymentId, index)}" alt="Quittance ${index + 1}" class="img-fluid" style="max-height: 75vh;">`);
    $('[data-receipt-position]', modal).textContent = count > 1 ? `${index + 1} / ${count}` : '';
    $('[data-receipt-step="-1"]', modal).disabled = index === 0;
    $('[data-receipt-step="1"]', modal).disabled = index >= count - 1;
}

export function openReceiptGallery(repaymentId, count = 1, index = 0) {
    const modal = getGalleryModal();
    modal.state = { repaymentId, count: Math.max(1, count), index };
    renderGallery(modal);
    showModal(modal);
}

let initialised = false;

/** Active l'ouverture de la galerie au clic sur les `.receipt-gallery-trigger` (une seule fois par page). */
export function initReceiptGallery() {
    if (initialised) return;
    initialised = true;

    document.addEventListener('click', event => {
        const trigger = event.target.closest('.receipt-gallery-trigger');
        if (!trigger) return;

        openReceiptGallery(
            trigger.dataset.repaymentId,
            Number(trigger.dataset.receiptCount) || 1,
            Number(trigger.dataset.receiptIndex) || 0,
        );
    });
}
