# Rembourso

Rembourso est une plateforme web permettant à des organisations (associations, clubs, sociétés locales, etc.) de gérer simplement les demandes de remboursement de frais de leurs membres : dépôt des demandes avec justificatifs, catégorisation, traitement par lots et suivi des remboursements.

🔗 Site de production : [rembourso.florianlovis.ch](https://rembourso.florianlovis.ch)

## Fonctionnalités

- Création et gestion d'organisations, invitation et gestion des membres et de leurs rôles
- Dépôt de demandes de remboursement avec pièces justificatives (plusieurs quittances par demande)
- Catégories et sous-catégories de dépenses configurables par organisation
- Traitement des demandes par lots avec génération de confirmations PDF (via Dompdf/FPDF)
- Génération de QR-factures suisses (Swiss QR-Bill)
- Tableau de bord utilisateur et espace de gestion par organisation

## Stack technique

- **Backend** : PHP (PDO / MySQL)
- **Frontend** : Bootstrap 5 (SCSS personnalisé), JavaScript
- **Build CSS** : Dart Sass + PostCSS/PurgeCSS
- **Dépendances PHP** : Composer (Dompdf, FPDF/FPDI, Swiss QR-Bill, ...)

## Prérequis

- PHP 8.1+ avec les extensions PDO MySQL et Imagick
- Imagick compatible avec la version de PHP utilisée (par ex. `pecl install imagick` ou via votre package manager, en fonction de votre version PHP)
- MySQL/MariaDB
- [Composer](https://getcomposer.org/)
- Node.js + npm
- [Dart Sass](https://sass-lang.com/install) (CLI `sass`)

## Installation

```bash
git clone <url-du-repo>
cd rembourso_v1-0-0
composer install
npm install
```

### Base de données

En local, la connexion utilise des identifiants par défaut (voir `config/connect-db.php`) pointant vers une base `db_rembourso` sur `localhost` (utilisateur `root` / mot de passe `root`). Adaptez ces valeurs à votre environnement si besoin.

En production, la configuration passe par les variables d'environnement suivantes :

| Variable      | Description                  |
|---------------|-------------------------------|
| `DB_HOST`     | Hôte de la base de données     |
| `DB_PORT`     | Port de la base de données     |
| `DB_NAME`     | Nom de la base de données      |
| `DB_USER`     | Utilisateur de la base         |
| `DB_PASSWORD` | Mot de passe de la base        |

### Compilation des styles

Les feuilles de style sont écrites en SCSS (`stylesheets/global.scss`) puis compilées en CSS avec Sass, et minifiées/purgées avec PostCSS :

```bash
sass stylesheets/global.scss:stylesheets/global.css --no-source-map
npx postcss stylesheets/global.css -o stylesheets/global.min.css
```

Pour recompiler automatiquement à chaque modification :

```bash
sass --watch stylesheets/global.scss:stylesheets/global.css
```

## Structure du projet

```
api/            Endpoints appelés en AJAX (gestion des demandes, membres, catégories, ...)
config/         Configuration (connexion BDD, session, sécurité, CSRF, headers, ...)
dashboard/      Tableau de bord utilisateur et espace organisation
includes/       Partiels PHP communs (head, header, footer)
login/          Authentification, mot de passe oublié
register/       Inscription
scripts/        JavaScript côté client
stylesheets/    Sources SCSS et CSS compilé
uploads/        Fichiers uploadés (justificatifs)
vendor/         Dépendances PHP (Composer)
```

## Licence

Projet privé — tous droits réservés.