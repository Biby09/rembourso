/**
 * Devise de l'organisation (includes/currency-setup.php) : enregistrée dès qu'elle change.
 * Module utilisé par la page « Paramètres » (settings.js) et par l'assistant de création (init-orga.js).
 */
import { api } from '../../core/api.js';
import { $ } from '../../core/dom.js';
import { errorMessage } from '../../core/http.js';
import { showAlert } from '../../core/ui.js';

const MESSAGE_TIMEOUT = 4000;

export function initCurrency({ organisationId }) {
    const select = $('#organisation-currency');
    if (!select) return;

    const message = $('#currency-message');
    let savedValue = select.value;

    select.addEventListener('change', async () => {
        select.disabled = true;
        const result = await api.updateOrganisationSetting(organisationId, 'currency', select.value);
        select.disabled = false;

        if (!result.success) {
            select.value = savedValue; // la devise enregistrée n'a pas changé
            showAlert(message, 'danger', errorMessage(result, "Erreur lors de l'enregistrement de la devise."), { timeout: MESSAGE_TIMEOUT });
            return;
        }

        savedValue = result.currency;
        showAlert(message, 'success', `Devise enregistrée : ${result.currency}.`, { timeout: MESSAGE_TIMEOUT });
    });
}
