<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

verify_csrf_request(null, true);

$name = trim((string) ($_POST['name'] ?? ''));
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
// Pas de retour à la ligne dans le sujet (utilisé dans les en-têtes du mail)
$subject = trim(str_replace(["\r", "\n"], ' ', (string) ($_POST['subject'] ?? '')));
$message = trim((string) ($_POST['message'] ?? ''));

if (!$email || $subject === '' || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs avec une adresse e-mail valide.']);
    exit;
}

rate_limit_consume('contact_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 5, 3600);

$message_content = "Nom : $name\nEmail : $email\n\n Objet: $subject\nMessage :\n$message";

$success = sendMail('webmaster@florianlovis.ch', $subject, $message_content, "Reply-To: $email\r\n");

echo json_encode(['success' => (bool) $success]);