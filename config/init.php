<?php
/**
 * Amorçage commun à toutes les pages et à tous les endpoints :
 * session (+ en-têtes de sécurité, CSRF, limitation de débit, journal des erreurs),
 * connexion à la base ($mysqlClient), e-mails, quittances et $userManager.
 *
 * Pages réservées aux utilisateurs connectés : inclure ensuite config/auth.php.
 * Endpoints JSON : config/api.php (public) ou config/api-auth.php (connecté).
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/connect-db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/mail.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/receipts.php';

// Version de l'application, affichée dans les footers (includes/footer.php et includes/app-footer.php)
const APP_VERSION = '2.0.0';

/**
 * Montant au format suisse : « 1 234,50 CHF »
 */
function format_amount(string|float $amount, string $currency): string
{
    return number_format((float) $amount, 2, ',', ' ') . ' ' . $currency;
}

/**
 * Date de la base (« 2026-11-04 » ou « 2026-11-04 15:30:00 ») au format « 04/11/2026 »
 */
function format_date(?string $date): string
{
    return $date ? date('d/m/Y', strtotime($date)) : '';
}

/**
 * Date reçue d'un formulaire (<input type="date"> : AAAA-MM-JJ) : renvoyée telle quelle si elle existe,
 * null sinon (« 2026-02-30 », texte vide…).
 */
function parse_date(string $text): ?string
{
    // « ! » : l'heure vaut 0 ; la date relue doit être identique (PHP changerait le 30 février en 2 mars)
    $date = DateTime::createFromFormat('!Y-m-d', $text);
    return $date && $date->format('Y-m-d') === $text ? $text : null;
}

/** Échappement HTML pour l'affichage d'une valeur dans une page */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user/userManager.php';
$userManager = new UserManager($mysqlClient);
