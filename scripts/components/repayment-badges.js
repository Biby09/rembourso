/**
 * Badges d'une demande de remboursement affichée en JavaScript (page « À rembourser »).
 * Même rendu que includes/repayment-status-badge.php (pages affichées en PHP).
 */
import { html } from '../core/dom.js';

/** Badge « Urgent » d'une demande urgente encore en attente (vide sinon). */
export function urgentBadgeHtml(repayment) {
    return repayment.urgent && Number(repayment.status) === 0
        ? html`<span class="badge bg-danger me-2"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Urgent</span>`
        : '';
}
