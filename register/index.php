<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="d-flex align-items-center" style="min-height: calc(100vh - 200px);">
        <section class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">

                    <h2 class="text-center mb-4">Inscription</h2>
                    <div id="formMessage"></div>

                    <!-- Indicateur d'étapes -->
                    <div class="mb-4">
                        <div class="progress">
                            <div class="progress-bar" id="progressBar" role="progressbar" style="width: 25%;"
                                aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">Étape 1/3</div>
                        </div>
                    </div>

                    <form id="registerForm" class="container">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <fieldset data-step="1">
                            <legend class="mb-4">Informations personnelles</legend>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="first_name" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        placeholder="Votre prénom" required>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="last_name" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        placeholder="Votre nom" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="Votre adresse email" required>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        placeholder="Votre numéro de téléphone" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="password" class="form-label">Mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Votre mot de passe" required>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" placeholder="Confirmez votre mot de passe" required>
                                </div>

                            </div>
                        </fieldset>
                        <fieldset data-step="2">
                            <legend class="mb-4">Adresse</legend>
                            <div class="mb-3">
                                <label for="street" class="form-label">Rue et n°</label>
                                <input type="text" class="form-control" id="street" name="address"
                                    placeholder="Votre rue et numéro" required>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label for="postal_code" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code"
                                        placeholder="Votre code postal" required>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="city" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                        placeholder="Votre ville" required>
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="country" class="form-label">Pays</label>
                                    <select name="country" id="country" class="form-control country-select" required>
                                        <option value="">Sélectionnez votre pays</option>
                                    </select>
                                </div>

                            </div>

                        </fieldset>
                        <fieldset data-step="3">
                            <legend class="mb-4">Coordonnées bancaires</legend>
                            <div class="mb-3">
                                <label for="iban" class="form-label">IBAN</label>
                                <input type="text" class="form-control" id="iban" name="iban" placeholder="Votre IBAN"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="same-address" class="form-label">
                                    <input type="checkbox" class="form-check-input" id="same-address"
                                        name="same-address" checked>
                                    <span class="form-check-label">Le bénéficiaire des remboursements est la même
                                        personne que l'utilisateur</span>
                                </label>
                            </div>
                        </fieldset>

                        <fieldset data-step="3" id="beneficiaryFieldset" style="display: none;">
                            <legend class="mb-4">Informations sur le bénéficiaire</legend>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="beneficiary_name" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="beneficiary_name"
                                        name="refund_first_name" placeholder="Nom du bénéficiaire">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="beneficiary_surname" class="form-label">Prénom</label>
                                    <input type="text" class="form-control" id="beneficiary_surname"
                                        name="refund_last_name" placeholder="Prénom du bénéficiaire">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="beneficiary_street" class="form-label">Rue et n°</label>
                                <input type="text" class="form-control" id="beneficiary_street" name="refund_address"
                                    placeholder="Rue et numéro du bénéficiaire">
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-4">
                                    <label for="beneficiary_postalCode" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="beneficiary_postalCode"
                                        name="refund_postal_code" placeholder="Code postal du bénéficiaire">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="beneficiary_city" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="beneficiary_city" name="refund_city"
                                        placeholder="Ville du bénéficiaire">
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="beneficiary_country" class="form-label">Pays</label>
                                    <select name="refund_country" id="beneficiary_country" class="form-control country-select">
                                        <option value="">Sélectionnez votre pays</option>
                                    </select>
                                </div>

                            </div>
                        </fieldset>


                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" id="prevBtn"
                                style="display: none;">Précédent</button>
                            <button type="submit" class="btn btn-success" id="submitBtn"
                                style="display: none;">S'inscrire</button>
                            <button type="button" class="btn btn-primary ms-auto" id="nextBtn">Suivant</button>
                        </div>
                    </form>
                    <p class="text-center mt-3">
                        Déjà un compte ? <a href="/login">Connectez-vous</a>
                    </p>

                </div>
            </div>
        </section>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
    <script src="/scripts/functions.js"></script>


    <!-- Gestion de l'inscription en plusieurs étapes -->
    <script>
        let currentStep = 1;
        const totalSteps = 3;

        const form = document.getElementById('registerForm');
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        const submitBtn = document.getElementById('submitBtn');
        const progressBar = document.getElementById('progressBar');

        // Validation des champs par étape
        const validationRules = {
            1: ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password'],
            2: ['street', 'postal_code', 'city', 'country'],
            3: ['iban', 'beneficiary_name', 'beneficiary_surname', 'beneficiary_street', 'beneficiary_postalCode', 'beneficiary_city', 'beneficiary_country']
        };

        // Afficher/masquer les fieldsets
        function updateSteps() {
            document.querySelectorAll('fieldset').forEach(fieldset => {
                // Ignorer le fieldset du bénéficiaire car il est géré séparément
                if (fieldset.id === 'beneficiaryFieldset') return;

                const step = fieldset.getAttribute('data-step');
                fieldset.style.display = currentStep == step ? 'block' : 'none';
            });

            // Gestion du fieldset du bénéficiaire (seulement à l'étape 3 si checkbox décochée)
            const beneficiaryFieldset = document.getElementById('beneficiaryFieldset');
            const sameAddressCheckbox = document.getElementById('same-address');
            if (currentStep === 3 && !sameAddressCheckbox.checked) {
                beneficiaryFieldset.style.display = 'block';
            } else {
                beneficiaryFieldset.style.display = 'none';
            }

            // Mettre à jour la progress bar
            const progress = (currentStep / totalSteps) * 100;
            progressBar.style.width = progress + '%';
            progressBar.textContent = `Étape ${currentStep}/${totalSteps}`;

            // Afficher/masquer les boutons
            prevBtn.style.display = currentStep === 1 ? 'none' : 'block';
            nextBtn.style.display = currentStep === totalSteps ? 'none' : 'block';
            submitBtn.style.display = currentStep === totalSteps ? 'block' : 'none';
        }

        // Valider l'étape actuelle
        function validateStep() {
            const fieldsToValidate = validationRules[currentStep];
            let isValid = true;
            const errors = [];
            const isSameAddress = document.getElementById('same-address').checked;

            fieldsToValidate.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field) return;

                // Ignorer les champs du bénéficiaire si la checkbox est cochée
                if (isSameAddress && fieldId.startsWith('beneficiary_')) {
                    return;
                }

                if (field.type === 'checkbox') {
                    return;
                }

                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                    const label = form.querySelector(`label[for="${fieldId}"]`)?.textContent || fieldId;
                    errors.push(`${label} est vide`);
                } else {
                    field.classList.remove('is-invalid');
                }

                // Validation spéciale pour email
                if (fieldId === 'email' && field.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(field.value)) {
                        field.classList.add('is-invalid');
                        isValid = false;
                        errors.push('Email invalide');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                }

                // Validation pour les mots de passe
                if (fieldId === 'confirm_password' && field.value.trim()) {
                    const password = document.getElementById('password').value;
                    if (field.value !== password) {
                        field.classList.add('is-invalid');
                        document.getElementById('password').classList.add('is-invalid');
                        isValid = false;
                        errors.push('Les mots de passe ne sont pas identiques');
                    } else {
                        field.classList.remove('is-invalid');
                        document.getElementById('password').classList.remove('is-invalid');
                    }
                }

                // Validation pour le téléphone
                if (fieldId === 'phone' && field.value.trim()) {
                    const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
                    if (!phoneRegex.test(field.value.replace(/\s/g, ''))) {
                        field.classList.add('is-invalid');
                        isValid = false;
                        errors.push('Numéro de téléphone invalide');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                }

                // Validation pour le code postal
                if (fieldId === 'postal_code' && field.value.trim()) {
                    const postalRegex = /^\d{4,6}$/;
                    if (!postalRegex.test(field.value)) {
                        field.classList.add('is-invalid');
                        isValid = false;
                        errors.push('Code postal invalide');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                }

                if (fieldId === 'beneficiary_postalCode' && field.value.trim()) {
                    const postalRegex = /^\d{4,6}$/;
                    if (!postalRegex.test(field.value)) {
                        field.classList.add('is-invalid');
                        isValid = false;
                        errors.push('Code postal du bénéficiaire invalide');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                }
            });

            // Afficher les erreurs
            if (!isValid) {
                const errorMsg = document.getElementById('formMessage');
                const errorText = errors.length > 0 ? errors.join('<br>') : 'Veuillez remplir tous les champs correctement';
                errorMsg.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${errorText}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            } else {
                document.getElementById('formMessage').innerHTML = '';
            }

            return isValid;
        }

        // Gestion des clics des boutons
        nextBtn.addEventListener('click', () => {
            if (validateStep()) {
                currentStep++;
                updateSteps();
                window.scrollTo(0, 0);
            }
        });

        prevBtn.addEventListener('click', () => {
            currentStep--;
            updateSteps();
            document.getElementById('formMessage').innerHTML = '';
            window.scrollTo(0, 0);
        });

        // Gestion de la checkbox "same-address"
        const sameAddressCheckbox = document.getElementById('same-address');
        const beneficiaryFieldset = document.getElementById('beneficiaryFieldset');
        const beneficiaryFields = ['beneficiary_name', 'beneficiary_surname', 'beneficiary_street', 'beneficiary_postalCode', 'beneficiary_city', 'beneficiary_country'];

        function toggleBeneficiaryFields() {
            const isChecked = sameAddressCheckbox.checked;

            if (isChecked) {
                beneficiaryFieldset.style.display = 'none';
                beneficiaryFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.removeAttribute('required');
                        field.classList.remove('is-invalid');
                    }
                });
            } else {
                beneficiaryFieldset.style.display = 'block';
                beneficiaryFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.setAttribute('required', 'required');
                    }
                });
            }
        }

        sameAddressCheckbox.addEventListener('change', toggleBeneficiaryFields);

        // Initialiser l'état du fieldset au démarrage
        toggleBeneficiaryFields();

        // Afficher la première étape au démarrage
        updateSteps();

    </script>

    <!-- Gestion de la soumission du formulaire -->
    <script>

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (validateStep()) {
                await sendForm(form, 'submit-register.php', {
                    messageContainer: '#formMessage',
                    successMessage: 'Inscription réussie ! Si vous n\'êtes pas redirigé automatiquement, <a href="/dashboard">cliquez ici</a>.',
                    errorMessage: 'Une erreur est survenue lors de l\'inscription.',
                    loadingMessage: 'Inscription en cours...'
                });
            }
        });

        // Retire la classe is-invalid au fur et à mesure de la saisie
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('input', () => {
                if (field.value.trim()) {
                    field.classList.remove('is-invalid');
                }
            });
        });
    </script>

    <!-- Initialiser les listes de pays -->
    <script>
    
        const contrySelects = document.querySelectorAll('.country-select');
        contrySelects.forEach(select => {
            initCountrySelect(select, select.getAttribute('data-country') || '');
        });
    
    </script>
</body>

</html>