<?php
/*
    Barre de navigation fixée en bas de l'écran, sur mobile et tablette (moins de 1200 px, là où le menu du haut
    ne tient plus), dans l'esprit d'une application : icône + libellé, bouton central « Nouvelle demande » mis en avant,
    et « Plus » qui ouvre un panneau avec les autres pages (offcanvas Bootstrap).
    Incluse par includes/app-header.php, qui fournit $appCurrent, $appLinks, $appSettings, $appIsActive, $appUrl.

    Dans une organisation, caissiers et admins : Mes demandes · À rembourser · [+ Demande] · Historique · Plus
                           membres            : + Demande · Tableau de bord · [Mes demandes] · Mon compte · Plus
    Hors organisation     : Tableau de bord · Mon compte · Plus
*/

// Pages hors organisation, avec leur icône (Bootstrap Icons)
$navDashboard = ['label' => 'Tableau de bord', 'short' => 'Accueil', 'icon' => 'bi-grid', 'url' => '/dashboard',
    'active' => $appScript === '/dashboard/index.php'];
$navAccount = ['label' => 'Mon compte', 'short' => 'Compte', 'icon' => 'bi-person', 'url' => '/dashboard/account',
    'active' => $appScript === '/dashboard/account/index.php'];

// Pages de l'organisation : lien du header + icône et libellé court
$navIcons = [
    '' => ['bi-plus-lg', 'Demande'],
    'requests/' => ['bi-receipt', 'Demandes'],
    'cashier/' => ['bi-cash-coin', 'À rembourser'],
    'history/' => ['bi-clock-history', 'Historique'],
    'members/' => ['bi-people', 'Membres'],
    'settings/' => ['bi-gear', 'Paramètres'],
];
$navOrganisation = [];
foreach (array_merge($appLinks, $appSettings ? [$appSettings] : []) as $link) {
    [$icon, $short] = $navIcons[$link['path']];
    $navOrganisation[$link['path']] = ['label' => $link['label'], 'short' => $short, 'icon' => $icon,
        'url' => $appUrl($link), 'active' => $appIsActive($link)];
}

// Barre : 2 boutons, le bouton central (dans une organisation), 1 bouton, puis « Plus »
if ($appCurrent && isset($navOrganisation['cashier/'])) {
    // Caissiers et admins : la nouvelle demande au centre
    $navCenter = $navOrganisation[''];
    $navItems = [$navOrganisation['requests/'], $navOrganisation['cashier/'], $navOrganisation['history/']];
} elseif ($appCurrent) {
    // Membres : leurs demandes au centre, la nouvelle demande en premier
    $navCenter = $navOrganisation['requests/'];
    $navItems = [$navOrganisation[''], $navDashboard, $navAccount];
} else {
    $navCenter = null;
    $navItems = [$navDashboard, $navAccount];
}

// Panneau « Plus » : tout ce qui n'est pas déjà dans la barre
$shownUrls = array_column(array_merge($navItems, $navCenter ? [$navCenter] : []), 'url');
$navMore = array_values(array_filter(
    array_merge(array_values($navOrganisation), [$navDashboard, $navAccount]),
    fn(array $item) => !in_array($item['url'], $shownUrls, true)
));
$navMoreActive = (bool) array_filter($navMore, fn(array $item) => $item['active']);

$navButton = function (array $item): void { ?>
    <a class="app-bottom-nav-item<?= $item['active'] ? ' active' : '' ?>" href="<?= e($item['url']) ?>"
        <?= $item['active'] ? 'aria-current="page"' : '' ?> aria-label="<?= e($item['label']) ?>">
        <i class="bi <?= $item['icon'] ?>" aria-hidden="true"></i>
        <span><?= e($item['short']) ?></span>
    </a>
<?php };
?>
<nav class="app-bottom-nav d-xl-none" aria-label="Navigation principale">
    <?php $navButton($navItems[0]); ?>
    <?php $navButton($navItems[1]); ?>

    <?php if ($navCenter): ?>
        <!-- Bouton central : page principale selon le rôle (nouvelle demande, ou mes demandes pour un membre) -->
        <a class="app-bottom-nav-center<?= $navCenter['active'] ? ' active' : '' ?>" href="<?= e($navCenter['url']) ?>"
            <?= $navCenter['active'] ? 'aria-current="page"' : '' ?> aria-label="<?= e($navCenter['label']) ?>">
            <span class="app-bottom-nav-center-circle"><i class="bi <?= $navCenter['icon'] ?>" aria-hidden="true"></i></span>
            <span><?= e($navCenter['short']) ?></span>
        </a>
    <?php endif; ?>

    <?php if (isset($navItems[2])) $navButton($navItems[2]); ?>

    <button type="button" class="app-bottom-nav-item<?= $navMoreActive ? ' active' : '' ?>" data-bs-toggle="offcanvas"
        data-bs-target="#app-more-menu" aria-controls="app-more-menu" aria-label="Plus de pages">
        <i class="bi bi-list" aria-hidden="true"></i>
        <span>Plus</span>
    </button>
</nav>

<!-- Panneau « Plus » : s'ouvre depuis le bas de l'écran -->
<div class="offcanvas offcanvas-bottom app-more-menu d-xl-none" tabindex="-1" id="app-more-menu" aria-labelledby="app-more-menu-title">
    <div class="offcanvas-header pb-2">
        <div class="text-truncate">
            <h2 class="offcanvas-title fs-5 mb-0" id="app-more-menu-title"><?= e($appCurrent?->getName() ?? 'Menu') ?></h2>
            <small class="text-muted"><?= e($user->getFullName()) ?></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <div class="list-group list-group-flush">
            <?php foreach ($navMore as $item): ?>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3<?= $item['active'] ? ' active' : '' ?>"
                    href="<?= e($item['url']) ?>" <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= $item['icon'] ?> fs-5" aria-hidden="true"></i><?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" href="/">
                <i class="bi bi-house fs-5" aria-hidden="true"></i>Site Rembourso
            </a>
            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 text-danger" href="/logout">
                <i class="bi bi-box-arrow-right fs-5" aria-hidden="true"></i>Se déconnecter
            </a>
        </div>
        <!-- Liens du footer (masqué sur mobile : includes/app-footer.php) -->
        <ul class="list-inline small text-muted text-center mt-3 mb-0">
            <li class="list-inline-item"><a href="/contact/" class="link-secondary">Contact</a></li>
            <li class="list-inline-item"><a href="/mentions-legales.php" class="link-secondary">Mentions légales</a></li>
            <li class="list-inline-item">Version <?= e(APP_VERSION) ?></li>
        </ul>
    </div>
</div>
