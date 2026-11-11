/**
 * Onglet « Historique de mes demandes » : liste paginée, modification et suppression des demandes.
 */
import { renderPagination } from '../../components/pagination.js';
import { api } from '../../core/api.js';
import { $, dataNumber, delegate, html, setHtml } from '../../core/dom.js';
import { errorMessage } from '../../core/http.js';
import { confirmAction, notify } from '../../core/ui.js';
import { initReceiptGallery } from '../../components/receipt-gallery.js';
import { createRepaymentAccordionItem } from '../../components/repayment-accordion.js';
import { openRepaymentEditDialog } from '../../components/repayment-edit-dialog.js';
import { organisationContext } from './context.js';

export function initHistory({ organisationId }) {
    const accordion = $('#accordion-repayment');
    if (!accordion) return; // Aucune demande

    const pagination = $('#repayment-history-pagination');
    const pageCount = dataNumber(pagination, 'pageCount') ?? 1;
    let currentPage = 1;

    async function loadPage(page) {
        const result = await api.getMyRepayments(organisationId, page);

        if (!result.success) {
            setHtml(accordion, html`<p class="text-center text-danger">${errorMessage(result, 'Erreur lors du chargement des demandes.')}</p>`);
            return;
        }

        currentPage = page;
        accordion.replaceChildren(...result.data.map(repayment => createRepaymentAccordionItem(repayment, result.currency)));
        if (!result.data.length) {
            setHtml(accordion, html`<p class="text-center text-muted">Aucune demande trouvée.</p>`);
        }

        renderPagination(pagination, { current: currentPage, total: pageCount, onChange: loadPage });
    }

    delegate(accordion, 'click', '[data-action="edit"]', (event, button) => {
        const item = button.closest('.accordion-item');
        openRepaymentEditDialog(dataNumber(item, 'repaymentId'), {
            onSaved: (repayment, currency) => item.replaceWith(createRepaymentAccordionItem(repayment, currency, { open: true })),
        });
    });

    delegate(accordion, 'click', '[data-action="delete"]', async (event, button) => {
        if (!confirmAction('Êtes-vous sûr de vouloir supprimer cette demande de remboursement ?')) return;

        const item = button.closest('.accordion-item');
        const result = await api.deleteRepayment(dataNumber(item, 'repaymentId'));
        if (result.success) {
            item.remove();
        } else {
            notify(errorMessage(result, 'Erreur lors de la suppression de la demande. Veuillez réessayer.'));
        }
    });

    loadPage(currentPage);
}

// --- Point d'entrée de la page « Mes demandes »
initReceiptGallery();
initHistory(organisationContext());
