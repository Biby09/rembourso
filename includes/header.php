<header class="border-bottom border-primary border-5 fixed-top bg-white">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a href="/"><img src="/media/logo.svg" alt="Rembourso" height="20"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about.php">A propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact">Contact</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="/dashboard">Mon espace</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout">Déconnexion</a>
                        </li>
                    </ul>
                <?php else: ?>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="/login">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register">Créer un compte</a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<script src="/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>