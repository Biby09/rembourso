/**
 * Onglet « Déposer une demande » : formulaire de demande de remboursement.
 * Les caissiers et admins peuvent saisir la demande pour quelqu'un d'autre
 * (membre de l'organisation ou nouvelle personne sans compte).
 */
import { submitForm } from '../../components/ajax-form.js';
import { endpoint } from '../../core/api.js';
import { initCountrySelects } from '../../components/country-select.js';
import { appendPreparedReceipts } from '../../components/receipt-compressor.js';
import { validateReceiptFiles } from '../../components/receipt-files.js';
import { bindSubcategorySelect, refreshSubcategories } from '../../components/subcategory-select.js';
import { $, $$, delegate, setVisible } from '../../core/dom.js';
import { todayIso } from '../../core/format.js';
import { hideModal, notify, showAlert, showModal } from '../../core/ui.js';

/**
 * Sélecteur « Bénéficiaire » (caissiers et admins).
 * « Nouvelle personne… » ouvre une modale pour saisir ses informations ; les champs
 * de la modale appartiennent au formulaire de demande (attribut form="refund-form").
 * Retourne une fonction qui ajoute une personne créée à la liste, ou null.
 */
function initBeneficiaryPicker(form) {
    const select = form.elements.beneficiary;
    const dialog = $('#newPersonDialog');
    if (!select || !dialog) return null; // Pas caissier

    const personFieldset = $('[data-new-person]', dialog);
    const accountlessOptions = $('[data-accountless-options]', form);
    const memberHint = $('[data-member-hint]', form);
    const personSummary = $('[data-new-person-summary]', form);
    let previousValue = select.value; // Choix à rétablir si la saisie est annulée
    let personConfirmed = false;
    let countriesReady = null;

    const personField = name => personFieldset.querySelector(`[name="person[${name}]"]`);

    const update = () => {
        const isNew = select.value === 'new';
        setVisible(memberHint, select.value !== 'self' && !isNew);
        setVisible(personSummary, isNew && personConfirmed);
        // Champs désactivés : ni validés ni envoyés avec la demande
        personFieldset.disabled = !isNew;
        if (isNew && personConfirmed) {
            $('[data-new-person-name]', personSummary).textContent =
                `${personField('first_name').value} ${personField('last_name').value}`.trim();
        }
    };

    const openDialog = async () => {
        countriesReady ??= initCountrySelects(personFieldset);
        personFieldset.disabled = false;
        await countriesReady;
        showModal(dialog);
    };

    select.addEventListener('change', () => {
        if (select.value === 'new') {
            personConfirmed = false;
            update();
            openDialog();
            return;
        }
        previousValue = select.value;
        personConfirmed = false;
        update();
    });

    delegate(form, 'click', '[data-action="edit-new-person"]', openDialog);
    dialog.addEventListener('shown.bs.modal', () => personField('first_name').focus());

    // Valider : les champs obligatoires doivent être remplis
    $('[data-action="confirm-new-person"]', dialog).addEventListener('click', () => {
        const invalidField = [...personFieldset.elements].find(field => !field.checkValidity());
        if (invalidField) {
            invalidField.reportValidity();
            return;
        }
        personConfirmed = true;
        update();
        hideModal(dialog);
    });

    // Fermeture sans validation : on revient au choix précédent
    dialog.addEventListener('hidden.bs.modal', () => {
        if (select.value === 'new' && !personConfirmed) {
            select.value = previousValue;
            update();
        }
    });

    // L'événement reset précède la remise à zéro des champs
    form.addEventListener('reset', () => setTimeout(() => {
        previousValue = select.value;
        personConfirmed = false;
        update();
    }));

    update();

    return beneficiary => {
        accountlessOptions.append(new Option(beneficiary.name, beneficiary.id));
        accountlessOptions.hidden = false;
    };
}

export function initSubmitRequest() {
    const form = $('#refund-form');
    if (!form) return;

    const message = $('#message-container-refund-form');
    const { amount, date, category, subcategory } = form.elements;
    const receiptsInput = form.elements['receipts[]'];
    const addBeneficiaryOption = initBeneficiaryPicker(form);

    // Montant toujours affiché avec 2 décimales
    amount.addEventListener('blur', () => {
        if (amount.value !== '') amount.value = Number.parseFloat(amount.value).toFixed(2);
    });

    receiptsInput.addEventListener('change', () => {
        const error = validateReceiptFiles(receiptsInput.files);
        if (error) {
            notify(error);
            receiptsInput.value = '';
        }
    });

    bindSubcategorySelect(category, subcategory, { refreshNow: true });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (date.value > todayIso()) {
            showAlert(message, 'danger', 'La date de la dépense ne peut pas être dans le futur.');
            return;
        }

        // Photos réduites et PDF convertis en images dans le navigateur avant l'envoi
        const formData = new FormData(form);
        const ready = await appendPreparedReceipts(formData, receiptsInput.files, {
            messageContainer: message,
            buttons: $$('button[type="submit"]', form),
        });
        if (!ready) return;

        const result = await submitForm(form, endpoint('repayment/submit-request'), {
            messageContainer: message,
            formData,
            successMessage: 'Demande de remboursement soumise avec succès !',
            errorMessage: 'Une erreur est survenue lors de la soumission de la demande de remboursement.',
        });

        if (!result.success) return;

        // Nouvelle personne sans compte : disponible directement pour la prochaine demande
        if (result.beneficiary) addBeneficiaryOption?.(result.beneficiary);

        // Après le reset du formulaire, la liste des sous-catégories doit suivre la catégorie vide
        if (category && subcategory) refreshSubcategories(category, subcategory);
    });
}

// --- Point d'entrée de la page « Nouvelle demande »
initSubmitRequest();
