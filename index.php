<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>


<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main style="margin-top: 0 !important;">
        <!-- HERO -->
        <section class="bg-gradient-primary text-white py-5 text-center">
            <div class="container">
                <h1 class="mb-4">Les remboursements de votre organisation, simplement.</h1>
                <p class="lead mb-4">Déposez vos demandes, joignez vos justificatifs et suivez chaque étape du traitement dans un espace partagé avec votre organisation.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="d-flex gap-2 justify-content-center">
                        <a class="btn btn-light" href="/register">Créer un compte</a>
                        <a class="btn btn-outline-light" href="/about.php">En savoir plus</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-2 justify-content-center">
                        <a class="btn btn-light" href="/dashboard">Mon espace</a>
                        <a class="btn btn-outline-light" href="/about.php">En savoir plus</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>

        <!-- AVANTAGES -->
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5">Pourquoi utiliser cette plateforme ?</h2>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Clarté</h3>
                                <img src="/media/home/clarete.svg" alt="Icone clarté" class="img-fluid my-3" style="max-width: 100px;">
                                <p class="card-text">Retrouvez vos demandes, vos organisations et vos invitations depuis votre tableau de bord.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Gain de temps</h3>
                                <img src="/media/home/time.svg" alt="Icone gain de temps" class="img-fluid my-3" style="max-width: 100px;">
                                <p class="card-text">Déposez une demande en quelques étapes et laissez les responsables la traiter au même endroit.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Organisation</h3>
                                <img src="/media/home/organisation.svg" alt="Icone organisation" class="img-fluid my-3" style="max-width: 100px;">
                                <p class="card-text">Utilisez les catégories et sous-catégories configurées par votre organisation.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Sécurisé</h3>
                                <img src="/media/home/safety.svg" alt="Icone sécurité" class="img-fluid my-3" style="max-width: 100px;">
                                <p class="card-text">Chaque organisation dispose d'un espace privé avec des accès adaptés aux rôles de chacun.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FONCTIONNALITES -->
        <section class="py-5 bg-light text-center">
            <div class="container">
                <h2 class="mb-5">Ce que vous pouvez faire</h2>
                <ul class="list-unstyled d-flex flex-wrap justify-content-center gap-4">
                    <li class="flex-grow-1">📄 Déposer des demandes de remboursement</li>
                    <li class="flex-grow-1">🧾 Joindre plusieurs justificatifs</li>
                    <li class="flex-grow-1">🔎 Suivre l'état de chaque demande</li>
                    <li class="flex-grow-1">🏷️ Organiser les dépenses par catégories</li>
                    <li class="flex-grow-1">💳 Traiter et historiser les remboursements</li>
                    <li class="flex-grow-1">👥 Inviter les membres et gérer les rôles</li>
                    <li class="flex-grow-1">🏢 Créer et administrer vos organisations</li>
                </ul>
            </div>
        </section>

        <!-- POUR QUI -->
        <section class="py-5 text-center">
            <div class="container">
                <h2 class="mb-4">Pour qui ?</h2>
                <p class="lead mb-5">La plateforme s'adapte aux organisations qui souhaitent partager une gestion claire des dépenses :</p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <span class="badge bg-primary p-3">Associations sportives</span>
                    <span class="badge bg-primary p-3">Groupe de jeunes</span>
                    <span class="badge bg-primary p-3">Associations étudiantes</span>
                    <span class="badge bg-primary p-3">Sociétés locales</span>
                    <span class="badge bg-primary p-3">Clubs et organisations diverses</span>
                </div>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="py-5 bg-primary text-white text-center">
            <div class="container">
                <h2 class="mb-4">Commencez dès aujourd'hui</h2>
                <p class="lead mb-4">Simplifiez la gestion financière de votre association en quelques clics.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-light btn-lg" href="/register">Créer un compte</a>
                <?php else: ?>
                    <a class="btn btn-light btn-lg" href="/dashboard">Mon espace</a>
                <?php endif; ?>
            </div>
        </section>

    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
</body>

</html>