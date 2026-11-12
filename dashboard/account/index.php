<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/auth.php';

/*
    Mon compte (/dashboard/account) : informations personnelles, coordonnées bancaires,
    mot de passe et suppression du compte (scripts/pages/account.js).
*/
?>

<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Mon compte | Rembourso';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>
    <main class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="fs-3 mb-4">Mon compte</h1>
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab1-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab1" type="button">Informations personnelles</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2"
                                        type="button">Informations bancaires</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3"
                                        type="button">Mot de passe</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab4-tab" data-bs-toggle="tab" data-bs-target="#tab4"
                                        type="button">Supprimer le compte</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- =============================================
=            Formulaire d'édition des informations personnelles            =
============================================= -->
                                <div class="tab-pane fade show active" id="tab1">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h2 class="card-title mb-0">Informations personnelles</h2>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-edit"
                                                data-form="info-perso-form">Modifier</button>
                                            <button type="button" class="btn btn-secondary btn-cancel d-none"
                                                data-form="info-perso-form">Annuler</button>
                                        </div>
                                    </div>
                                    <div id="info-perso-message" class="mb-3"></div>
                                    <form class="row g-3" id="info-perso-form">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <div class="col-md-6">
                                            <label for="firstName" class="form-label">Prénom</label>
                                            <input type="text" class="form-control" name="firstName" id="firstName"
                                                value="<?= htmlspecialchars($user->getFirstName()) ?>" disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lastName" class="form-label">Nom</label>
                                            <input type="text" class="form-control" name="lastName" id="lastName"
                                                value="<?= htmlspecialchars($user->getLastName()) ?>" required disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" id="email"
                                                value="<?= htmlspecialchars($user->getEmail()) ?>" required disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="address" class="form-label">Adresse</label>
                                            <input type="text" class="form-control" name="address" id="address"
                                                value="<?= htmlspecialchars($user->getAddress()) ?>" required disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="postalCode" class="form-label">Code postal</label>
                                            <input type="text" class="form-control" name="postalCode" id="postalCode"
                                                value="<?= htmlspecialchars($user->getPostalCode()) ?>" required disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="city" class="form-label">Ville</label>
                                            <input type="text" class="form-control" name="city" id="city"
                                                value="<?= htmlspecialchars($user->getCity()) ?>" required disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="country" class="form-label">Pays</label>
                                            <select name="country" id="country" class="form-control country-select"
                                                data-country="<?= htmlspecialchars($user->getCountry() ?? '') ?>" disabled>
                                                <option value="">Sélectionnez votre pays</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary d-none">Enregistrer les modifications</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- =============================================
=            Formulaire d'édition des informations bancaires            =
============================================= -->
                                <div class="tab-pane fade" id="tab2">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h2 class="card-title mb-0">Informations bancaires</h2>
                                        <div>
                                            <button type="button" class="btn btn-primary btn-edit"
                                                data-form="info-bank-form">Modifier</button>
                                            <button type="button" class="btn btn-secondary btn-cancel d-none"
                                                data-form="info-bank-form">Annuler</button>
                                        </div>
                                    </div>
                                    <div id="info-bank-message" class="mb-3"></div>
                                    <form class="row g-3" id="info-bank-form">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <div class="col-12">
                                            <label for="iban" class="form-label">IBAN</label>
                                            <input type="text" name="iban" class="form-control" id="iban" disabled
                                                required
                                                value="<?= htmlspecialchars(iban_to_human_format($user->getIban())) ?>">
                                        </div>
                                        <div class="col-12">
                                            <input type="checkbox" name="same-address" id="same-address" disabled
                                                <?= $user->hasSameBeneficiary() ? 'checked' : '' ?>>
                                            <label for="same-address">Le bénéficiaire des remboursements est la même
                                                personne que l'utilisateur</label>
                                        </div>
                                        <div id="beneficiary-fields" class="row g-3<?= $user->hasSameBeneficiary() ? ' d-none' : '' ?>">
                                            <div class="col-6">
                                                <label for="refund_first_name" class="form-label">Prénom du
                                                    bénéficiaire</label>
                                                <input type="text" name="refund_first_name" class="form-control"
                                                    id="refund_first_name" disabled required
                                                    value="<?= htmlspecialchars($user->getRefundFirstName()) ?>">
                                            </div>

                                            <div class="col-6">
                                                <label for="refund_last_name" class="form-label">Nom du
                                                    bénéficiaire</label>
                                                <input type="text" name="refund_last_name" class="form-control"
                                                    id="refund_last_name" disabled required
                                                    value="<?= htmlspecialchars($user->getRefundLastName()) ?>">
                                            </div>
                                            <div class="col-12">
                                                <label for="refund_address" class="form-label">Adresse du
                                                    bénéficiaire</label>
                                                <input type="text" name="refund_address" class="form-control"
                                                    id="refund_address" disabled required
                                                    value="<?= htmlspecialchars($user->getRefundAddress()) ?>">
                                            </div>
                                            <div class="col-4">
                                                <label for="refund_postal_code" class="form-label">Code postal du
                                                    bénéficiaire</label>
                                                <input type="text" name="refund_postal_code" class="form-control"
                                                    id="refund_postal_code" disabled required
                                                    value="<?= htmlspecialchars($user->getRefundPostalCode()) ?>">
                                            </div>
                                            <div class="col-4">
                                                <label for="refund_city" class="form-label">Ville du
                                                    bénéficiaire</label>
                                                <input type="text" name="refund_city" class="form-control"
                                                    id="refund_city" disabled required
                                                    value="<?= htmlspecialchars($user->getRefundCity()) ?>">
                                            </div>
                                            <div class="col-4">
                                                <label for="refund_country" class="form-label">Pays</label>
                                                <select name="refund_country" id="refund_country"
                                                    class="form-control country-select"
                                                    data-country="<?= htmlspecialchars($user->getRefundCountry() ?? '') ?>"
                                                    disabled>
                                                    <option value="">Sélectionnez votre pays</option>
                                                </select>
                                            </div>


                                        </div>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary d-none">Enregistrer les
                                                informations bancaires</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- =============================================
=            Formulaire de modification du mot de passe            =
============================================= -->
                                <div class="tab-pane fade" id="tab3">
                                    <h2 class="card-title mb-4">Modifier le mot de passe</h2>
                                    <div id="password-message" class="mb-3"></div>
                                    <form class="row g-3" id="password-form">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <div class="col-12">
                                            <label for="currentPassword" class="form-label">Mot de passe actuel</label>
                                            <input type="password" class="form-control" name="currentPassword"
                                                id="currentPassword" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="newPassword" class="form-label">Nouveau mot de passe</label>
                                            <input type="password" class="form-control" name="newPassword"
                                                id="newPassword" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="confirmPassword" class="form-label">Confirmer le mot de
                                                passe</label>
                                            <input type="password" class="form-control" name="confirmPassword"
                                                id="confirmPassword" required>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Mettre
                                                à jour le mot de passe</button>
                                        </div>
                                    </form>
                                </div>
                                <!-- =============================================
=            Formulaire de suppression du compte            =
============================================= -->
                                <div class="tab-pane fade" id="tab4">
                                    <h2 class="card-title mb-4">Supprimer mon compte</h2>
                                    <div id="delete-message" class="mb-3"></div>
                                    <div class="alert alert-danger mb-4" role="alert">
                                        <strong>⚠️ Attention !</strong> Cette action est irréversible. Votre compte sera
                                        désactivé définitivement. Si vous avez rejoint des organisations, vos donnée
                                        seront conservées pour permettre à l'organisation de continuer à fonctionner,
                                        mais votre compte sera désactivé et vous ne pourrez plus vous connecter. Si vous
                                        êtes le seul membre d'une organisation, celle-ci sera également supprimée.
                                    </div>
                                    <form id="delete-account-form">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <div class="mb-3">
                                            <label for="confirmEmail" class="form-label">Tapez votre email pour
                                                confirmer la suppression</label>
                                            <input type="email" class="form-control" name="confirmEmail"
                                                id="confirmEmail" placeholder="votre@email.com" required>
                                        </div>
                                        <button type="submit" class="btn btn-danger">Supprimer définitivement mon
                                            compte</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php'; ?>
    <script type="module" src="<?= asset('/scripts/pages/account.js') ?>"></script>
</body>


</html>