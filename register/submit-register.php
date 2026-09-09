<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/objects.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

verify_csrf_request(null, true);

//Controler que tous les champs sont présents

if (!isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['password'], $_POST['confirm_password'], $_POST['address'], $_POST['postal_code'], $_POST['city'], $_POST['country'], $_POST['iban'], $_POST['phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}

if (isset($_POST['same-address'])) {
    $_POST['refund_first_name'] = $_POST['first_name'];
    $_POST['refund_last_name'] = $_POST['last_name'];
    $_POST['refund_address'] = $_POST['address'];
    $_POST['refund_postal_code'] = $_POST['postal_code'];
    $_POST['refund_city'] = $_POST['city'];
    $_POST['refund_country'] = $_POST['country'];
} else {
    if (!isset($_POST['refund_first_name'], $_POST['refund_last_name'], $_POST['refund_address'], $_POST['refund_postal_code'], $_POST['refund_city'], $_POST['refund_country'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
        exit;
    }
}

// Récupérer les données du formulaire

$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$phone = trim($_POST['phone']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$address = trim($_POST['address']);
$postal_code = trim($_POST['postal_code']);
$city = trim($_POST['city']);
$country = trim($_POST['country']);
$iban = trim($_POST['iban']);
$refund_first_name = trim($_POST['refund_first_name']);
$refund_last_name = trim($_POST['refund_last_name']);
$refund_address = trim($_POST['refund_address']);
$refund_postal_code = trim($_POST['refund_postal_code']);
$refund_city = trim($_POST['refund_city']);
$refund_country = trim($_POST['refund_country']);

// Contrôler que les champs ne sont pas vides

if (empty($first_name) || empty($last_name) || !$email || empty($password) || empty($confirm_password) || empty($address) || empty($postal_code) || empty($city) || empty($country) || empty($iban) || empty($phone) || empty($refund_first_name) || empty($refund_last_name) || empty($refund_address) || empty($refund_postal_code) || empty($refund_city) || empty($refund_country)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}

// Contrôler que les mots de passe correspondent
if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
    exit;
}

// Contrôler que l'email n'est pas déjà utilisé
if (!User::isEmailAvailable($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
    exit;
}

// Créer l'utilisateur

$user = User::create($last_name, $first_name, $address, $postal_code, $city, $country, $iban, $email, $phone, $password, $refund_last_name, $refund_first_name, $refund_address, $refund_postal_code, $refund_city, $refund_country);

if ($user) {
    // Connecter l'utilisateur
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user->id;
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Inscription réussie', 'redirect' => '/dashboard']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer plus tard.']);
    exit;
}
