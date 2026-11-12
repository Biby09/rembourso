/**
 * Point d'entrée de la page « Mes demandes » (/dashboard/organisation/requests/).
 * La liste et la pagination sont affichées en PHP (includes/repayment-item.php) ;
 * ce script gère seulement la galerie des quittances, la modification et la suppression.
 * Après une modification ou une suppression, la page est rechargée (la demande modifiée reste ouverte).
 */
import { initReceiptGallery } from '../../components/receipt-gallery.js';
import { openRepaymentEditDialog } from '../../components/repayment-edit-dialog.js';
import { api } from '../../core/api.js';
import { $, dataNumber, delegate } from '../../core/dom.js';
import { errorMessage } from '../../core/http.js';
import { confirmAction, notify } from '../../core/ui.js';

const accordion = $('#accordion-repayment');
const repaymentId = button => dataNumber(button.closest('.accordion-item'), 'repaymentId');

/** Recharge la page en rouvrant la demande (#repayment-12-collapse) */
function reloadWithOpen(id) {
    window.location.hash = `repayment-${id}-collapse`;
    window.location.reload();
}

initReceiptGallery();

if (accordion) {
    delegate(accordion, 'click', '[data-action="edit"]', (event, button) => {
        const id = repaymentId(button);
        openRepaymentEditDialog(id, { onSaved: () => reloadWithOpen(id) });
    });

    delegate(accordion, 'click', '[data-action="delete"]', async (event, button) => {
        if (!confirmAction('Êtes-vous sûr de vouloir supprimer cette demande de remboursement ?')) return;

        const result = await api.deleteRepayment(repaymentId(button));
        if (result.success) {
            window.location.hash = '';
            window.location.reload();
        } else {
            notify(errorMessage(result, 'Erreur lors de la suppression de la demande. Veuillez réessayer.'));
        }
    });

    // Demande à rouvrir après un rechargement (#repayment-12-collapse)
    const opened = document.getElementById(window.location.hash.slice(1));
    if (opened && accordion.contains(opened)) {
        window.bootstrap.Collapse.getOrCreateInstance(opened, { toggle: false }).show();
    }
}
