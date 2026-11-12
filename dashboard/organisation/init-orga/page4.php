<?php
/* Assistant de configuration, étape 4 : récapitulatif (inclus par dashboard/organisation/init-orga.php). */

// Réglages enregistrés aux étapes précédentes (relus en base : ils ont pu changer sur les autres étapes)
$categoryCount = $organisation->areCategoriesEnabled()
    ? count((new CategoryManager($mysqlClient, $organisation))->getCategories())
    : 0;
$pendingInvitationCount = count($invitationManager->getPendingInvitationsOfOrganisation($organisation));
?>
<h1 class="fs-3">Votre organisation est prête</h1>
<p class="lead">Voici le récapitulatif de la configuration.</p>

<ul class="list-group mb-4">
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-coin me-2 text-primary" aria-hidden="true"></i>Devise</span>
        <span class="fw-semibold"><?= e(Organisation::CURRENCIES[$organisation->getCurrency()] ?? $organisation->getCurrency()) ?></span>
    </li>
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <span><i class="bi bi-tags me-2 text-primary" aria-hidden="true"></i>Catégories</span>
        <span class="fw-semibold">
            <?php if (!$organisation->areCategoriesEnabled()): ?>
                Désactivées
            <?php else: ?>
                <?= $categoryCount ?> catégorie<?= $categoryCount > 1 ? 's' : '' ?><?= $organisation->areSubcategoriesEnabled() ? ', avec sous-catégories' : '' ?>
            <?php endif; ?>
        </span>
    </li>
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <span><i class="bi bi-envelope me-2 text-primary" aria-hidden="true"></i>Invitations envoyées</span>
        <span class="fw-semibold"><?= $pendingInvitationCount ?></span>
    </li>
</ul>

<h2 class="fs-5">Et maintenant ?</h2>
<ul>
    <li>Déposez votre première demande dans « Nouvelle demande ».</li>
    <li>Les demandes en attente apparaissent dans « À rembourser », pour les caissiers.</li>
    <li>Tous ces réglages restent modifiables : engrenage <i class="bi bi-gear" aria-hidden="true"></i> du menu (paramètres) et page « Membres ».</li>
</ul>
