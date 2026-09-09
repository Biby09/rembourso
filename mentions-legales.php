<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php'; ?>

<!DOCTYPE html>
<html lang="fr">

<?php
$page_title = "Mentions légales - Rembourso";
include $_SERVER['DOCUMENT_ROOT'] . '/includes/head.php'; ?>

<body>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>

    <main>
        <section class="py-5">
            <div class="container">
                <h1 class="text-center mb-5">Mentions légales</h1>

                <div class="row justify-content-center">
                    <div class="col-lg-8">

                        <h2 class="h4 mt-4">Éditeur du site</h2>
                        <p>
                            Le site Rembourso est édité par Florian Lovis.<br>
                            Contact : <a href="mailto:webmaster@florianlovis.ch">webmaster@florianlovis.ch</a>
                        </p>

                        <h2 class="h4 mt-4">Hébergement</h2>
                        <p>
                            Le site est hébergé par un prestataire d'hébergement web tiers. Les coordonnées de
                            l'hébergeur peuvent être communiquées sur simple demande auprès de l'éditeur, à l'adresse
                            de contact ci-dessus.
                        </p>

                        <h2 class="h4 mt-4">Objet du site</h2>
                        <p>
                            Rembourso est une plateforme permettant à des organisations de gérer les demandes de
                            remboursement de frais de leurs membres (dépôt de justificatifs, validation, suivi et
                            traitement des remboursements).
                        </p>

                        <h2 class="h4 mt-4">Données personnelles</h2>
                        <p>
                            Dans le cadre de son fonctionnement, Rembourso collecte et conserve uniquement les
                            données nécessaires à la fourniture du service : informations de compte (nom, prénom,
                            adresse email), informations relatives aux organisations et à leurs membres, ainsi que
                            les demandes de remboursement et leurs justificatifs (factures, reçus).
                        </p>
                        <p>
                            <strong>Ces données ne sont ni vendues, ni louées, ni transmises à des tiers</strong> à
                            des fins commerciales ou publicitaires. Elles ne sont utilisées que pour permettre le
                            bon fonctionnement du service (authentification, gestion des organisations, traitement
                            des remboursements) et ne sont accessibles qu'aux personnes autorisées au sein de
                            l'organisation concernée (membres, caissiers, administrateurs), selon leur rôle.
                        </p>
                        <p>
                            Les données sont conservées pendant toute la durée d'utilisation du service. Vous pouvez
                            à tout moment demander la suppression de votre compte et des données associées depuis
                            votre espace personnel, ou en contactant l'éditeur à l'adresse ci-dessus.
                        </p>
                        <p>
                            Conformément à la réglementation applicable en matière de protection des données
                            personnelles, vous disposez d'un droit d'accès, de rectification, de suppression et
                            d'opposition concernant vos données. Pour exercer ces droits, contactez l'éditeur à
                            l'adresse indiquée ci-dessus.
                        </p>

                        <h2 class="h4 mt-4">Cookies</h2>
                        <p>
                            Le site utilise uniquement des cookies strictement nécessaires à son fonctionnement
                            (notamment la gestion de la session de connexion). Aucun cookie de suivi publicitaire
                            ou de mesure d'audience tiers n'est utilisé.
                        </p>

                        <h2 class="h4 mt-4">Propriété intellectuelle</h2>
                        <p>
                            L'ensemble des contenus présents sur le site Rembourso (textes, logos, code source) est
                            protégé par le droit de la propriété intellectuelle. Toute reproduction non autorisée
                            est interdite.
                        </p>

                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
</body>

</html>
