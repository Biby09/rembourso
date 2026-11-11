<?php

function request_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/**
 * En production, redirige (301) les variantes d'URL vers l'URL canonique :
 * http -> https et /index.php -> /, pour éviter le contenu dupliqué côté moteurs de recherche.
 */
function enforce_canonical_url(): void
{
    if (getenv('APP_ENV') !== 'production' || PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method !== 'GET' && $method !== 'HEAD') {
        return;
    }

    $isHttps = request_is_https()
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $query = parse_url($uri, PHP_URL_QUERY);
    $canonicalPath = preg_replace('#/index\.php$#', '/', $path);

    if ($isHttps && $canonicalPath === $path) {
        return;
    }

    $target = 'https://rembourso.florianlovis.ch' . $canonicalPath . ($query !== null && $query !== '' ? '?' . $query : '');
    header('Location: ' . $target, true, 301);
    exit;
}

/**
 * Retourne un chemin de redirection interne sûr (évite les redirections ouvertes
 * vers un autre domaine via ?redirect=https://... ou //domaine).
 */
function safe_redirect_path(?string $path, string $default = '/dashboard'): string
{
    if (!is_string($path) || $path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '\\')) {
        return $default;
    }

    return $path;
}

function apply_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), geolocation=(), microphone=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; media-src 'self'");

    if (getenv('APP_ENV') === 'production' && request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}