<?php
$requiredRole = 'admin';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/organisation-page.php';

/*
    « Paramètres de l'organisation » (/dashboard/organisation/settings/?id=…, engrenage du header), administrateurs :
    - catégories et sous-catégories (scripts/pages/organisation/categories.js) ;
    - devise (scripts/pages/organisation/currency.js) ;
    - renommage et suppression de l'organisation (scripts/pages/organisation/settings.js).
*/
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Paramètres | ' . $organisation->getName();
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4" id="organisation-page" data-organisation-id="<?= $organisation->getId() ?>" data-organisation-name="<?= e($organisation->getName()) ?>">
        <?php $heading = "Paramètres de l'organisation"; include $_SERVER['DOCUMENT_ROOT'] . '/includes/page-heading.php'; ?>

        <!-- Catégories -->
        <section class="card mb-4">
            <div class="card-body">
                <h2 class="card-title fs-4">Catégories et sous-catégories</h2>
                <p class="text-muted">Les demandes de remboursement peuvent être classées par catégorie et sous-catégorie.
                    Désactiver une option ne supprime rien : elle peut être réactivée plus tard.</p>
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/category-setup.php'; ?>
            </div>
        </section>

        <!-- Devise -->
        <section class="card mb-4">
            <div class="card-body">
                <h2 class="card-title fs-4">Devise</h2>
                <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/currency-setup.php'; ?>
            </div>
        </section>

        <!-- Renommage -->
        <section class="card mb-4">
            <div class="card-body">
                <h2 class="card-title fs-4">Renommer l'organisation</h2>
                <p class="text-muted mb-3">Ce nom est visible par tous les membres.</p>
                <div id="rename-org-message"></div>
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md">
                        <label for="organisation-name" class="form-label">Nom de l'organisation</label>
                        <input type="text" class="form-control" id="organisation-name" value="<?= e($organisation->getName()) ?>" maxlength="100" required>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" id="rename-organisation-button" class="btn btn-primary w-100">Renommer</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Zone de danger -->
        <section class="card border-danger">
            <div class="card-body">
                <h2 class="card-title fs-4 text-danger">Supprimer l'organisation</h2>
                <p class="text-muted mb-3">
                    La suppression est <strong>définitive et irréversible</strong>.
                    Toutes les demandes, catégories, reçus et l'historique seront supprimés.
                </p>
                <button type="button" id="delete-organisation-button" class="btn btn-outline-danger"
                    data-bs-toggle="modal" data-bs-target="#deleteOrganisationDialog">
                    <i class="bi bi-exclamation-triangle"></i> Supprimer l'organisation
                </button>
            </div>
        </section>
    </main>

    <?php
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/category-modals.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/delete-organisation-modal.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php';
    ?>
    <script type="module" src="/scripts/pages/organisation/settings.js"></script>
</body>

</html>
