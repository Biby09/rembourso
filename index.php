<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
$page_title = 'Rembourso | Gestion des remboursements associatifs';
$page_description = 'Rembourso simplifie la gestion des demandes de remboursement pour les associations et organisations.';
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
        <section class="homepage-features py-5">
            <div class="container">
                <h2 class="text-center mb-5">Pourquoi utiliser cette plateforme ?</h2>
                <div class="homepage-feature-grid">
                    <article class="homepage-feature">
                        <div class="homepage-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                        <h3>Clarté</h3>
                        <p>Retrouvez vos demandes, vos organisations et vos invitations depuis votre tableau de bord.</p>
                    </article>
                    <article class="homepage-feature">
                        <div class="homepage-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <polyline points="12 7 12 12 15 14"></polyline>
                            </svg>
                        </div>
                        <h3>Gain de temps</h3>
                        <p>Déposez une demande en quelques étapes et laissez les responsables la traiter au même endroit.</p>
                    </article>
                    <article class="homepage-feature">
                        <div class="homepage-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 9l9-5 9 5-9 5-9-5z"></path>
                                <path d="M3 9v8l9 5 9-5V9"></path>
                                <path d="M12 14v8"></path>
                            </svg>
                        </div>
                        <h3>Organisation</h3>
                        <p>Utilisez les catégories et sous-catégories configurées par votre organisation.</p>
                    </article>
                    <article class="homepage-feature">
                        <div class="homepage-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"></path>
                                <path d="M9 12l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h3>Sécurisé</h3>
                        <p>Chaque organisation dispose d'un espace privé avec des accès adaptés aux rôles de chacun.</p>
                    </article>
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