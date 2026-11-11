/**
 * Onglet « Historique des remboursements » (caissiers / admins) : lots de remboursement traités.
 */
import { renderPagination } from '../../components/pagination.js';
import { api } from '../../core/api.js';
import { $, html, setHtml } from '../../core/dom.js';
import { formatAmount, formatDate } from '../../core/format.js';
import { errorMessage } from '../../core/http.js';
import { organisationContext } from './context.js';

const COLUMN_COUNT = 5;

const messageRow = (cssClass, text) => html`<tr><td colspan="${COLUMN_COUNT}" class="text-center ${cssClass}">${text}</td></tr>`;

export function initBatchHistory({ organisationId }) {
    const table = $('#repayment-history-table');
    if (!table) return; // Aucun lot

    const tbody = $('tbody', table);
    const pagination = $('#repayment-history-batches-pagination');
    let currentPage = 1;

    async function loadPage(page) {
        const result = await api.getRepaymentBatches(organisationId, page);

        if (!result.success) {
            setHtml(tbody, messageRow('text-danger', errorMessage(result, "Erreur lors du chargement de l'historique.")));
            return;
        }

        const { repaymentBatches, totalPages, currency } = result.data;
        currentPage = page;

        setHtml(tbody, repaymentBatches.length
            ? repaymentBatches.map(batch => html`
                <tr>
                    <td>${batch.user}</td>
                    <td>${batch.cashier}</td>
                    <td class="text-center">${formatDate(batch.date)}</td>
                    <td class="text-end">${formatAmount(batch.totalAmount, currency)}</td>
                    <td class="text-center">
                        <a href="${api.batchConfirmationUrl(batch.id)}" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener">Confirmation</a>
                    </td>
                </tr>`)
            : messageRow('text-muted', 'Aucun remboursement trouvé.'));

        renderPagination(pagination, { current: currentPage, total: totalPages, onChange: loadPage });
    }

    loadPage(currentPage);
}

// --- Point d'entrée de la page « Historique »
initBatchHistory(organisationContext());
