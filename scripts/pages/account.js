/**
 * Page « Mon compte » : informations personnelles, bancaires, mot de passe, suppression du compte.
 */
import { submitForm } from '../components/ajax-form.js';
import { initCountrySelects } from '../components/country-select.js';
import { endpoint } from '../core/api.js';
import { $, $$, setVisible } from '../core/dom.js';

const editableFields = form => $$('input:not([type="hidden"]), select', form);

/**
 * Bascule un formulaire entre lecture seule et édition.
 * Les boutons « Modifier » / « Annuler » sont liés au formulaire via data-form="<id>".
 */
function setEditMode(form, isEditing) {
    editableFields(form).forEach(input => { input.disabled = !isEditing; });
    setVisible($('button[type="submit"]', form), isEditing);
    setVisible($(`.btn-edit[data-form="${form.id}"]`), !isEditing);
    setVisible($(`.btn-cancel[data-form="${form.id}"]`), isEditing);
}

function snapshotValues(form) {
    return editableFields(form).map(input => [input, input.type === 'checkbox' ? input.checked : input.value]);
}

function restoreValues(snapshot) {
    snapshot.forEach(([input, value]) => {
        if (input.type === 'checkbox') input.checked = value;
        else input.value = value;
    });
}

/** Formulaire « Modifier / Annuler / Enregistrer ». */
function initEditableForm(form, { url, messageContainer, successMessage, errorMessage, onRestore = () => {}, beforeSubmit = () => {} }) {
    let snapshot = [];

    $(`.btn-edit[data-form="${form.id}"]`).addEventListener('click', () => {
        snapshot = snapshotValues(form);
        setEditMode(form, true);
    });

    // « Annuler » restaure les valeurs d'avant l'édition
    $(`.btn-cancel[data-form="${form.id}"]`).addEventListener('click', () => {
        restoreValues(snapshot);
        onRestore();
        setEditMode(form, false);
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        beforeSubmit();

        const result = await submitForm(form, url, { messageContainer, successMessage, errorMessage, resetOnSuccess: false });
        if (result.success) setEditMode(form, false);
    });

    setEditMode(form, false);
}

// --- Informations personnelles
const personalForm = $('#info-perso-form');

initEditableForm(personalForm, {
    url: endpoint('account/update-profile'),
    messageContainer: '#info-perso-message',
    successMessage: 'Informations personnelles mises à jour avec succès.',
    errorMessage: 'Une erreur est survenue lors de la mise à jour des informations personnelles.',
});

// --- Informations bancaires
const bankForm = $('#info-bank-form');
const sameAddressCheckbox = $('#same-address');
const beneficiaryFields = $('#beneficiary-fields');

// Champ bénéficiaire -> champ personnel correspondant
const BENEFICIARY_SOURCE = {
    refund_first_name: 'firstName',
    refund_last_name: 'lastName',
    refund_address: 'address',
    refund_postal_code: 'postalCode',
    refund_city: 'city',
    refund_country: 'country',
};

/** Si le bénéficiaire est l'utilisateur lui-même, masque ses champs et y recopie les données personnelles. */
function syncBeneficiaryFields() {
    const sameAddress = sameAddressCheckbox.checked;
    setVisible(beneficiaryFields, !sameAddress);
    if (!sameAddress) return;

    Object.entries(BENEFICIARY_SOURCE).forEach(([target, source]) => {
        bankForm.elements[target].value = personalForm.elements[source].value;
    });
}

sameAddressCheckbox.addEventListener('change', syncBeneficiaryFields);

initEditableForm(bankForm, {
    url: endpoint('account/update-banking-info'),
    messageContainer: '#info-bank-message',
    successMessage: 'Informations bancaires mises à jour avec succès.',
    errorMessage: 'Une erreur est survenue lors de la mise à jour des informations bancaires.',
    onRestore: syncBeneficiaryFields,
    beforeSubmit: syncBeneficiaryFields,
});

// --- Mot de passe
const passwordForm = $('#password-form');
passwordForm.addEventListener('submit', event => {
    event.preventDefault();
    submitForm(passwordForm, endpoint('account/update-password'), {
        messageContainer: '#password-message',
        successMessage: 'Mot de passe mis à jour avec succès.',
        errorMessage: 'Une erreur est survenue lors de la mise à jour du mot de passe.',
    });
});

// --- Suppression du compte
const deleteForm = $('#delete-account-form');
deleteForm.addEventListener('submit', event => {
    event.preventDefault();
    submitForm(deleteForm, endpoint('account/delete-account'), {
        messageContainer: '#delete-message',
        successMessage: 'Votre compte a été supprimé avec succès.',
        errorMessage: 'Une erreur est survenue lors de la suppression du compte.',
    });
});

// Les selects de pays doivent avoir leurs options avant de recopier le pays du bénéficiaire
initCountrySelects().then(syncBeneficiaryFields);
