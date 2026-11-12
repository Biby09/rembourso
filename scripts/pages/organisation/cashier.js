/**
 * Onglet « Gestion des remboursements » (caissiers / admins) :
 * liste des membres ayant des demandes en attente, puis traitement des demandes d'un membre
 * (accepter / refuser, QR-code de paiement, validation).
 */
import { initReceiptGallery, receiptPreviewHtml } from '../../components/receipt-gallery.js';
import { urgentBadgeHtml } from '../../components/repayment-badges.js';
import { api } from '../../core/api.js';
import { $, $$, dataNumber, delegate, html, raw, setHtml, toElement } from '../../core/dom.js';
import { formatAmount, formatDate } from '../../core/format.js';
import { errorMessage } from '../../core/http.js';
import { hideModal, notify, showModal, withButtonLoading } from '../../core/ui.js';
import { initAccountless } from './accountless.js';
import { organisationContext } from './context.js';

const ALL = 'all';

// ---------------------------------------------------------------------------
// Tableau des membres ayant des demandes en attente
// ---------------------------------------------------------------------------

function membersTableHtml(result) {
    if (!result.success) {
        return html`<p class="text-center text-danger">${errorMessage(result, 'Erreur lors du chargement des demandes.')}</p>`;
    }
    if (!result.data?.length) {
        return html`<p class="text-center text-muted">Aucune demande en attente.</p>`;
    }

    return html`
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="text-start">Utilisateur</th>
                    <th class="text-center">Nombre de demandes</th>
                    <th class="text-end">Montant total</th>
                </tr>
            </thead>
            <tbody>
                ${result.data.map(member => html`
                    <tr>
                        <td class="text-start">
                            <button type="button" class="btn btn-link p-0" data-member-id="${member.id}">${member.full_name}</button>
                            ${Number(member.urgent_count) > 0 ? html`<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                                ${Number(member.urgent_count) > 1 ? `${member.urgent_count} urgentes` : 'Urgent'}</span>` : ''}
                        </td>
                        <td class="text-center">${member.request_count}</td>
                        <td class="text-end">${formatAmount(member.total_pending, result.currency)}</td>
                    </tr>`)}
            </tbody>
        </table>`;
}

// ---------------------------------------------------------------------------
// Modal de traitement des demandes d'un membre
// ---------------------------------------------------------------------------

function repaymentCardHtml(repayment, currency) {
    const radio = (value, label, color, checked = false) => html`
        <input type="radio" class="btn-check" name="status-${repayment.id}" id="status-${repayment.id}-${value}"
            value="${value}" autocomplete="off" ${checked ? html`checked` : ''}>
        <label class="btn btn-outline-${color}" for="status-${repayment.id}-${value}">${label}</label>`;

    return html`
        <div class="card mb-3" data-repayment-card data-repayment-id="${repayment.id}" data-amount="${repayment.amount}"
            data-category-id="${repayment.category?.id ?? ''}" data-subcategory-id="${repayment.subcategory?.id ?? ''}">
            <div class="card-body">
                <h5 class="card-title mb-3">${urgentBadgeHtml(repayment)}${repayment.label}</h5>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Date de l'achat :</strong> ${formatDate(repayment.transaction_date)}</p>
                        <p><strong>Date de dépôt de la demande :</strong> ${formatDate(repayment.requested_at)}</p>
                    </div>
                    <div class="col-md-4">
                        ${repayment.category ? html`<p><strong>Catégorie :</strong> ${repayment.category.name}</p>` : ''}
                        ${repayment.subcategory ? html`<p><strong>Sous-catégorie :</strong> ${repayment.subcategory.name}</p>` : ''}
                        <h6>${formatAmount(repayment.amount, currency)}</h6>
                    </div>
                    <div class="col-md-4 d-flex justify-content-center align-items-center">
                        ${receiptPreviewHtml(repayment, 200)}
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    ${radio('accepted', 'Accepter', 'success')}
                    ${radio('rejected', 'Refuser', 'danger')}
                    ${radio('pending', 'En attente', 'warning', true)}
                </div>
            </div>
        </div>`;
}

/** Catégories / sous-catégories présentes dans les demandes, pour les filtres. */
function collectFilterOptions(repayments) {
    const categories = new Map();
    const subcategories = new Map();
    const categoriesWithoutSubcategory = new Set();
    let hasUncategorized = false;

    repayments.forEach(({ category, subcategory }) => {
        if (category) categories.set(String(category.id), category.name);
        else hasUncategorized = true;

        if (subcategory) {
            subcategories.set(String(subcategory.id), { name: subcategory.name, categoryId: String(category?.id ?? '') });
        } else {
            categoriesWithoutSubcategory.add(String(category?.id ?? ''));
        }
    });

    const hasNoSubcategory = categoriesWithoutSubcategory.size > 0;
    return {
        categories,
        subcategories,
        hasUncategorized,
        hasNoSubcategory,
        categoriesWithoutSubcategory,
        showCategoryFilter: categories.size > 1 || (categories.size === 1 && hasUncategorized),
        showSubcategoryFilter: subcategories.size > 1 || (subcategories.size >= 1 && hasNoSubcategory),
    };
}

const optionsHtml = (allLabel, entries, emptyLabel) => html`
    <option value="${ALL}">${allLabel}</option>
    ${entries.map(([id, name]) => html`<option value="${id}">${name}</option>`)}
    ${emptyLabel ? html`<option value="">${emptyLabel}</option>` : ''}`;

function filtersHtml(options) {
    if (!options.showCategoryFilter && !options.showSubcategoryFilter) return '';

    const filter = (id, label, content) => html`
        <div class="d-flex align-items-center gap-2 flex-nowrap">
            <label for="${id}" class="form-label mb-0 fw-semibold text-nowrap">${label} :</label>
            <select id="${id}" class="form-select form-select-sm w-auto">${content}</select>
        </div>`;

    return html`
        <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
            ${options.showCategoryFilter ? filter('category-filter', 'Catégorie',
                optionsHtml('Toutes les catégories', [...options.categories], options.hasUncategorized && 'Sans catégorie')) : ''}
            ${options.showSubcategoryFilter ? filter('subcategory-filter', 'Sous-catégorie',
                optionsHtml('Toutes les sous-catégories', [...options.subcategories].map(([id, s]) => [id, s.name]), options.hasNoSubcategory && 'Sans sous-catégorie')) : ''}
        </div>`;
}

/** Filtre les cartes par catégorie / sous-catégorie. */
function initFilters(modalBody, options) {
    const categorySelect = $('#category-filter', modalBody);
    const subcategorySelect = $('#subcategory-filter', modalBody);

    const applyFilters = () => {
        const category = categorySelect?.value ?? ALL;
        const subcategory = subcategorySelect?.value ?? ALL;
        $$('[data-repayment-card]', modalBody).forEach(card => {
            const visible = (category === ALL || card.dataset.categoryId === category)
                && (subcategory === ALL || card.dataset.subcategoryId === subcategory);
            card.classList.toggle('d-none', !visible);
        });
    };

    // Les sous-catégories proposées dépendent de la catégorie choisie
    const rebuildSubcategoryOptions = () => {
        if (!subcategorySelect) return;
        const category = categorySelect?.value ?? ALL;

        const entries = [...options.subcategories]
            .filter(([, sub]) => category === ALL || sub.categoryId === category)
            .map(([id, sub]) => [id, sub.name]);
        const showEmpty = category === ALL ? options.hasNoSubcategory : options.categoriesWithoutSubcategory.has(category);

        setHtml(subcategorySelect, optionsHtml('Toutes les sous-catégories', entries, showEmpty && 'Sans sous-catégorie'));
    };

    categorySelect?.addEventListener('change', () => {
        rebuildSubcategoryOptions();
        applyFilters();
    });
    subcategorySelect?.addEventListener('change', applyFilters);
}

function modalDialogHtml(memberName, repayments, currency) {
    const options = collectFilterOptions(repayments);

    return html`
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Demandes de remboursement${memberName ? html` – ${memberName}` : ''}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    ${repayments.length ? html`
                        ${filtersHtml(options)}
                        <div data-repayment-cards>${repayments.map(repayment => repaymentCardHtml(repayment, currency))}</div>`
                    : html`<p class="text-center text-muted">Aucune demande trouvée.</p>`}
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Total à rembourser : <span data-total>${formatAmount(0, currency)}</span></h6>
                    <div>
                        <button type="button" class="btn btn-primary" data-save disabled>Enregistrer</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>`;
}

/** Décisions prises dans le modal : [{ repayment_id, status: 'accepted' | 'rejected' }]. */
function collectDecisions(modal) {
    return $$('[data-repayment-card]', modal)
        .map(card => ({ card, status: $('input[type="radio"]:checked', card)?.value }))
        .filter(({ status }) => status === 'accepted' || status === 'rejected')
        .map(({ card, status }) => ({ repayment_id: dataNumber(card, 'repaymentId'), status, amount: Number(card.dataset.amount) }));
}

// ---------------------------------------------------------------------------

export function initCashier({ organisationId }) {
    const container = $('#cashier-table-container');
    const modal = $('#cashier-tab-dialog-container');
    if (!container || !modal) return;

    async function reloadTable() {
        setHtml(container, membersTableHtml(await api.getMembersWithPendingRepayments(organisationId)));
    }

    async function openMemberModal(memberId, memberName) {
        const result = await api.getMemberPendingRepayments(organisationId, memberId);
        if (!result.success) {
            notify(errorMessage(result, 'Erreur lors du chargement des demandes.'));
            return;
        }

        const { currency } = result;
        modal.replaceChildren(toElement(modalDialogHtml(memberName, result.data ?? [], currency)));
        initFilters($('.modal-body', modal), collectFilterOptions(result.data ?? []));

        const saveButton = $('[data-save]', modal);
        const totalElement = $('[data-total]', modal);
        // 'review' : choix des statuts ; 'confirm' : QR-code affiché, en attente de validation du paiement
        let step = 'review';
        let decisions = [];

        modal.querySelector('.modal-body').addEventListener('change', event => {
            if (event.target.type !== 'radio') return;

            decisions = collectDecisions(modal);
            const total = decisions.filter(d => d.status === 'accepted').reduce((sum, d) => sum + d.amount, 0);
            totalElement.textContent = formatAmount(total, currency);
            saveButton.disabled = decisions.length === 0;
            saveButton.textContent = total > 0 ? 'Suivant' : 'Enregistrer';
        });

        const updates = () => decisions.map(({ repayment_id, status }) => ({ repayment_id, status }));

        const confirmPayment = async () => {
            const confirmResult = await api.processRepayments(organisationId, 'confirm_payment', updates());
            if (!confirmResult.success) {
                notify(errorMessage(confirmResult, 'Erreur lors de la mise à jour des statuts. Veuillez réessayer.'));
                return;
            }
            hideModal(modal);
            reloadTable();
        };

        saveButton.addEventListener('click', () => withButtonLoading(saveButton, 'Traitement...', async () => {
            if (step === 'confirm' || !decisions.some(d => d.status === 'accepted')) {
                await confirmPayment();
                return;
            }

            // Des demandes sont acceptées : on affiche d'abord le QR-code de paiement
            const qrResult = await api.processRepayments(organisationId, 'get_qr', updates());
            if (!qrResult.success) {
                notify(errorMessage(qrResult, 'Erreur lors de la génération du QR-code. Veuillez réessayer.'));
                return;
            }
            setHtml($('.modal-body', modal), raw(qrResult.modalContent)); // HTML échappé côté serveur
            step = 'confirm';
        }).then(() => {
            if (step === 'confirm') saveButton.textContent = 'Valider les remboursements';
        }));

        showModal(modal);
    }

    delegate(container, 'click', '[data-member-id]', (event, button) => {
        openMemberModal(dataNumber(button, 'memberId'), button.textContent.trim());
    });

    reloadTable();
}

// --- Point d'entrée de la page « À rembourser »
const context = organisationContext();
initReceiptGallery();
initCashier(context);
initAccountless(context);
