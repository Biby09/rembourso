<?php
/*
    Titre d'une page de l'application : nom de l'organisation en petit, au-dessus du titre de la page,
    et un bouton d'action facultatif à droite.

    Prérequis : $heading (titre de la page).
    Optionnel : $organisation (nom affiché au-dessus) ; $headingAction (HTML du bouton, déjà échappé).
*/
?>
<div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
    <div class="text-break">
        <?php if (isset($organisation)): ?>
            <p class="text-muted mb-0" data-organisation-name-display><?= e($organisation->getName()) ?></p>
        <?php endif; ?>
        <h1 class="fs-3 mb-0"><?= e($heading) ?></h1>
    </div>
    <?= $headingAction ?? '' ?>
</div>
