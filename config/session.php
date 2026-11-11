<?php
// Durée de vie d'une session : 12 heures
const SESSION_LIFETIME = 3600 * 12;

require_once __DIR__ . '/security-headers.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

function start_secure_session(): void
{
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => request_is_https(),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

start_secure_session();

// Session expirée : on repart sur une session vierge
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
    session_unset();
    session_destroy();
    start_secure_session();
}

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/rate-limit.php';
require_once __DIR__ . '/error-handling.php';

enforce_canonical_url();
apply_security_headers();

// Mise à jour du timestamp d'activité
$_SESSION['last_activity'] = time();
