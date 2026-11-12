# Rembourso

Rembourso est une plateforme web permettant à des organisations (associations, clubs, sociétés locales, etc.) de gérer simplement les demandes de remboursement de frais de leurs membres : dépôt des demandes avec justificatifs, catégorisation, traitement par lots et suivi des remboursements.

🔗 Site de production : [rembourso.florianlovis.ch](https://rembourso.florianlovis.ch)

## Fonctionnalités

- Création et gestion d'organisations, invitation et gestion des membres et de leurs rôles
- Dépôt de demandes de remboursement avec pièces justificatives (plusieurs quittances par demande)
- Saisie de demandes par un caissier ou un administrateur pour un autre membre ou pour une personne sans compte ; la personne peut ensuite créer son compte via un lien envoyé par e-mail (ou rattacher ses demandes à son compte existant)
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

- PHP 8.1+ avec les extensions PDO MySQL et Imagick (Imagick ne sert plus que de solution de secours : les PDF sont normalement convertis dans le navigateur)
- Imagick compatible avec la version de PHP utilisée (par ex. `pecl install imagick` ou via votre package manager, en fonction de votre version PHP)
- MySQL/MariaDB
- [Composer](https://getcomposer.org/)
- Node.js + npm
- [Dart Sass](https://sass-lang.com/install) (CLI `sass`)

## Installation

```bash
git clone <url-du-repo>
cd rembourso
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
| `APP_URL`     | Adresse du site, pour les liens des e-mails (ex. `https://dev.rembourso.florianlovis.ch` sur le site de développement ; par défaut l'adresse de production) |
| `APP_ENV`     | `production` sur le site de production uniquement (redirection HTTPS, HSTS) |

### E-mails

Les e-mails partent en SMTP authentifié (PHPMailer, serveur `mail.infomaniak.com`, port 465) dès qu'un identifiant
et un mot de passe sont configurés ; sinon, par la fonction `mail()` de PHP (ex. en local).

| Variable         | Description |
|------------------|-------------|
| `MAIL_USERNAME`  | Boîte mail Infomaniak utilisée pour se connecter (adresse complète) |
| `MAIL_PASSWORD`  | Son mot de passe |
| `MAIL_FROM`      | Expéditeur affiché : cette boîte ou l'un de ses alias (par défaut `noreply@florianlovis.ch`) |
| `MAIL_FROM_NAME` | Nom affiché (par défaut « Rembourso ») |
| `MAIL_HOST`, `MAIL_PORT` | Serveur SMTP (par défaut `mail.infomaniak.com`, 465) |

En cas d'échec, la raison est écrite dans le journal d'erreurs PHP (`sendMail : échec…`).
Test depuis un terminal (le mot de passe est saisi sans s'afficher) : voir `tools/test-mail.php`.

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
account-claim/  Page du lien de création de compte envoyé à une personne sans compte
api/            Endpoints JSON appelés en AJAX, rangés par domaine (account/, organisation/, repayment/, ...)
class/          Classes métier : une entité et un Manager par dossier (voir ci-dessous)
config/         Amorçage et fonctions communes
  init.php          Session, base de données, e-mails, quittances, $userManager (toutes les pages)
  auth.php          Pages réservées aux utilisateurs connectés ($user, $organisationManager)
  organisation-page.php  Pages d'une organisation ($organisation, $isCashier, $isAdmin, rôle requis)
  api.php / api-auth.php Endpoints JSON publics / réservés aux utilisateurs connectés
  repayment-api.php, accountless.php  Validation commune aux endpoints des demandes et des personnes sans compte
dashboard/      Application : tableau de bord, Mon compte et pages d'une organisation
  organisation/     Nouvelle demande (index), requests/, cashier/, history/, members/, settings/
                    init-orga.php + init-orga/page1..4 : assistant de configuration après la création (devise, catégories, membres)
includes/       Partiels PHP : head, header/footer du site public, app-header/app-footer de l'application,
                page-heading, réglages partagés entre l'assistant et les pages (currency-, category-, invitation-setup.php)
                et modals/ (un fichier par fenêtre modale)
                repayment-item.php + pagination.php : liste des demandes (tableau de bord, « Mes demandes »)
                mail/layout.php : gabarit HTML des e-mails (config/mail.php envoie une version HTML et une version texte)
login/          Authentification, mot de passe oublié
migration/      Scripts SQL à exécuter en production lors des déploiements (préfixés par la date)
register/       Inscription
scripts/        JavaScript côté client (modules ES natifs, sans build)
stylesheets/    Sources SCSS et CSS compilé
uploads/        Fichiers uploadés (justificatifs)
vendor/         Dépendances PHP (Composer)
```

### Classes PHP

Chaque dossier de `class/` contient une **entité** (propriétés privées, `hydrate()` à partir d'une ligne de la base, getters)
et un **Manager** qui lit et enregistre ces entités. Un Manager reçoit le PDO et son périmètre :

| Manager | Périmètre | Rôle |
|---|---|---|
| `UserManager` | — | Comptes et personnes sans compte |
| `OrganisationManager` | utilisateur connecté | Organisations dont il est membre, rôles, membres, réglages |
| `CategoryManager` | une organisation | Catégories et sous-catégories |
| `RepaymentRequestManager` | une organisation + utilisateur | Demandes de remboursement et quittances |
| `RepaymentBatchManager` | une organisation | Lots de remboursement (historique, validation) |
| `InvitationManager` | utilisateur connecté | Invitations à rejoindre une organisation |
| `PasswordResetManager`, `AccountClaimManager` | — | Liens envoyés par e-mail (via `TokenManager`) |

Un Manager limité à une organisation ne renvoie jamais un objet d'une autre organisation (exception à la place).
Les transactions passent par le trait `Transactional` (`class/database/transactional.php`).

### JavaScript

Le JavaScript est écrit en modules ES (`<script type="module">`), chargés directement par le navigateur : aucune étape de build n'est nécessaire. Aucun script n'est écrit dans les pages PHP (la CSP n'autorise plus les scripts inline).

```
scripts/
  core/           Briques de base
    api.js          Tous les appels aux endpoints de /api (endpoint('domaine/action'))
    http.js         Requêtes fetch (ajout automatique de l'en-tête CSRF)
    dom.js          Helpers DOM et tag `html` qui échappe les valeurs interpolées
    format.js       Formatage des montants et des dates
    ui.js           Alertes, boutons en chargement, modals Bootstrap
  components/     Composants réutilisés par plusieurs pages
    ajax-form.js        Envoi de formulaires en AJAX (aussi en déclaratif via data-ajax-form)
    country-select.js   Listes de pays
    subcategory-select.js  Sous-catégories dépendant de la catégorie
    receipt-gallery.js  Aperçu et galerie des quittances
    receipt-files.js    Validation des fichiers de quittance
    receipt-compressor.js  Photos réduites et PDF convertis en images (pdf.js) dans le navigateur, avant l'envoi
    repayment-badges.js, repayment-edit-dialog.js  Badge « Urgent » et modification d'une demande
    person-fields.js    Champs d'une personne sans compte
    pagination.js       Pagination
    invitation-response.js  Réponse à une invitation
  pages/          Un point d'entrée par page
    ajax-forms.js   Connexion, mot de passe oublié / réinitialisation, contact, lien de création de compte
    register.js, account.js, dashboard.js, invitation.js
    organisation/   Une page = un fichier : submit.js, requests.js, cashier.js, history.js, members.js, settings.js, init-orga.js
                    (+ modules accountless.js, categories.js, currency.js, member-management.js et context.js)
```

pdf.js (`pdfjs-dist`, version figée dans `package.json`) est servi depuis `/node_modules` comme Bootstrap, à cause de la CSP : `node_modules` doit donc être déployé avec le site. Le `.htaccess` de la racine déclare le type des fichiers `.mjs`.

Les API renvoient des données brutes (non échappées) : l'échappement HTML se fait à l'affichage, via le tag `html` de `scripts/core/dom.js`.

## Licence

Projet privé — tous droits réservés.