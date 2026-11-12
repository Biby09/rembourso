<?php
/*
    Header de l'application (pages /dashboard, /invitation), à la place du header du site public (includes/header.php) :
    - gauche : logo (→ tableau de bord), sélecteur de l'organisation courante, engrenage des paramètres (administrateurs) ;
    - milieu (grand écran, dès 1200 px) : pages de l'organisation courante, selon le rôle ;
    - droite : menu du compte ;
    - mobile et tablette (moins de 1200 px) : logo et organisation en haut, navigation fixée en bas de l'écran
      (includes/app-bottom-nav.php, incluse à la fin de ce fichier).
    La page doit avoir <body class="app">.

    Prérequis : $user et $organisationManager (config/auth.php).
    Optionnel : $organisation (organisation courante) ; dans ce cas aussi $isCashier et $isAdmin (config/organisation-page.php).
*/
$appOrganisations = $organisationManager->getAllOrganisationsForUser();
$appCurrent = $organisation ?? null;
$appScript = $_SERVER['SCRIPT_NAME'];

// Pages de l'organisation courante. 'path' : dossier de la page (le lien est actif, en gras, sur cette page)
$appLinks = [];
$appSettings = null;
if ($appCurrent) {
    $appLinks[] = ['label' => 'Nouvelle demande', 'path' => ''];
    $appLinks[] = ['label' => 'Mes demandes', 'path' => 'requests/'];
    if ($isCashier ?? false) {
        $appLinks[] = ['label' => 'À rembourser', 'path' => 'cashier/'];
        $appLinks[] = ['label' => 'Historique', 'path' => 'history/'];
    }
    if ($isAdmin ?? false) {
        $appLinks[] = ['label' => 'Membres', 'path' => 'members/'];
        $appSettings = ['label' => "Paramètres de l'organisation", 'path' => 'settings/'];
    }
}

/** Le lien mène-t-il à la page affichée ? (/dashboard/organisation/ + dossier + index.php) */
$appIsActive = fn(array $link) => $appScript === '/dashboard/organisation/' . $link['path'] . 'index.php';
$appUrl = fn(array $link) => '/dashboard/organisation/' . $link['path'] . '?id=' . $appCurrent->getId();
?>
<header class="app-header border-bottom border-primary border-5 fixed-top bg-white">
    <nav class="navbar navbar-expand-xl" aria-label="Navigation de l'application">
        <div class="container">
            <!-- Gauche : logo, organisation courante et paramètres -->
            <div class="d-flex align-items-center gap-3 app-header-start">
                <a href="/dashboard" class="flex-shrink-0" title="Tableau de bord">
                    <img src="/media/logo.svg" alt="Rembourso – tableau de bord" height="20">
                </a>

                <?php if ($appOrganisations): ?>
                    <div class="dropdown app-org-switch">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100 d-flex align-items-center gap-2 text-start" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Organisation : <?= e($appCurrent?->getName() ?? 'aucune') ?> (changer)">
                            <i class="bi bi-building flex-shrink-0" aria-hidden="true"></i>
                            <span class="text-truncate flex-grow-1" data-organisation-name-display><?= e($appCurrent?->getName() ?? 'Mes organisations') ?></span>
                        </button>
                        <ul class="dropdown-menu shadow w-100">
                            <li><h6 class="dropdown-header">Mes organisations</h6></li>
                            <?php foreach ($appOrganisations as $appOrganisation): ?>
                                <?php $isCurrent = $appCurrent?->getId() === $appOrganisation->getId(); ?>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between gap-3 <?= $isCurrent ? 'active' : '' ?>"
                                        href="/dashboard/organisation/?id=<?= $appOrganisation->getId() ?>" <?= $isCurrent ? 'aria-current="true"' : '' ?>>
                                        <span class="text-truncate"><?= e($appOrganisation->getName()) ?></span>
                                        <?php if ($isCurrent): ?><i class="bi bi-check-lg" aria-hidden="true"></i><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/dashboard">Tableau de bord</a></li>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($appSettings): ?>
                    <!-- Engrenage : paramètres de l'organisation (bouton plein sur la page des paramètres) -->
                    <a class="btn btn-sm <?= $appIsActive($appSettings) ? 'btn-secondary' : 'btn-outline-secondary' ?> flex-shrink-0 d-none d-xl-inline-flex align-items-center"
                        href="<?= $appUrl($appSettings) ?>" title="<?= e($appSettings['label']) ?>" aria-label="<?= e($appSettings['label']) ?>"
                        <?= $appIsActive($appSettings) ? 'aria-current="page"' : '' ?>>
                        <i class="bi bi-gear" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Droite : compte (grand écran, dès 1200 px ; sur mobile, dans la barre du bas). order-xl-last : après les liens du milieu -->
            <div class="d-flex align-items-center gap-2 order-xl-last ms-2">
                <div class="dropdown d-none d-xl-block">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle app-account-button" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu du compte">
                        <i class="bi bi-person-fill" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><h6 class="dropdown-header"><?= e($user->getFullName()) ?></h6></li>
                        <li><a class="dropdown-item" href="/dashboard">Tableau de bord</a></li>
                        <li><a class="dropdown-item" href="/dashboard/account">Mon compte</a></li>
                        <li><a class="dropdown-item" href="/">Site Rembourso</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/logout">Se déconnecter</a></li>
                    </ul>
                </div>

            </div>

            <!-- Milieu (grand écran, dès 1200 px ; masqué en dessous : barre du bas) -->
            <div class="collapse navbar-collapse" id="appNavbarContent">
                <?php if ($appLinks): ?>
                    <ul class="navbar-nav mx-auto gap-xl-3">
                        <?php foreach ($appLinks as $link): ?>
                            <li class="nav-item">
                                <a class="nav-link text-nowrap <?= $appIsActive($link) ? 'active fw-semibold' : '' ?>" href="<?= $appUrl($link) ?>"
                                    <?= $appIsActive($link) ? 'aria-current="page"' : '' ?>><?= e($link['label']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/app-bottom-nav.php'; ?>

<script src="/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>