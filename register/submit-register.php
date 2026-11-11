<?php
// Inscription (formulaire en 3 étapes, scripts/pages/register.js)
// POST : first_name, last_name, email, password, confirm_password, address, postal_code, city, country, iban,
//        same-address (case cochée) ou refund_first_name, refund_last_name, refund_address, refund_postal_code, refund_city, refund_country

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

header('Content-Type: application/json; charset=utf-8');

/** Réponse d'erreur JSON (les messages s'affichent dans le formulaire) */
function register_error(string $message, int $status = 400): never
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    register_error('Méthode non autorisée', 405);
}

verify_csrf_request(null, true);

$data = [];
foreach (['first_name', 'last_name', 'address', 'postal_code', 'city', 'country', 'iban'] as $field) {
    $data[$field] = trim((string) ($_POST[$field] ?? ''));
}

// Bénéficiaire des remboursements : la personne elle-même (case « même adresse ») ou une autre personne
foreach (['first_name', 'last_name', 'address', 'postal_code', 'city', 'country'] as $field) {
    $data['refund_' . $field] = isset($_POST['same-address']) ? $data[$field] : trim((string) ($_POST['refund_' . $field] ?? ''));
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = (string) ($_POST['password'] ?? '');

if (!$email || $password === '' || in_array('', $data, true)) {
    register_error('Tous les champs sont requis');
}
if ($password !== ($_POST['confirm_password'] ?? null)) {
    register_error('Les mots de passe ne correspondent pas');
}
if (!$userManager->isEmailAvailable($email)) {
    register_error('Cet email est déjà utilisé');
}

// IBAN stocké au format machine (sans espaces)
$data['iban'] = iban_to_machine_format($data['iban']);
if (!verify_iban($data['iban'])) {
    register_error('IBAN invalide');
}
$data['email'] = $email;

try {
    $user = $userManager->createUser($data, $password);
} catch (Exception $e) {
    log_server_exception($e, "Erreur lors de l'inscription");
    register_error("Une erreur est survenue lors de l'inscription. Veuillez réessayer plus tard.", 500);
}

// Connexion directe après l'inscription
session_regenerate_id(true);
$_SESSION['user_id'] = $user->getId();
echo json_encode(['success' => true, 'message' => 'Inscription réussie', 'redirect' => '/dashboard']);
