<?php
$requiredRole = 'admin';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/organisation-page.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/category/categoryManager.php';

/*
    Assistant de configuration d'une organisation, affiché juste après sa création
    (/dashboard/organisation/init-orga.php?id=…&page=1, redirection de api/organisation/create-organisation.php).
    Administrateurs uniquement ; on peut le quitter à tout moment (« Terminer plus tard ») :
    tous les réglages restent modifiables dans « Paramètres de l'organisation » et « Membres ».

    Déroulement :
        Page 1 : bienvenue et devise des remboursements
        Page 2 : catégories et sous-catégories des dépenses
        Page 3 : invitation des membres (trésorier, caissiers…)
        Page 4 : récapitulatif et premiers pas

    Chaque étape est un fichier de dashboard/organisation/init-orga/ ; cette page affiche autour
    la progression et les boutons Retour / Suivant (scripts/pages/organisation/init-orga.js).
*/

$steps = [
    1 => ['title' => 'Bienvenue', 'file' => 'page1.php'],
    2 => ['title' => 'Catégories', 'file' => 'page2.php'],
    3 => ['title' => 'Membres', 'file' => 'page3.php'],
    4 => ['title' => 'Terminé', 'file' => 'page4.php'],
];
$step = (int) ($_GET['page'] ?? 1);
if (!isset($steps[$step])) {
    $step = 1;
}
$stepCount = count($steps);

/** Adresse d'une étape de l'assistant */
$stepUrl = fn(int $number) => '/dashboard/organisation/init-orga.php?id=' . $organisation->getId() . '&page=' . $number;
?>
<!DOCTYPE html>
<html lang="fr">
<?php
$page_title = 'Configuration | ' . $organisation->getName();
$page_description = "Configuration de l'organisation";
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';
?>

<body class="app">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-header.php'; ?>

    <main class="container py-4" id="organisation-page" data-organisation-id="<?= $organisation->getId() ?>" data-organisation-name="<?= e($organisation->getName()) ?>">
        <!-- Progression -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-end gap-2 mb-2">
                <p class="text-muted mb-0">Configuration de « <span data-organisation-name-display><?= e($organisation->getName()) ?></span> »</p>
                <p class="text-muted small mb-0 text-nowrap">Étape <?= $step ?> sur <?= $stepCount ?></p>
            </div>
            <div class="progress" role="progressbar" aria-label="Progression de la configuration"
                aria-valuenow="<?= $step ?>" aria-valuemin="1" aria-valuemax="<?= $stepCount ?>" style="height: 6px;">
                <div class="progress-bar" style="width: <?= round($step / $stepCount * 100) ?>%"></div>
            </div>
            <ol class="list-inline small mt-2 mb-0 d-none d-md-block">
                <?php foreach ($steps as $number => $stepInfo): ?>
                    <li class="list-inline-item me-3 <?= $number === $step ? 'fw-semibold text-primary' : 'text-muted' ?>">
                        <?= $number ?>. <?= e($stepInfo['title']) ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- Contenu de l'étape -->
        <div class="card mb-4">
            <div class="card-body p-4">
                <?php include __DIR__ . '/init-orga/' . $steps[$step]['file']; ?>
            </div>
        </div>

        <!-- Navigation -->
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-between align-items-sm-center">
            <?php if ($step < $stepCount): ?>
                <a href="<?= e($stepUrl($step + 1)) ?>" class="btn btn-primary btn-lg order-sm-3">Suivant</a>
            <?php else: ?>
                <a href="<?= e(organisation_url($organisation)) ?>" class="btn btn-primary btn-lg order-sm-3">Terminer la configuration</a>
            <?php endif; ?>
            <?php if ($step > 1): ?>
                <a href="<?= e($stepUrl($step - 1)) ?>" class="btn btn-outline-secondary order-sm-1">Retour</a>
            <?php endif; ?>
            <?php if ($step < $stepCount): ?>
                <a href="<?= e(organisation_url($organisation)) ?>" class="btn btn-link text-muted order-sm-2 ms-sm-auto me-sm-3">Terminer plus tard</a>
            <?php endif; ?>
        </div>
    </main>

    <?php
    // Modals utilisés par les étapes 2 (catégories) et 3 (invitations)
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/category-modals.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/modals/member-modals.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-footer.php';
    ?>
    <script type="module" src="/scripts/pages/organisation/init-orga.js"></script>
</body>

</html>
