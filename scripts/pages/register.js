/**
 * Inscription en 3 étapes : informations personnelles, adresse, coordonnées bancaires.
 */
import { submitForm } from '../components/ajax-form.js';
import { initCountrySelects } from '../components/country-select.js';
import { api } from '../core/api.js';
import { $, $$, html, setVisible } from '../core/dom.js';
import { errorMessage } from '../core/http.js';
import { clearAlert, showAlert, withButtonLoading } from '../core/ui.js';

const TOTAL_STEPS = 3;

// Champs contrôlés à chaque étape (identifiants des inputs)
const STEP_FIELDS = {
    1: ['first_name', 'last_name', 'email', 'password', 'confirm_password'],
    2: ['street', 'postal_code', 'city', 'country'],
    3: ['iban', 'beneficiary_name', 'beneficiary_surname', 'beneficiary_street', 'beneficiary_postalCode', 'beneficiary_city', 'beneficiary_country'],
};
const BENEFICIARY_FIELDS = STEP_FIELDS[3].filter(id => id.startsWith('beneficiary_'));

// Contrôles de format complémentaires : [regex, message]
const FORMAT_RULES = {
    email: [/^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'Email invalide'],
    postal_code: [/^\d{4,6}$/, 'Code postal invalide'],
    beneficiary_postalCode: [/^\d{4,6}$/, 'Code postal du bénéficiaire invalide'],
};

const form = $('#registerForm');
const message = $('#formMessage');
const progressBar = $('#progressBar');
const prevButton = $('#prevBtn');
const nextButton = $('#nextBtn');
const submitButton = $('#submitBtn');
const sameAddressCheckbox = $('#same-address');
const beneficiaryFieldset = $('#beneficiaryFieldset');

let currentStep = 1;

const field = id => document.getElementById(id);
const fieldLabel = id => form.querySelector(`label[for="${id}"]`)?.textContent.trim() || id;

function setInvalid(input, invalid) {
    input.classList.toggle('is-invalid', invalid);
}

function updateSteps() {
    $$('fieldset[data-step]', form).forEach(fieldset => {
        if (fieldset === beneficiaryFieldset) return;
        setVisible(fieldset, Number(fieldset.dataset.step) === currentStep);
    });
    setVisible(beneficiaryFieldset, currentStep === 3 && !sameAddressCheckbox.checked);

    const progress = (currentStep / TOTAL_STEPS) * 100;
    progressBar.style.width = `${progress}%`;
    progressBar.setAttribute('aria-valuenow', String(progress));
    progressBar.textContent = `Étape ${currentStep}/${TOTAL_STEPS}`;

    setVisible(prevButton, currentStep > 1);
    setVisible(nextButton, currentStep < TOTAL_STEPS);
    setVisible(submitButton, currentStep === TOTAL_STEPS);
}

/** Valide les champs de l'étape courante et affiche les erreurs. */
function validateStep() {
    const errors = [];
    const skipBeneficiary = sameAddressCheckbox.checked;

    for (const id of STEP_FIELDS[currentStep]) {
        const input = field(id);
        if (!input || (skipBeneficiary && BENEFICIARY_FIELDS.includes(id))) continue;

        const value = input.value.trim();
        let error = null;

        if (!value) {
            error = `${fieldLabel(id)} est vide`;
        } else if (FORMAT_RULES[id]) {
            const [regex, formatError, normalize = v => v] = FORMAT_RULES[id];
            if (!regex.test(normalize(value))) error = formatError;
        }

        setInvalid(input, Boolean(error));
        if (error) errors.push(error);
    }

    if (currentStep === 1) {
        const password = field('password');
        const confirm = field('confirm_password');
        if (confirm.value && password.value !== confirm.value) {
            setInvalid(password, true);
            setInvalid(confirm, true);
            errors.push('Les mots de passe ne sont pas identiques');
        }
    }

    if (errors.length) {
        showAlert(message, 'danger', html`${errors.map((error, i) => html`${i ? html`<br>` : ''}${error}`)}`, { dismissible: true });
        return false;
    }

    clearAlert(message);
    return true;
}

function goToStep(step) {
    currentStep = step;
    updateSteps();
    window.scrollTo(0, 0);
}

function toggleBeneficiaryFields() {
    const sameAddress = sameAddressCheckbox.checked;
    BENEFICIARY_FIELDS.forEach(id => {
        const input = field(id);
        input.required = !sameAddress;
        if (sameAddress) setInvalid(input, false);
    });
    updateSteps();
}

/** Vérifie côté serveur que l'e-mail n'est pas déjà associé à un compte. */
async function checkEmailAvailable() {
    const input = field('email');
    const result = await api.checkEmail(input.value.trim());

    if (!result.success) {
        showAlert(message, 'danger', errorMessage(result, "Impossible de vérifier l'adresse e-mail."), { dismissible: true });
        return false;
    }
    if (!result.available) {
        setInvalid(input, true);
        showAlert(message, 'danger', html`Cet email est déjà utilisé. <a href="/login">Se connecter</a> ou <a href="/login/forgot-password.php">réinitialiser le mot de passe</a>.`, { dismissible: true });
        return false;
    }
    return true;
}

nextButton.addEventListener('click', async () => {
    if (!validateStep()) return;

    if (currentStep === 1) {
        const available = await withButtonLoading(nextButton, 'Vérification...', checkEmailAvailable);
        if (!available) return;
    }

    goToStep(currentStep + 1);
});

prevButton.addEventListener('click', () => {
    clearAlert(message);
    goToStep(currentStep - 1);
});

sameAddressCheckbox.addEventListener('change', toggleBeneficiaryFields);

// Retire l'état d'erreur dès que l'utilisateur corrige un champ
form.addEventListener('input', event => {
    if (event.target.value?.trim()) setInvalid(event.target, false);
});

form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!validateStep()) return;

    await submitForm(form, 'submit-register.php', {
        messageContainer: message,
        loadingMessage: 'Inscription en cours...',
        successMessage: html`Inscription réussie ! Si vous n'êtes pas redirigé automatiquement, <a href="/dashboard">cliquez ici</a>.`,
        errorMessage: "Une erreur est survenue lors de l'inscription.",
        resetOnSuccess: false,
    });
});

initCountrySelects();
toggleBeneficiaryFields();
