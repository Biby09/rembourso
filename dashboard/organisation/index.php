<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';

// Vérificiation du GET id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /dashboard');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=/dashboard/organisation/?id=' . ($_GET['id'] ?? ''));
    exit;
}
try {
    $user = new User($_SESSION['user_id']);
} catch (Exception $e) {
    // En cas d'erreur lors de la récupération de l'utilisateur, on détruit la session et on redirige vers la page de connexion
    session_destroy();
    header('Location: /login?redirect=/dashboard/organisation' . ($_GET['id'] ?? ''));
    exit;
}


// Vérification de l'existence de l'organisation
try {
    $organisation = new Organisation($_GET['id']);
} catch (Exception $e) {
    header('Location: /dashboard');
    exit;
}

// Vérification que l'utilisateur est membre de l'organisation
if (!$organisation->isMember($user->id)) {
    header('Location: /dashboard');
    exit;
}
?>


<!DOCTYPE html>
<html lang="fr">

<?php
$page_title = htmlspecialchars($organisation->name);
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-4"><?= htmlspecialchars($organisation->name) ?></h1>
                    <div class="card">
                        <div class="card-header">
                            <!-- Onglets desktop -->
                            <ul class="nav nav-tabs card-header-tabs d-none d-md-flex" role="tablist"
                                id="org-tabs-desktop">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab-submit-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-submit" type="button" role="tab">Déposer une
                                        demande</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab-history-tab" data-bs-toggle="tab"
                                        data-bs-target="#tab-history" type="button" role="tab">Historique de mes
                                        demandes</button>
                                </li>
                                <?php if ($organisation->isCashier($user->id)): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="tab-cashier-tab" data-bs-toggle="tab"
                                            data-bs-target="#tab-cashier" type="button" role="tab">Gestion des
                                            remboursements</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="tab-repayment-history-tab" data-bs-toggle="tab"
                                            data-bs-target="#tab-repayment-history" type="button" role="tab">Historique des
                                            remboursements</button>
                                    </li>
                                <?php endif; ?>

                                <?php if ($organisation->isAdmin($user->id)): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="tab-manage-tab" data-bs-toggle="tab"
                                            data-bs-target="#tab-manage" type="button" role="tab">Gestion de
                                            l'organisation</button>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <!-- Select mobile -->
                            <select class="form-select d-md-none" id="org-tabs-mobile"
                                aria-label="Navigation des onglets">
                                <option value="#tab-submit" selected>Déposer une demande</option>
                                <option value="#tab-history">Historique de mes demandes</option>
                                <?php if ($organisation->isCashier($user->id)): ?>
                                    <option value="#tab-cashier">Gestion des remboursements</option>
                                    <option value="#tab-repayment-history">Historique des remboursements</option>
                                <?php endif; ?>
                                <?php if ($organisation->isAdmin($user->id)): ?>
                                    <option value="#tab-manage">Gestion de l'organisation</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Onglet 1: Déposer une demande -->
                                <div class="tab-pane fade show active" id="tab-submit" role="tabpanel">
                                    <div class="card-body text-center">
                                        <h2 class="card-title">Déposer une demande remboursement</h2>
                                    </div>

                                    <p class="text-muted mb-4">Veuillez remplir le formulaire ci-dessous pour soumettre
                                        une
                                        demande de remboursement.</p>
                                    <div id="message-container-refund-form"></div>
                                    <form id="refund-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <div class="row">
                                            <div class="mb-3 col-md-9">
                                                <label for="label" class="form-label">Libellé</label>
                                                <input type="text" id="label" name="label" class="form-control" required
                                                    maxlength="100">
                                            </div>
                                            <div class="mb-3 col-md-3">
                                                <label for="amount" class="form-label">Montant
                                                    [<?= htmlspecialchars($organisation->currency) ?>]</label>
                                                <input type="number" id="amount" name="amount" class="form-control"
                                                    step="0.01" max="99999999.99" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <?php

                                            if ($organisation->use_cat && count($organisation->getCategories()) > 0): ?>
                                                <div class="mb-3 col-md-3">
                                                    <label for="category" class="form-label">Catégorie</label>
                                                    <select id="category" name="category" class="form-control" required>
                                                        <option value="" selected disabled hidden>Choisir une catégorie
                                                        </option>
                                                        <?php foreach ($organisation->getCategories() as $cat): ?>
                                                            <option value="<?= htmlspecialchars($cat->id) ?>">
                                                                <?= htmlspecialchars($cat->name) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <?php if ($organisation->use_subcat && count($organisation->getSubcategories()) > 0): ?>
                                                    <div class="mb-3 col-md-3">
                                                        <label for="subcategory" class="form-label">Sous-catégorie</label>
                                                        <select id="subcategory" name="subcategory" class="form-control">
                                                            <option value="" selected disabled hidden>Choisir une sous-catégorie
                                                            </option>
                                                            <!-- Les options seront chargées dynamiquement via JavaScript en fonction de la catégorie sélectionnée -->
                                                        </select>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div class="mb-3 col-md-6">
                                                <label for="date" class="form-label">Date de la dépense</label>
                                                <input type="date" id="date" name="date" class="form-control"
                                                    max="<?= date('Y-m-d') ?>" required>
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label for="receipts" class="form-label">Justificatif</label>
                                                <input type="file" id="receipts" name="receipts[]" class="form-control"
                                                    accept="image/*,.pdf" multiple required>
                                            </div>
                                            <input type="hidden" name="organisation-id"
                                                value="<?= $organisation->id ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Soumettre la demande</button>
                                    </form>
                                </div>

                                <!-- Onglet 2: Historique -->
                                <div class="tab-pane fade" id="tab-history" role="tabpanel">
                                    <div class="card-body text-center">
                                        <h2 class="card-title">Mes demandes de remboursement</h2>
                                        <?php
                                        $userRepayments = $organisation->getUserRepaymentRequests();
                                        if (!empty($userRepayments)) { ?>
                                            <p class="card-text">Vous avez les demandes de remboursement suivantes :</p>
                                            <div class="accordion" id="accordion-repayment">
                                                <!-- Les éléments de l'accordéon seront générés dynamiquement via JavaScript -->
                                            </div>
                                            <?php
                                            $count = $organisation->countUserRepaymentRequests();
                                            $page = intdiv($count, 10);
                                            if ($count % 10 != 0) {
                                                $page++;
                                            }
                                            if ($count > 10): ?>

                                                <div class="pagination d-flex justify-content-center gap-2 mt-4">
                                                    <button class="btn btn-outline-primary btn-sm pagination-btn"
                                                        data-page="back"><i class="bi bi-chevron-double-left"></i></button>

                                                    <?php for ($i = 1; $i <= $page; $i++): ?>
                                                        <button class="btn btn-outline-primary btn-sm pagination-btn"
                                                            data-page="<?= $i ?>"><?= $i ?></button>
                                                    <?php endfor; ?>

                                                    <button class="btn btn-outline-primary btn-sm pagination-btn"
                                                        data-page="next"><i class="bi bi-chevron-double-right"></i>
                                                    </button>

                                                </div>
                                            <?php endif; ?>

                                            <?php
                                        } else {
                                            echo '<p class="card-text">Vous n\'avez aucune demande de remboursement en cours.</p>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <!-- Onglet 3: Interface de remboursement (visible seulement pour les caissiers et les admins) -->
                                <?php if ($organisation->isCashier($user->id)): ?>
                                    <div class="tab-pane fade" id="tab-cashier" role="tabpanel">
                                        <div class="card-body text-center">
                                            <h2 class="card-title">Gestion des remboursements</h2>
                                            <div id="cashier-table-container">
                                                <!-- Le tableau de gestion des remboursements sera généré dynamiquement via JavaScript -->

                                            </div>
                                        </div>
                                    </div>

                                    <!-- Onglet 4: Historique des remboursements (visible seulement pour les admins et les caissiers) -->
                                    <div class="tab-pane fade" id="tab-repayment-history" role="tabpanel">
                                        <div class="card-body text-center">
                                            <h2 class="card-title">Historique des remboursements</h2>
                                            <div id="repayment-history-table-container">
                                                <?php
                                                $repaymentBatches = $organisation->getRepaymentBatches();
                                                if (!empty($repaymentBatches)): ?>
                                                    <table class="table table-striped" id="repayment-history-table">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Utilisateur</th>
                                                                <th scope="col">Caissier</th>
                                                                <th scope="col">Date</th>
                                                                <th scope="col">Montant total</th>
                                                                <th scope="col">Confirmation</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Les lignes du tableau seront générées dynamiquement via JavaScript -->
                                                        </tbody>
                                                    </table>
                                                <?php else: ?>
                                                    <p class="text-muted">Aucun remboursement dans l'historique pour le moment.
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <!-- Onglet 5: Gestion de l'organisation (visible seulement pour les admins) -->
                                <?php if ($organisation->isAdmin($user->id)): ?>
                                    <div class="tab-pane fade" id="tab-manage" role="tabpanel">
                                        <div class="card-body text-center">
                                            <h2 class="card-title">Gestion de l'organisation</h2>
                                            <section class="mb-2">
                                                <h3>Gérer les membres</h3>
                                                <div id="manage-members-container">
                                                    <?php
                                                    $members = $organisation->getMembers();
                                                    if (!empty($members)): ?>
                                                        <table class="table table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 30%">Nom</th>
                                                                    <th style="width: 40%">Rôle</th>
                                                                    <th style="width: 30%">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($members as $member): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($member->first_name) . ' ' . htmlspecialchars($member->last_name) ?>
                                                                        </td>
                                                                        <td><?= htmlspecialchars($organisation->getUserRole($member->id, true)) ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($member->id !== $user->id): ?>
                                                                                <button
                                                                                    class="btn btn-sm btn-outline-secondary change-role-btn"
                                                                                    data-user-id="<?= $member->id ?>">Modifier</button>
                                                                            <?php else: ?>
                                                                                <span class="text-muted">C'est vous</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>

                                                    <?php else: ?>
                                                        <p class="text-muted">Aucun membre dans cette organisation.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex justify-content-center mt-4 mb-5">
                                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#addMemberDialog">
                                                        Inviter un utilisateur
                                                    </button>
                                                </div>

                                                <div>
                                                    <h4 class="mb-3">Invitations en cours</h4>
                                                    <div id="invitations-container">
                                                        <!-- Les invitations sont chargés dynamiquement via javascript-->
                                                    </div>
                                                </div>
                                            </section>
                                            <hr class="mb-5">
                                            <section>
                                                <h3 class="mb-3">Gestion des catégories et des sous catégories</h3>
                                                <div class="mb-3">
                                                    <div class="d-flex flex-row gap-3 justify-content-center">
                                                        <div class="form-check"
                                                            title="Si vous désactivez les catégories, elles ne seront plus utilisées dans les demandes de remboursement. Cependant, elles ne seront pas supprimées et pourront être réactivées ultérieurement.">
                                                            <input type="checkbox" name="use-cat" id="use-cat"
                                                                <?= $organisation->use_cat ? 'checked' : '' ?>>
                                                            <label for="use-cat">Activer les catégories</label>
                                                        </div>
                                                        <div class="form-check"
                                                            title="Si vous désactivez les sous-catégories, elles ne seront plus utilisées dans les demandes de remboursement. Cependant, elles ne seront pas supprimées et pourront être réactivées ultérieurement."
                                                            <?php if (!$organisation->use_cat)
                                                                echo 'style="display: none;"'; ?>>
                                                            <input type="checkbox" name="use-subcat" id="use-subcat"
                                                                <?= $organisation->use_subcat ? 'checked' : '' ?>>
                                                            <label for="use-subcat">Activer les sous-catégories</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="categories-management-container" class="mt-4">
                                                    <!-- La gestion des catégories et des sous-catégories est chargée dynamiquement via javascript-->
                                                </div>
                                                <button id="add-category-button" class="btn btn-primary">Ajouter une
                                                    catégorie</button>
                                            </section>
                                            <hr class="mb-5">

                                            <section>
                                                <h3 class="mb-3">Paramètres de l'organisation</h3>

                                                <!-- Renommage -->
                                                <div class="card border-0 shadow-sm mb-4">
                                                    <div class="card-body">
                                                        <h4 class="card-title">Renommer l'organisation</h4>
                                                        <p class="text-muted mb-3">Ce nom est visible par tous les membres.
                                                        </p>
                                                        <div id="rename-org-message"></div>
                                                        <div class="row g-2 align-items-end">
                                                            <div class="col-12 col-md">
                                                                <label for="organisation-name" class="form-label">Nom de
                                                                    l'organisation</label>
                                                                <input type="text" class="form-control"
                                                                    id="organisation-name"
                                                                    value="<?= htmlspecialchars($organisation->name) ?>"
                                                                    maxlength="100" required>
                                                            </div>
                                                            <div class="col-12 col-md-auto">
                                                                <button id="rename-organisation-button"
                                                                    class="btn btn-primary w-100">Renommer</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Zone de danger -->
                                                <div class="card border-danger">
                                                    <div class="card-body">
                                                        <h4 class="card-title text-danger">Supprimer l'organisation</h4>
                                                        <p class="text-muted mb-3">
                                                            La suppression est <strong>définitive et irréversible</strong>.
                                                            Toutes les demandes, catégories, reçus et l'historique seront
                                                            supprimés.
                                                        </p>
                                                        <button id="delete-organisation-button"
                                                            class="btn btn-outline-danger" data-bs-toggle="modal"
                                                            data-bs-target="#deleteOrganisationDialog">
                                                            <i class="bi bi-exclamation-triangle"></i> Supprimer
                                                            l'organisation
                                                        </button>
                                                    </div>
                                                </div>
                                            </section>
                                        </div>
                                    </div>

                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="repaymentEditDialog"></div>

        <?php
        if ($organisation->isCashier($_SESSION['user_id'])): ?>
            <!-- Juste le conteneur .modal, sans contenu fixe -->
            <div class="modal fade" id="cashier-tab-dialog-container" tabindex="-1" aria-hidden="true">
                <!-- .modal-dialog sera injecté dynamiquement -->
            </div>

        <?php endif; ?>
        <?php
        if ($organisation->isAdmin($_SESSION['user_id'])): ?>
            <div class="modal fade" id="manageMemberDialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">

                        <div class="modal-header bg-light border-0 pb-0">
                            <div class="w-100">
                                <h5 class="modal-title fw-600 mb-2">Gestion des membres</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body px-4 py-4">
                            <div class="mb-4">
                                <div class="d-flex align-items-center gap-2 pb-3">
                                    <div>
                                        <p class="mb-0"><strong id="member-name" class="fs-5"></strong></p>
                                        <small class="text-muted" id="member-current-role"></small>
                                    </div>
                                </div>
                                <label for="member-role" class="form-label fw-500 mb-2">Modifier le rôle</label>
                                <select id="member-role" class="form-select form-select-lg border-1">
                                    <option value="member">Membre</option>
                                    <option value="cashier">Caissier</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                                <small class="text-muted mt-2 d-block">Le rôle définit les permissions de cet utilisateur
                                    dans l'organisation.</small>
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-top px-4 py-3">
                            <button type="button" id="delete-member-btn" class="btn btn-outline-danger btn-sm me-auto">
                                <i class="bi bi-trash"></i> Retirer de l'organisation
                            </button>
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary">Sauvegarder</button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="modal fade" id="addMemberDialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">

                        <div class="modal-header bg-light border-0 pb-0">
                            <div class="w-100">
                                <h5 class="modal-title fw-600 mb-2">Inviter un utilisateur</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body px-4 py-4">
                            <form id="add-member-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <div class="mb-3">
                                    <label for="user-email" class="form-label">Adresse e-mail de l'utilisateur</label>
                                    <input type="email" class="form-control" id="user-email"
                                        placeholder="Entrez l'adresse e-mail de l'utilisateur">
                                </div>
                                <div class="mb-3">
                                    <label for="user-role" class="form-label">Rôle</label>
                                    <select id="user-role" class="form-select border-1">
                                        <option value="member">Membre</option>
                                        <option value="cashier">Caissier</option>
                                        <option value="admin">Administrateur</option>
                                    </select>
                                    <small class="text-muted mt-2 d-block">Le rôle définit les permissions de cet
                                        utilisateur dans l'organisation.</small>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer bg-light border-top px-4 py-3">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary">Ajouter le membre</button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="modal fade modal-dialog-scrollable" id="editCategoryDialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">

                        <div class="modal-header bg-light border-0 pb-0">
                            <div class="w-100">
                                <h5 class="modal-title fw-600 mb-2">Modifier la catégorie</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body px-4 py-4">
                            <form id="edit-category-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <div class="mb-3">
                                    <fieldset>
                                        <legend class="col-form-label">Nom de la catégorie</legend>
                                        <input type="text" class="form-control" id="category-name"
                                            placeholder="Entrez le nom de la catégorie" required>
                                    </fieldset>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer bg-light border-top px-4 py-3">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteOrganisationDialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">

                        <div class="modal-header bg-danger text-white border-0">
                            <h5 class="modal-title fw-600"><i class="bi bi-exclamation-triangle"></i> Supprimer
                                l'organisation</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body px-4 py-4">
                            <div class="alert alert-danger" role="alert">
                                Cette action est <strong>irréversible</strong>. Toutes les données de
                                <strong><?= htmlspecialchars($organisation->name) ?></strong> seront définitivement
                                supprimées :
                                demandes de remboursement, reçus, catégories, membres et historique.
                            </div>
                            <div id="delete-org-message"></div>
                            <label for="confirm-organisation-name" class="form-label">
                                Pour confirmer, tapez le nom de l'organisation :
                                <strong><?= htmlspecialchars($organisation->name) ?></strong>
                            </label>
                            <input type="text" class="form-control" id="confirm-organisation-name"
                                placeholder="<?= htmlspecialchars($organisation->name) ?>" autocomplete="off">
                        </div>

                        <div class="modal-footer bg-light border-top px-4 py-3">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-danger" id="confirm-delete-organisation-btn" disabled>
                                Supprimer définitivement
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addCategoryDialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">

                        <div class="modal-header bg-light border-0 pb-0">
                            <div class="w-100">
                                <h5 class="modal-title fw-600 mb-2">Ajouter une catégorie</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body px-4 py-4">
                            <form id="add-category-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <div class="mb-3">
                                    <fieldset>
                                        <legend class="col-form-label">Nom de la catégorie</legend>
                                        <input type="text" class="form-control" id="new-category-name"
                                            placeholder="Entrez le nom de la catégorie" required>
                                    </fieldset>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer bg-light border-top px-4 py-3">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary">Ajouter</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
    <script src="/scripts/functions.js"></script>
    <script src="/scripts/update-subcategory.js"></script>
    <script>
        // Déclaré globalement pour être accessible par tous les scripts de la page
        // (évite la ReferenceError TDZ: utilisé avant sa déclaration const plus bas).
        const catCheckbox = document.getElementById('use-cat');
        const subcatCheckbox = document.getElementById('use-subcat');
    </script>
    <script src="/scripts/organisation-functions.js"></script>

    <!-- Script de gestion du formulaire de demande de remboursement -->
    <script>
        //Gestion du champ montant
        document.getElementById('amount').addEventListener('blur', function () {
            if (this.value !== '') {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
        //Gestion de l'import du fichier
        const input = document.querySelector('input[type="file"]');
        const acceptedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'application/pdf'];

        input.addEventListener('change', () => {
            const files = Array.from(input.files);

            if (files.some(file => !acceptedTypes.includes(file.type))) {
                alert('Type de fichier non autorisé. Acceptés: images (PNG, JPEG, WebP, GIF) et PDF');
                input.value = '';
            }
        });
        // Gestion de la soumission du formlaire de demande de remboursement
        const form = document.getElementById('refund-form');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const dateInput = document.getElementById('date');
            if (dateInput.value > new Date().toISOString().split('T')[0]) {
                document.querySelector('#message-container-refund-form').innerHTML =
                    '<div class="alert alert-danger">La date de la dépense ne peut pas être dans le futur.</div>';
                return;
            }

            await sendForm(form, '/api/submit-repayment-request.php', {
                messageContainer: '#message-container-refund-form',
                successMessage: 'Demande de remboursement soumise avec succès !',
                errorMessage: 'Une erreur est survenue lors de la soumission de la demande de remboursement.'
            });
        });
    </script>

    <!-- Script de gestion des différentes pages dans l'historique des demandes de remboursement -->
    <script>
        // Gestion de la pagination
        const paginationButtons = document.querySelectorAll('.pagination-btn');
        const pageCount = paginationButtons.length - 2; // Exclure les boutons de navigation < et >
        let currentPage = 1;


        async function updatePagination(page) {

            // Générer l'accordéon et remplacer l'ancien
            const newAccordion = await generateAccordion(page, <?= $organisation->id ?>);
            const oldAccordion = document.getElementById('accordion-repayment');
            if (oldAccordion) {
                oldAccordion.replaceWith(newAccordion);
            } else {
                document.querySelector('#tab-history .card-body').appendChild(newAccordion);
            }

            // Mettre à jour la page actuelle
            currentPage = page;

            for (let btn of paginationButtons) {
                btn.classList.remove('active');
                if (parseInt(btn.getAttribute('data-page')) === currentPage) {
                    btn.classList.add('active');
                }
            }

            if (currentPage === 1) {
                paginationButtons[0].classList.add('disabled');
            } else {
                paginationButtons[0].classList.remove('disabled');
            }
            if (currentPage === pageCount) {
                paginationButtons[paginationButtons.length - 1].classList.add('disabled');
            } else {
                paginationButtons[paginationButtons.length - 1].classList.remove('disabled');
            }

            if (pageCount > 5) {
                // Calculer la plage de 5 pages à afficher autour de la page actuelle
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(pageCount, startPage + 4);

                // Ajuster si on est proche de la fin pour toujours afficher 5 boutons
                if (endPage - startPage < 4) {
                    startPage = Math.max(1, endPage - 4);
                }

                for (let btn of paginationButtons) {
                    const dataPage = btn.getAttribute('data-page');
                    const btnPage = parseInt(dataPage);

                    // Garder toujours visibles les boutons back et next
                    if (dataPage === 'back' || dataPage === 'next') {
                        btn.classList.remove('d-none');
                    } else if (btnPage >= startPage && btnPage <= endPage) {
                        btn.classList.remove('d-none');
                    } else {
                        btn.classList.add('d-none');
                    }
                }
            }


            //Initier les boutons de modification pour les nouveaux éléments de l'accordéon
        };

        // Générer l'accordéon pour la page 1 à l'ouverture de l'onglet
        // 'show.bs.tab' est déclenché par le clic desktop ET par le select mobile.
        document.getElementById('tab-history-tab').addEventListener('show.bs.tab', async () => {
            await updatePagination(1);
            paginationButtons.forEach(button => {
                button.addEventListener('click', async () => {

                    let page = currentPage;

                    switch (button.getAttribute('data-page')) {
                        case 'back':
                            if (currentPage > 1) {
                                page = 1;
                            }
                            break;

                        case 'next':
                            if (currentPage < pageCount) {
                                page = pageCount;
                            }
                            break;

                        default:
                            page = parseInt(button.getAttribute('data-page'));
                    }
                    await updatePagination(page);
                })
            });
        });
    </script>

    <!-- 
///////////////////////////////////////
ONLGLET GESTION DES REMBOURSEMENTS
////////////////////////////// -->

    <!--Script de chargement du contenu de l'accordéon primaire -->

    <script>
        document.getElementById('tab-cashier-tab').addEventListener('show.bs.tab', async () => {
            await reloadCashierTable(<?= $organisation->id ?>);
        });
    </script>

    <!--
///////////////////////////////////////
ONLGLET GESTION DE L'ORGANISATION
////////////////////////////// -->

    <script>
        //Gestion de l'ouverture du modal de gestion des membres
        // Les boutons .change-role-btn sont dans le DOM (contenu statique de l'onglet),
        // on attache donc leurs listeners une seule fois, indépendamment de l'affichage de l'onglet.
        {
            const manageMemberButtons = document.querySelectorAll('.change-role-btn');
            manageMemberButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const userId = button.getAttribute('data-user-id');
                    const memberNameElement = document.getElementById('member-name');
                    const memberRoleSelectElement = document.getElementById('member-role');
                    const memberCurrentRoleElement = button.closest('tr').querySelector('td:nth-child(2)');

                    const memberCurrentRole = memberCurrentRoleElement.textContent.trim().toLowerCase();

                    if (memberCurrentRole === 'membre') {
                        memberRoleSelectElement.value = 'member';
                    } else if (memberCurrentRole === 'caissier') {
                        memberRoleSelectElement.value = 'cashier';
                    } else if (memberCurrentRole === 'administrateur') {
                        memberRoleSelectElement.value = 'admin';
                    }

                    memberNameElement.textContent = button.closest('tr').querySelector('td').textContent;

                    // Cloner le bouton supprimer pour éviter les écouteurs dupliqués
                    const deleteButton = document.getElementById('delete-member-btn');
                    const newDeleteButton = deleteButton.cloneNode(true);
                    deleteButton.parentNode.replaceChild(newDeleteButton, deleteButton);

                    newDeleteButton.addEventListener('click', async () => {
                        if (confirm('Êtes-vous sûr de vouloir retirer ce membre de l\'organisation ?')) {
                            try {
                                const response = await fetch('/api/edit-member.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        member_id: userId,
                                        organisation_id: <?= $organisation->id ?>,
                                        action: 'delete'
                                    })
                                });
                                const result = await response.json();
                                if (result.success) {
                                    manageMemberModalInstance.hide();
                                    // Retirer le membre de la table sans recharger la page
                                    button.closest('tr').remove();
                                } else {
                                    alert(result.error || 'Une erreur est survenue lors de la suppression du membre.');
                                }
                            } catch (error) {
                                console.error('Error:', error);
                                alert('Une erreur est survenue lors de la suppression du membre.');
                            }
                        }
                    });
                    const saveButton = manageMemberDialog.querySelector('.modal-footer .btn-primary');
                    const newSaveButton = saveButton.cloneNode(true);
                    saveButton.parentNode.replaceChild(newSaveButton, saveButton);

                    newSaveButton.addEventListener('click', async () => {
                        const newRole = document.getElementById('member-role').value;
                        try {
                            const response = await fetch('/api/edit-member.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    member_id: userId,
                                    organisation_id: <?= $organisation->id ?>,
                                    action: 'update',
                                    new_role: newRole
                                })
                            });
                            const result = await response.json();
                            if (result.success) {
                                manageMemberModalInstance.hide();
                                // Mettre à jour le rôle du membre dans la table sans recharger la page

                                if (newRole === 'member') {
                                    memberCurrentRoleElement.textContent = 'Membre';
                                } else if (newRole === 'cashier') {
                                    memberCurrentRoleElement.textContent = 'Caissier';
                                } else if (newRole === 'admin') {
                                    memberCurrentRoleElement.textContent = 'Administrateur';
                                }

                            } else {
                                alert(result.error || 'Une erreur est survenue lors de la mise à jour du rôle du membre.');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Une erreur est survenue lors de la mise à jour du rôle du membre.');
                        }
                    });


                    manageMemberModalInstance.show();
                });
            });
        }

        //Créer les instances des modals
        const showOrgInlineMessage = (container, type, text, timeout = 4000) => {
            if (!container) return;
            container.innerHTML = `<div class="alert alert-${type} py-2 mb-2">${text}</div>`;
            if (timeout > 0) {
                setTimeout(() => {
                    container.innerHTML = '';
                }, timeout);
            }
        };

        const setOrgButtonLoadingState = (button, isLoading, loadingText = 'Chargement...') => {
            if (!button) return;

            if (isLoading) {
                button.dataset.originalText = button.dataset.originalText || button.textContent.trim();
                button.disabled = true;
                button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`;
                return;
            }

            button.disabled = false;
            const originalText = button.dataset.originalText || button.textContent.trim();
            button.textContent = originalText;
            delete button.dataset.originalText;
        };

        const manageMemberDialog = document.getElementById('manageMemberDialog');
        const addMemberDialog = document.getElementById('addMemberDialog');
        const editCategoryDialog = document.getElementById('editCategoryDialog');
        const manageMemberModalInstance = getBootstrapModal(manageMemberDialog);
        const addMemberModalInstance = getBootstrapModal(addMemberDialog);
        const editCategoryModalInstance = getBootstrapModal(editCategoryDialog);

        document.getElementById('addMemberDialog').addEventListener('show.bs.modal', () => {
            const addMemberForm = document.getElementById('add-member-form');
            addMemberForm.reset();

            const saveButton = addMemberDialog.querySelector('.modal-footer .btn-primary');
            // Créer une fonction unique pour éviter les écouteurs dupliqués
            const handleSave = async () => {
                const email = document.getElementById('user-email').value.trim();
                const role = document.getElementById('user-role').value;

                if (!email) {
                    alert('Veuillez entrer une adresse e-mail valide.');
                    return;
                }

                try {
                    const response = await fetch('/api/add-member.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            email: email,
                            organisation_id: <?= $organisation->id ?>,
                            role: role
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Invitation envoyée avec succès !');
                        addMemberModalInstance.hide();
                        updateInvitationsList();
                    } else {
                        alert(result.error || 'Une erreur est survenue lors de l\'envoi de l\'invitation.');
                        addMemberModalInstance.hide();

                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Une erreur est survenue lors de l\'envoi de l\'invitation.');
                }
            };

            // Retirer les anciens écouteurs en clonant l'élément
            const newSaveButton = saveButton.cloneNode(true);
            saveButton.parentNode.replaceChild(newSaveButton, saveButton);
            newSaveButton.addEventListener('click', handleSave);
        });

        // Charger les invitations en cours à l'ouverture de l'onglet de gestion de l'organisation
        const manageTab = document.getElementById('tab-manage-tab');
        manageTab.addEventListener('show.bs.tab', updateInvitationsList);

        async function updateInvitationsList() {
            const invitationsContainer = document.getElementById('invitations-container');
            const inviationTable = await generateMemberInviationsTable(<?= $organisation->id ?>);

            if (invitationsContainer.innerHTML.trim() === '') {
                invitationsContainer.appendChild(inviationTable);
            } else {
                invitationsContainer.replaceChild(inviationTable, invitationsContainer.firstChild);
            }
        };

        // ===== Renommage et suppression de l'organisation =====
        (function () {
            const orgId = <?= $organisation->id ?>;
            const currentName = <?= json_encode($organisation->name) ?>;

            // --- Renommage ---
            const renameButton = document.getElementById('rename-organisation-button');
            const nameInput = document.getElementById('organisation-name');
            const renameMessage = document.getElementById('rename-org-message');

            const showRenameMessage = (type, text) => {
                showOrgInlineMessage(renameMessage, type, text, 4000);
            };

            renameButton.addEventListener('click', async () => {
                const newName = nameInput.value.trim();
                if (!newName) {
                    showRenameMessage('danger', 'Le nom ne peut pas être vide.');
                    return;
                }
                if (newName === currentName) {
                    showRenameMessage('info', 'Le nom est identique à l\'actuel.');
                    return;
                }

                setOrgButtonLoadingState(renameButton, true, 'Renommage...');
                try {
                    const result = await requestJson('/api/update-organisation.php', {
                        organisation_id: orgId,
                        action: 'rename',
                        name: newName
                    });

                    if (result.success) {
                        document.querySelector('h1').textContent = result.name;
                        document.title = result.name;
                        nameInput.value = result.name;
                        showRenameMessage('success', 'Organisation renommée avec succès.');
                    } else {
                        showRenameMessage('danger', result.error || 'Erreur lors du renommage.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showRenameMessage('danger', 'Erreur lors du renommage.');
                } finally {
                    setOrgButtonLoadingState(renameButton, false);
                }
            });

            // --- Suppression ---
            const deleteDialog = document.getElementById('deleteOrganisationDialog');
            if (!deleteDialog) return;

            const confirmInput = deleteDialog.querySelector('#confirm-organisation-name');
            const confirmButton = deleteDialog.querySelector('#confirm-delete-organisation-btn');
            const deleteMessage = deleteDialog.querySelector('#delete-org-message');
            const deleteModalInstance = new bootstrap.Modal(deleteDialog);

            // Réinitialiser à l'ouverture
            deleteDialog.addEventListener('show.bs.modal', () => {
                confirmInput.value = '';
                confirmButton.disabled = true;
                deleteMessage.innerHTML = '';
            });

            // Activer le bouton seulement si le nom correspond exactement
            confirmInput.addEventListener('input', () => {
                confirmButton.disabled = confirmInput.value.trim() !== currentName;
            });

            confirmButton.addEventListener('click', async () => {
                setOrgButtonLoadingState(confirmButton, true, 'Suppression...');
                try {
                    const result = await requestJson('/api/update-organisation.php', {
                        organisation_id: orgId,
                        action: 'delete'
                    });

                    if (result.success) {
                        window.location.href = '/dashboard';
                    } else {
                        showOrgInlineMessage(deleteMessage, 'danger', result.error || 'Erreur lors de la suppression.');
                        setOrgButtonLoadingState(confirmButton, false);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showOrgInlineMessage(deleteMessage, 'danger', 'Erreur lors de la suppression.');
                    setOrgButtonLoadingState(confirmButton, false);
                }
            });
        })();
    </script>


    <!-- Script pour l'ajout de nouvelle catégorie  -->
    <script>
        // Ce bloc n'est pertinent que si la gestion des catégories est présente (admin).
        if (catCheckbox) {
            const addCategoryButton = document.getElementById('add-category-button');
            const addCategoryDialog = document.getElementById('addCategoryDialog');
            const addCategoryModalInstance = new bootstrap.Modal(addCategoryDialog);
            const addCategoryForm = document.getElementById('add-category-form');
            const addCategoryInput = addCategoryDialog.querySelector('#new-category-name');
            const addCategorySaveButton = addCategoryDialog.querySelector('.btn-primary');

            addCategoryButton.disabled = !catCheckbox.checked;

            let isAddingCategory = false;

            async function submitNewCategory() {
                if (isAddingCategory) {
                    return;
                }

                const name = addCategoryInput.value.trim();
                if (!name) {
                    alert('Veuillez entrer un nom de catégorie valide.');
                    return;
                }

                isAddingCategory = true;
                addCategorySaveButton.disabled = true;

                try {
                    const response = await fetch('/api/edit-category.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            organisation_id: <?= $organisation->id ?>,
                            action: 'add',
                            name: name
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        addCategoryModalInstance.hide();
                        reloadCategoriesManagement(); // Recharger la gestion des catégories pour afficher la nouvelle catégorie
                    } else {
                        alert(result.error || 'Une erreur est survenue lors de l\'ajout de la catégorie.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Une erreur est survenue lors de l\'ajout de la catégorie.');
                } finally {
                    isAddingCategory = false;
                    addCategorySaveButton.disabled = false;
                }
            }

            addCategoryButton.addEventListener('click', () => {
                if (addCategoryButton.disabled || !catCheckbox.checked) {
                    return;
                }

                // Réinitialiser le champ du nom de la catégorie
                addCategoryInput.value = '';
                addCategoryModalInstance.show();
                setTimeout(() => {
                    addCategoryInput.focus();
                }, 500); // Délai pour s'assurer que le modal est complètement affiché avant de mettre le focus
            });

            addCategorySaveButton.addEventListener('click', submitNewCategory);

            addCategoryForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await submitNewCategory();
            });
        } // fin if (catCheckbox)

    </script>

    <!-- Script de gestion des catégories et sous catégories-->

    <script>
        // Ce bloc n'est pertinent que si la gestion des catégories est présente (admin).
        if (catCheckbox && subcatCheckbox) {
            catCheckbox.addEventListener('change', async () => {
                try {
                    const response = await fetch('/api/update-organisation-settings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            organisation_id: <?= $organisation->id ?>,
                            setting: 'use_cat',
                            value: catCheckbox.checked
                        })
                    });
                    const result = await response.json();
                    if (!result.success) {
                        alert(result.error || 'Une erreur est survenue lors de la mise à jour des paramètres de l\'organisation.');
                        catCheckbox.checked = !catCheckbox.checked; // Revenir à l'état précédent en cas d'erreur
                    } else {
                        const addCategoryButton = document.getElementById('add-category-button');
                        if (!result.categories) {
                            subcatCheckbox.checked = false;
                            subcatCheckbox.parentElement.style.display = 'none';
                            if (addCategoryButton) addCategoryButton.disabled = true;
                        } else {
                            subcatCheckbox.parentElement.style.display = 'block';
                            if (addCategoryButton) addCategoryButton.disabled = false;
                        }
                        reloadCategoriesManagement();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Une erreur est survenue lors de la mise à jour des paramètres de l\'organisation.');
                    catCheckbox.checked = !catCheckbox.checked; // Revenir à l'état précédent en cas d'erreur
                }
            });

            subcatCheckbox.addEventListener('change', async () => {
                try {
                    const response = await fetch('/api/update-organisation-settings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            organisation_id: <?= $organisation->id ?>,
                            setting: 'use_subcat',
                            value: subcatCheckbox.checked
                        })
                    });
                    const result = await response.json();
                    if (!result.success) {
                        alert(result.error || 'Une erreur est survenue lors de la mise à jour des paramètres de l\'organisation.');
                        subcatCheckbox.checked = !subcatCheckbox.checked; // Revenir à l'état précédent en cas d'erreur
                    } else {
                        // Si les sous-catégories sont activées, activer aussi les catégories
                        if (subcatCheckbox.checked) {
                            catCheckbox.checked = true;
                            catCheckbox.parentElement.style.display = 'block';
                        }

                        reloadCategoriesManagement(); // Recharger la gestion des catégories pour afficher ou masquer les options de sous-catégories
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Une erreur est survenue lors de la mise à jour des paramètres de l\'organisation.');
                    subcatCheckbox.checked = !subcatCheckbox.checked; // Revenir à l'état précédent en cas d'erreur
                }
            });

            async function reloadCategoriesManagement() {
                const container = document.getElementById('categories-management-container');
                const newContent = await generateCategoriesManagement(<?= $organisation->id ?>);
                if (container.innerHTML.trim() === '') {
                    container.appendChild(newContent);
                } else {
                    container.replaceChild(newContent, container.firstChild);
                }

                // Réinitialiser la Map des modifications à chaque rechargement de la gestion des catégories
                modifiedSubcategories = new Map();
            }
            // Exposée globalement: appelée aussi depuis le script d'ajout de catégorie.
            window.reloadCategoriesManagement = reloadCategoriesManagement;
            manageTab.addEventListener('show.bs.tab', reloadCategoriesManagement);

            // Charger le tableau de gestion des catégories au chargement de la page
            // (évite de devoir désactiver/réactiver les catégories pour l'afficher).
            reloadCategoriesManagement();

            // Utiliser une Map pour tracker les modifications (clé = ID ou temp_id, valeur = objet modification)
            let modifiedSubcategories = new Map();

            editCategoryDialog.addEventListener('show.bs.modal', async (event) => {
                if (!catCheckbox.checked) {
                    event.preventDefault();
                    return;
                }

                // Réinitialiser la Map à chaque ouverture du modal
                modifiedSubcategories.clear();

                const categoryId = event.relatedTarget.getAttribute('data-category-id');
                const modalBody = editCategoryDialog.querySelector('.modal-body');

                const response = await fetch('/api/get-subcategories.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        categoryId: categoryId
                    })
                });

                const result = await response.json();

                if (result.success && result.subcategories) {
                    editCategoryDialog.querySelector('#category-name').value = result.categoryName;

                    const subcategoriesEnabled = subcatCheckbox.checked;
                    if (!subcategoriesEnabled) {
                        const disabledFieldset = document.createElement('fieldset');
                        disabledFieldset.id = 'subcategories-fieldset';
                        disabledFieldset.classList.add('mt-3');
                        disabledFieldset.innerHTML = `
                        <div class="d-flex flex-row justify-content-between align-items-center mb-3">
                            <legend class="col-form-label text-secondary">Sous-catégories</legend>
                        </div>
                        <div class="alert alert-light border text-secondary mb-0" role="alert">
                            Les sous-catégories sont désactivées pour cette organisation et ne sont pas modifiables.
                        </div>
                    `;

                        if (document.getElementById('subcategories-fieldset')) {
                            modalBody.replaceChild(disabledFieldset, document.getElementById('subcategories-fieldset'));
                        } else {
                            modalBody.appendChild(disabledFieldset);
                        }
                    } else {
                        const subcategories = result.subcategories;
                        const fieldset = document.createElement('fieldset');
                        fieldset.id = 'subcategories-fieldset';
                        fieldset.classList.add('mt-3');
                        fieldset.innerHTML = `<div class="d-flex flex-row justify-content-between align-items-center mb-3">
            <legend class="col-form-label">Sous-catégories</legend>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add-subcategory-btn">Ajouter</button>
        </div>`;

                        const inputContainer = document.createElement('div');
                        inputContainer.classList.add('d-flex', 'flex-column', 'align-items-center', 'gap-3');

                        // Fonction utilitaire pour gérer la mise à jour d'une sous-catégorie
                        const updateSubcategory = (id, name, isNew = false) => {
                            const key = id;
                            if (isNew) {
                                modifiedSubcategories.set(key, { name, action: 'add', temp_id: id });
                            } else {
                                // Vérifier si déjà modifiée
                                if (modifiedSubcategories.has(key)) {
                                    modifiedSubcategories.get(key).name = name;
                                } else {
                                    // Ajouter à la Map avec action 'update' uniquement si le nom a changé
                                    const originalName = subcategories.find(s => s.id === id)?.name;
                                    if (originalName !== name) {
                                        modifiedSubcategories.set(key, { id, name, action: 'update' });
                                    } else if (modifiedSubcategories.has(key)) {
                                        modifiedSubcategories.delete(key);
                                    }
                                }
                            }
                        };

                        // Fonction utilitaire pour supprimer une sous-catégorie
                        const deleteSubcategory = (element, id, isNew = false) => {
                            if (!isNew) {
                                modifiedSubcategories.set(id, { id, action: 'delete' });
                            } else {
                                modifiedSubcategories.delete(id);
                            }
                            element.remove();
                        };

                        // Fonction pour créer un élément de sous-catégorie
                        const createSubcatElement = (subcat, isNew = false) => {
                            const subcatElement = document.createElement('div');
                            subcatElement.classList.add('d-flex', 'flex-row', 'align-items-center', 'gap-2', 'w-100', 'justify-content-center');

                            const tempId = isNew ? `new-${Date.now()}` : subcat.id;
                            const placeholder = isNew ? 'Nouvelle sous-catégorie' : '';
                            const value = isNew ? '' : subcat.name;

                            subcatElement.innerHTML = `
                            <input type="text" class="form-control subcategory-name" value="${value}" data-subcategory-id="${tempId}" placeholder="${placeholder}" required>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-subcategory-btn" data-subcategory-id="${tempId}">Supprimer</button>
                        `;

                            const input = subcatElement.querySelector('.subcategory-name');
                            const deleteBtn = subcatElement.querySelector('.delete-subcategory-btn');

                            // Écouteur pour la modification du nom
                            input.addEventListener('input', (event) => {
                                updateSubcategory(tempId, event.target.value, isNew);
                            });

                            // Écouteur pour la suppression
                            deleteBtn.addEventListener('click', (event) => {
                                event.preventDefault();
                                deleteSubcategory(subcatElement, tempId, isNew);
                            });

                            return subcatElement;
                        };

                        // Ajouter les sous-catégories existantes
                        subcategories.forEach(subcat => {
                            inputContainer.appendChild(createSubcatElement(subcat, false));
                        });

                        fieldset.appendChild(inputContainer);

                        // Écouteur pour le bouton "Ajouter"
                        const addButton = fieldset.querySelector('#add-subcategory-btn');
                        addButton.addEventListener('click', (event) => {
                            event.preventDefault();
                            const newElement = createSubcatElement({}, true);
                            inputContainer.appendChild(newElement);
                            newElement.querySelector('.subcategory-name').focus();
                        });

                        // Remplacer ou ajouter le fieldset
                        if (document.getElementById('subcategories-fieldset')) {
                            modalBody.replaceChild(fieldset, document.getElementById('subcategories-fieldset'));
                        } else {
                            modalBody.appendChild(fieldset);
                        }
                    }
                } else {
                    modalBody.innerHTML = '<p class="text-danger">Une erreur est survenue lors du chargement des sous-catégories.</p>';
                }


                const saveButton = editCategoryDialog.querySelector('.modal-footer .btn-primary');
                const newSaveButton = saveButton.cloneNode(true);
                saveButton.parentNode.replaceChild(newSaveButton, saveButton);

                newSaveButton.addEventListener('click', async () => {
                    const categoryName = document.getElementById('category-name').value.trim();
                    if (!categoryName) {
                        alert('Le nom de la catégorie ne peut pas être vide.');
                        return;
                    }

                    if (modifiedSubcategories.size === 0 && categoryName === result.categoryName) {
                        editCategoryModalInstance.hide();
                        return;
                    }

                    try {
                        const response = await fetch('/api/update-category.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                category_id: categoryId,
                                name: categoryName,
                                subcategories: Array.from(modifiedSubcategories.values())
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            editCategoryModalInstance.hide();
                            reloadCategoriesManagement(); // Recharger la gestion des catégories pour refléter les changements
                        } else {
                            alert(result.error || 'Une erreur est survenue lors de la mise à jour de la catégorie.');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Une erreur est survenue lors de la mise à jour de la catégorie.');
                    }
                });
            });
        } // fin if (catCheckbox && subcatCheckbox)
    </script>
    <script>
        // Gestion de l'onglet "Historique des remboursements"
        const repaymentHistoryTab = document.getElementById('tab-repayment-history-tab');
        if (repaymentHistoryTab) {
            repaymentHistoryTab.addEventListener('show.bs.tab', async () => {
                await loadRepaymentHistory(<?= $organisation->id ?>, 1);
            });
        }
    </script>

    <script>
        // Synchronisation navigation mobile (select) <-> onglets Bootstrap (desktop)
        (function () {
            const mobileSelect = document.getElementById('org-tabs-mobile');
            if (!mobileSelect) return;

            // Quand on change le select mobile, activer l'onglet Bootstrap correspondant
            mobileSelect.addEventListener('change', () => {
                const targetSelector = mobileSelect.value;
                const desktopTab = document.querySelector(`#org-tabs-desktop [data-bs-target="${targetSelector}"]`);
                if (desktopTab) {
                    bootstrap.Tab.getOrCreateInstance(desktopTab).show();
                }
            });

            // Quand un onglet desktop change, mettre à jour le select mobile (utile au redimensionnement)
            document.querySelectorAll('#org-tabs-desktop [data-bs-toggle="tab"]').forEach(tabBtn => {
                tabBtn.addEventListener('shown.bs.tab', (event) => {
                    const target = event.target.getAttribute('data-bs-target');
                    if (mobileSelect.value !== target) {
                        mobileSelect.value = target;
                    }
                });
            });
        })();
    </script>
</body>

</html>