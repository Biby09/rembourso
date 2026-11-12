<?php
/*
    Footer de l'application (pages avec includes/app-header.php) : une ligne discrète, à la place du grand footer
    du site public (includes/footer.php). Garde l'accès au contact et aux mentions légales depuis toutes les pages.
    Grand écran seulement : sur mobile et tablette, ces liens sont dans le panneau « Plus » de la barre du bas
    (includes/app-bottom-nav.php).
    APP_VERSION : config/init.php
*/
?>
<!-- Pas de classe mt-* : margin-top: auto (global.scss, règle « footer ») pousse le footer en bas des pages courtes -->
<footer class="app-footer border-top bg-white py-3 d-none d-xl-block">
    <div class="container">
        <!-- Liste « · » : les éléments passent à la ligne proprement sur un petit écran -->
        <ul class="list-inline small text-muted text-center mb-0">
            <li class="list-inline-item">© <?= date('Y') ?> Rembourso</li>
            <li class="list-inline-item">Version <?= e(APP_VERSION) ?></li>
            <li class="list-inline-item"><a href="/contact/" class="link-secondary">Contact</a></li>
            <li class="list-inline-item"><a href="/mentions-legales.php" class="link-secondary">Mentions légales</a></li>
            <li class="list-inline-item"><a href="https://github.com/Biby09/rembourso" class="link-secondary" target="_blank" rel="noopener noreferrer">GitHub</a></li>
        </ul>
    </div>
</footer>
