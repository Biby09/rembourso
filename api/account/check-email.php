<?php
// Vérifie qu'une adresse e-mail n'est pas déjà utilisée (étape 1 de l'inscription, scripts/pages/register.js).
// Accessible sans être connecté, mais protégé par CSRF et limité par adresse IP (énumération des comptes).
// JSON : email

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/api.php';

api_require_method('POST');
$input = api_json_input();

$email = filter_var(trim((string) ($input['email'] ?? '')), FILTER_VALIDATE_EMAIL);
if ($email === false) {
    json_error('Email invalide.');
}

rate_limit_consume('check_email_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 20, 900);

json_success(['available' => $userManager->isEmailAvailable($email)]);
