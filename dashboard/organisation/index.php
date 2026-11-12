<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/organisation-page.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

/*
    Page d'accueil d'une organisation : « Nouvelle demande » (/dashboard/organisation/?id=…).
    - Formulaire de demande de remboursement : libellé, montant, catégorie et sous-catégorie (si l'organisation
      les utilise), date et quittances (scripts/pages/organisation/submit.js).
    - Caissiers et administrateurs : choix du bénéficiaire (soi-même, un membre, ou une nouvelle personne
      sans compte saisie dans un modal, includes/modals/new-person-modal.php).
*/

$categoryManager = new CategoryManager($mysqlClient, $organisation);
$categories = $organisation->areCategoriesEnabled() ? $categoryManager->getCategories() : [];
$showSubcategories = $categories && $organisation->areSubcategoriesEnabled() && $categoryManager->countSubcategories() > 0;

// Bénéficiaires possibles (caissiers) : membres avec compte, puis personnes sans compte
$beneficiaryGroups = ['members' => ['label' => 'Membres', 'users' => []], 'accountless' => ['label' => 'Sans compte', 'users' => []]];
if ($isCashier) {
    foreach ($organisationManager->getMembers($organisation) as ['user' => $member]) {
        if ($member->getId() !== $user->getId()) {
            $beneficiaryGroups[$member->isRegistered() ? 'members' : 'accountless']['users'][] = $member;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Nouvelle demande | ' . $organisation->getName(); // échappé par head.php
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4" id="organisation-page" data-organisation-id="<?= $organisation->getId() ?>" data-organisation-name="<?= e($organisation->getName()) ?>">
        <?php $heading = 'Nouvelle demande de remboursement'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/page-heading.php'; ?>

        <div class="card">
            <div class="card-body p-4">
                <p class="text-muted mb-4">Remplissez le formulaire ci-dessous et joignez vos quittances (PNG, JPEG ou PDF).</p>
                <div id="message-container-refund-form"></div>
                <form id="refund-form">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <input type="hidden" name="organisation-id" value="<?= $organisation->getId() ?>">
                    <div class="row">
                        <?php if ($isCashier): ?>
                            <!-- Caissiers et admins : demande pour quelqu'un d'autre -->
                            <div class="mb-3 col-md-3">
                                <label for="beneficiary" class="form-label">Bénéficiaire</label>
                                <select id="beneficiary" name="beneficiary" class="form-select">
                                    <option value="self" selected>Moi-même</option>
                                    <?php foreach ($beneficiaryGroups as $groupKey => $group): ?>
                                        <optgroup label="<?= e($group['label']) ?>" data-<?= $groupKey ?>-options<?= $group['users'] ? '' : ' hidden' ?>>
                                            <?php foreach ($group['users'] as $member): ?>
                                                <option value="<?= $member->getId() ?>"><?= e($member->getFullName()) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                    <option value="new">Nouvelle personne…</option>
                                </select>
                                <small class="form-text text-muted d-none" data-member-hint>IBAN enregistré utilisé.</small>
                                <small class="form-text text-muted d-none" data-new-person-summary>
                                    <span data-new-person-name></span> ·
                                    <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-action="edit-new-person">Modifier</button>
                                </small>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3 <?= $isCashier ? 'col-md-6' : 'col-md-9' ?>">
                            <label for="label" class="form-label">Libellé</label>
                            <input type="text" id="label" name="label" class="form-control" required maxlength="100">
                        </div>
                        <div class="mb-3 col-md-3">
                            <label for="amount" class="form-label">Montant [<?= e($organisation->getCurrency()) ?>]</label>
                            <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01" max="99999999.99" required>
                        </div>
                    </div>
                    <div class="row">
                        <?php if ($categories): ?>
                            <div class="mb-3 col-md-3">
                                <label for="category" class="form-label">Catégorie</label>
                                <select id="category" name="category" class="form-select" required>
                                    <option value="" selected disabled hidden>Choisir une catégorie</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category->getId() ?>"><?= e($category->getName()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($showSubcategories): ?>
                                <div class="mb-3 col-md-3">
                                    <label for="subcategory" class="form-label">Sous-catégorie</label>
                                    <!-- Options chargées selon la catégorie choisie (scripts/components/subcategory-select.js) -->
                                    <select id="subcategory" name="subcategory" class="form-select">
                                        <option value="" selected disabled hidden>Choisir une sous-catégorie</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="mb-3 col-md-6">
                            <label for="date" class="form-label">Date de la dépense</label>
                            <input type="date" id="date" name="date" class="form-control" max="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="receipts" class="form-label">Justificatifs</label>
                            <input type="file" id="receipts" name="receipts[]" class="form-control"
                                accept="image/png,image/jpeg,application/pdf" multiple required>
                        </div>
                    </div>
                    <?php $urgentFieldId = 'urgent'; $urgentChecked = false; include $_SERVER['DOCUMENT_ROOT'] . '/includes/urgent-field.php'; ?>
                    <button type="submit" class="btn btn-primary">Soumettre la demande</button>
                </form>
            </div>
        </div>
    </main>

    <?php
    if ($isCashier) {
        include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/new-person-modal.php';
    }
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php';
    ?>
    <script type="module" src="<?= asset('/scripts/pages/organisation/submit.js') ?>"></script>
</body>

</html>
