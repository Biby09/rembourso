<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php';

?>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    <main style="margin-top: 0 !important;">
        <!-- HERO ABOUT -->
        <section class="bg-primary text-white py-5 text-center bg-gradient-primary">
            <div class="container">
                <h1 class="mb-4">Une gestion des remboursements pensée pour les organisations</h1>
                <p class="lead">Rembourso réunit les membres, les justificatifs et les responsables dans un même espace de travail.</p>
            </div>
        </section>

        <!-- HISTOIRE -->
        <section class="py-5">
            <div class="container text-center">
                <h2 class="mb-4">Pourquoi ce site existe</h2>
                <p class="lead mb-3">Une demande de remboursement implique souvent plusieurs personnes : le membre qui avance les frais, la personne qui vérifie la demande et la trésorerie qui effectue le remboursement.</p>
                <p class="lead">Rembourso rassemble ce parcours dans un espace partagé : chacun dépose ses demandes et justificatifs, tandis que les caissiers et administrateurs disposent d'une vue dédiée pour traiter les demandes et conserver leur historique.</p>
            </div>
        </section>

        <!-- FONCTIONNALITES -->
        <section class="py-5 bg-light">
            <div class="container">
                        <h2 class="text-center mb-5">Une plateforme pour chaque rôle</h2>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Pour les membres</h3>
                                <p class="card-text">Déposez une demande, indiquez la dépense et joignez un ou plusieurs justificatifs.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Pour les caissiers</h3>
                                <p class="card-text">Consultez les demandes, vérifiez leurs informations et gérez le traitement des remboursements.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Pour les administrateurs</h3>
                                <p class="card-text">Gérez les membres, les invitations, les catégories et les paramètres de votre organisation.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <h3 class="card-title">Un suivi partagé</h3>
                                <p class="card-text">Retrouvez l'état des demandes et l'historique des remboursements dans l'espace de l'organisation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- VISION -->
        <section class="py-5 text-center">
            <div class="container">
                <h2 class="mb-4">Notre objectif</h2>
                <p class="lead">Rendre le parcours de remboursement simple, transparent et collaboratif, tout en laissant à chaque organisation la liberté de structurer ses catégories et ses membres.</p>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-5 bg-primary text-white text-center">
            <div class="container">
                <h2 class="mb-4">Prêt à simplifier la gestion de votre association ?</h2>
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