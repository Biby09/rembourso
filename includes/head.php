<?php
$site_name = 'Rembourso';
$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$page_title = $page_title ?? 'Rembourso | Gestion des remboursements associatifs';
$page_description = $page_description ?? 'Rembourso simplifie la gestion des demandes de remboursement pour les associations et organisations.';
$canonical_url = $canonical_url ?? 'https://rembourso.florianlovis.ch' . $request_path;
$private_path = preg_match('#^/(api|dashboard|invitation|logout|login|register)(/|$)|^/token\.php$#', $request_path) === 1;
$robots_directive = $robots_directive ?? ($private_path ? 'noindex, nofollow' : 'index, follow');
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="<?= htmlspecialchars($robots_directive, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') ?>">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="<?= htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="https://rembourso.florianlovis.ch/media/logo.svg">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="stylesheet" href="/stylesheets/global.min.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/media/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/media/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/media/favicon/favicon-16x16.png">
    <link rel="manifest" href="/media/favicon/site.webmanifest">
</head>