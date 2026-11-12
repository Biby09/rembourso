<?php
/**
 * Test de l'envoi des e-mails, en ligne de commande (sur ta machine) :
 *
 *   read -s MAIL_PASSWORD && export MAIL_PASSWORD        ← tape le mot de passe (rien ne s'affiche), puis Entrée
 *   MAIL_USERNAME=boite@florianlovis.ch MAIL_FROM=noreply@florianlovis.ch php tools/test-mail.php ton.adresse@exemple.ch
 *   unset MAIL_PASSWORD                                  ← oublie le mot de passe une fois le test fini
 *
 * Envoie un e-mail de test avec le vrai sendMail() (SMTP si MAIL_USERNAME et MAIL_PASSWORD sont définis, sinon mail())
 * et affiche le résultat ou l'erreur. Utilise la base locale (l'e-mail envoyé y est enregistré, comme sur le site).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$to = $argv[1] ?? '';
if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Usage : php tools/test-mail.php adresse@destinataire.ch\n");
    exit(1);
}

// Contexte « local » : base de données locale, liens en http://localhost
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = 8000;
require $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$settings = mail_settings();
$smtp = $settings['username'] !== '' && $settings['password'] !== '';
echo 'Méthode      : ' . ($smtp ? "SMTP {$settings['host']}:{$settings['port']} (connexion : {$settings['username']})" : 'fonction mail() (MAIL_USERNAME / MAIL_PASSWORD absents)') . "\n";
echo "Expéditeur   : {$settings['from_name']} <{$settings['from']}>\n";
echo "Destinataire : $to\n\n";

// Les erreurs de sendMail() sont écrites dans le journal PHP : ici, on les affiche dans le terminal
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

$result = sendMail($to, 'Test d\'envoi – Rembourso', "Ceci est un e-mail de test envoyé depuis tools/test-mail.php.\n\nSi vous le recevez, l'envoi des e-mails fonctionne.",
    details: ['Méthode' => $smtp ? 'SMTP authentifié' : 'mail()', 'Expéditeur' => $settings['from']],
    action: ['label' => 'Ouvrir Rembourso', 'url' => APP_PRODUCTION_URL]);

echo $result !== false ? "✓ E-mail envoyé. Vérifie ta boîte de réception (et les spams).\n" : "✗ Échec : voir le message ci-dessus.\n";
exit($result !== false ? 0 : 1);
