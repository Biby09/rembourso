/**
 * Onglet caissier, section « Personnes sans compte » : bénéficiaires saisis par un caissier.
 * Modification de leurs informations et envoi du lien de création de compte.
 */
import { initCountrySelects } from '../../components/country-select.js';
import { fillPersonFields, readPersonFields } from '../../components/person-fields.js';
import { api } from '../../core/api.js';
import { $, dataNumber, delegate, html, setHtml } from '../../core/dom.js';
import { formatDate } from '../../core/format.js';
import { errorMessage } from '../../core/http.js';
import { clearAlert, hideModal, notify, showAlert, showModal, withButtonLoading } from '../../core/ui.js';

function claimButtonHtml(person) {
    if (!person.email) {
        return html`<button type="button" class="btn btn-sm btn-outline-primary" disabled
            title="Renseignez l'adresse e-mail pour envoyer le lien">Envoyer le lien</button>`;
    }
    return html`<button type="button" class="btn btn-sm btn-outline-primary" data-action="send-claim">
        ${person.last_claim_sent_at ? 'Renvoyer le lien' : 'Envoyer le lien'}</button>`;
}

function accountlessHtml(result) {
    if (!result.success) {
        return html`<p class="text-danger">${errorMessage(result, 'Erreur lors du chargement des personnes sans compte.')}</p>`;
    }
    if (!result.data.length) {
        return html`<p class="text-muted">Aucune personne sans compte. Pour en ajouter une, choisissez « Nouvelle personne sans compte » dans l'onglet « Déposer une demande ».</p>`;
    }

    return html`
        <div class="table-responsive">
            <table class="table table-striped align-middle text-start">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>E-mail</th>
                        <th>Lien envoyé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${result.data.map(person => html`
                        <tr data-person-id="${person.id}">
                            <td>${person.name}</td>
                            <td>${person.email || html`<span class="text-muted fst-italic">non renseigné</span>`}</td>
                            <td>${person.last_claim_sent_at ? formatDate(person.last_claim_sent_at) : '—'}</td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-action="edit">Modifier</button>
                                ${claimButtonHtml(person)}
                            </td>
                        </tr>`)}
                </tbody>
            </table>
        </div>`;
}

export function initAccountless({ organisationId }) {
    const container = $('#accountless-container');
    const dialog = $('#accountlessDialog');
    if (!container || !dialog) return;

    const form = $('#accountless-form', dialog);
    const dialogMessage = $('#accountless-dialog-message', dialog);
    let people = new Map(); // id => données de la personne
    let editedId = null;
    let countriesReady = null;

    const reload = async () => {
        const result = await api.getAccountlessMembers(organisationId);
        people = new Map((result.data ?? []).map(person => [person.id, person]));
        setHtml(container, accountlessHtml(result));
    };

    // --- Modification
    delegate(container, 'click', '[data-action="edit"]', async (event, button) => {
        editedId = dataNumber(button.closest('tr'), 'personId');
        clearAlert(dialogMessage);
        countriesReady ??= initCountrySelects(dialog);
        await countriesReady;
        fillPersonFields(form, people.get(editedId) ?? {});
        showModal(dialog);
    });

    form.addEventListener('submit', event => {
        event.preventDefault();
        const submitButton = $('button[type="submit"]', dialog);

        return withButtonLoading(submitButton, 'Enregistrement...', async () => {
            const result = await api.updateAccountlessMember(organisationId, editedId, readPersonFields(form));
            if (!result.success) {
                showAlert(dialogMessage, 'danger', errorMessage(result, 'Erreur lors de la modification.'));
                return;
            }
            hideModal(dialog);
            notify('Informations mises à jour.');
            reload();
        });
    });

    // --- Lien de création de compte
    delegate(container, 'click', '[data-action="send-claim"]', (event, button) => withButtonLoading(button, 'Envoi...', async () => {
        const person = people.get(dataNumber(button.closest('tr'), 'personId'));
        const result = await api.sendAccountClaim(organisationId, person.id);
        if (!result.success) {
            notify(errorMessage(result, "Impossible d'envoyer le lien."));
            return;
        }
        notify(`Lien de création de compte envoyé à ${person.email}.`);
        reload();
    }));

    reload();
}
