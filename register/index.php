<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

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
                            <div class="progress-bar" id="progressBar" role="progressbar" style="width: 33%;"
                                aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">Étape 1/3</div>
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
                                <div class="mb-3 col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="Votre adresse email" required>
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
                        <fieldset data-step="2" class="d-none">
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
                        <fieldset data-step="3" class="d-none">
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

                        <fieldset data-step="3" id="beneficiaryFieldset" class="d-none">
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
                            <button type="button" class="btn btn-secondary d-none" id="prevBtn">Précédent</button>
                            <button type="submit" class="btn btn-success d-none" id="submitBtn">S'inscrire</button>
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
    <script type="module" src="/scripts/pages/register.js"></script>
</body>

</html>