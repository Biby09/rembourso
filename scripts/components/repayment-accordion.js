/**
 * Élément d'accordéon représentant une demande de remboursement (historique de l'utilisateur).
 */
import { receiptPreviewHtml } from './receipt-gallery.js';
import { $, html, setHtml, toElement } from '../core/dom.js';
import { formatAmount, formatDate } from '../core/format.js';

const STATUSES = {
    0: { label: 'En attente', color: 'warning' },
    1: { label: 'Acceptée', color: 'success' },
    2: { label: 'Refusée', color: 'danger' },
};

/** Badge « Urgent » d'une demande urgente encore en attente (vide sinon). */
export function urgentBadgeHtml(repayment) {
    return repayment.urgent && Number(repayment.status) === 0
        ? html`<span class="badge bg-danger me-2"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Urgent</span>`
        : '';
}

export function repaymentStatus(status) {
    return STATUSES[Number(status)] ?? STATUSES[2];
}

const detail = (label, value) => (value ? html`<p><strong>${label} :</strong> ${value}</p>` : '');

/**
 * Crée l'élément d'accordéon d'une demande.
 * La quittance n'est chargée qu'à l'ouverture de l'élément.
 * Les boutons d'action portent data-action="edit" / "delete".
 */
export function createRepaymentAccordionItem(repayment, currency, { open = false, parentId = 'accordion-repayment' } = {}) {
    const status = repaymentStatus(repayment.status);
    const collapseId = `repayment-${repayment.id}-collapse`;

    const item = toElement(html`
        <div class="accordion-item" data-repayment-id="${repayment.id}">
            <h3 class="accordion-header">
                <button class="accordion-button${open ? '' : ' collapsed'}" type="button" data-bs-toggle="collapse"
                    data-bs-target="#${collapseId}" aria-expanded="${String(open)}" aria-controls="${collapseId}">
                    <span class="badge bg-${status.color} me-2">${status.label}</span>
                    ${urgentBadgeHtml(repayment)}
                    ${repayment.label}
                </button>
            </h3>
            <div id="${collapseId}" class="accordion-collapse collapse${open ? ' show' : ''}" data-bs-parent="#${parentId}">
                <div class="accordion-body container">
                    <div class="row mb-3">
                        <div class="col-lg-6 d-flex flex-column justify-content-start align-items-start mt-3">
                            <h4>${repayment.label}</h4>
                            ${detail('Pour', repayment.beneficiary_name)}
                            ${detail('Saisie par', repayment.created_by_name)}
                            ${detail('Montant', formatAmount(repayment.amount, currency))}
                            ${detail('Catégorie', repayment.category?.name)}
                            ${detail('Sous-catégorie', repayment.subcategory?.name)}
                            ${detail("Date de l'achat", formatDate(repayment.transaction_date))}
                            ${detail('Date de dépôt de la demande', formatDate(repayment.requested_at))}
                            ${detail('Date de traitement', formatDate(repayment.batch_date))}
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-center" data-receipt-container></div>
                        </div>
                    </div>
                    ${repayment.editable ? html`
                        <div class="d-flex justify-content-start gap-2">
                            <button type="button" class="btn btn-primary" data-action="edit">Modifier</button>
                            <button type="button" class="btn btn-danger" data-action="delete">Supprimer</button>
                        </div>` : ''}
                </div>
            </div>
        </div>`);

    const receiptContainer = $('[data-receipt-container]', item);
    const loadReceipt = () => {
        if (!receiptContainer.hasChildNodes()) setHtml(receiptContainer, receiptPreviewHtml(repayment));
    };

    if (open) loadReceipt();
    $(`#${collapseId}`, item).addEventListener('show.bs.collapse', loadReceipt);

    return item;
}
