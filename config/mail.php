<?php
/**
 * Envoi des e-mails : chaque e-mail contient une version HTML (gabarit includes/mail/layout.php)
 * et une version texte (messageries qui n'affichent pas le HTML).
 * Chaque e-mail envoyé est enregistré dans la table emails (avec le jeton du lien qu'il contient,
 * pour connaître la date du dernier envoi d'une invitation).
 *
 * Deux façons d'envoyer, choisies automatiquement :
 * - SMTP authentifié avec PHPMailer (recommandé par Infomaniak : meilleure délivrabilité) si les variables
 *   d'environnement MAIL_USERNAME et MAIL_PASSWORD sont définies (Manager Infomaniak) ;
 * - sinon la fonction mail() de PHP (ex. en local).
 */

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

const APP_PRODUCTION_URL = 'https://rembourso.florianlovis.ch';

/**
 * Réglages des e-mails, lus dans les variables d'environnement (définies dans le Manager Infomaniak) :
 * MAIL_USERNAME et MAIL_PASSWORD (boîte mail utilisée pour se connecter), MAIL_FROM (expéditeur affiché :
 * cette boîte ou l'un de ses alias), MAIL_FROM_NAME, MAIL_HOST et MAIL_PORT (valeurs par défaut : Infomaniak).
 * Le mot de passe n'est ainsi jamais écrit dans le code ni publié sur GitHub.
 *
 * @return array{host: string, port: int, username: string, password: string, from: string, from_name: string}
 */
function mail_settings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }

    $settings = [
        'host' => getenv('MAIL_HOST') ?: 'mail.infomaniak.com',
        'port' => (int) (getenv('MAIL_PORT') ?: 465),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'from' => getenv('MAIL_FROM') ?: 'noreply@florianlovis.ch',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Rembourso',
    ];

    if (filter_var($settings['from'], FILTER_VALIDATE_EMAIL) === false) {
        $settings['from'] = 'noreply@florianlovis.ch';
    }
    // Pas de retour à la ligne dans un en-tête (sinon on pourrait y injecter d'autres en-têtes)
    $settings['from_name'] = str_replace(["\r", "\n", '"'], '', (string) $settings['from_name']);
    $settings['port'] = (int) $settings['port'];

    return $settings;
}

/**
 * Adresse complète d'une page du site, pour les liens des e-mails : app_url('/dashboard') → https://rembourso.florianlovis.ch/dashboard
 *
 * Adresse de base, dans l'ordre :
 * 1. variable d'environnement APP_URL (définie par site dans le Manager, ex. https://dev.rembourso.florianlovis.ch
 *    pour le site de développement : ses e-mails mènent alors au dev et non à la production) ;
 * 2. en local (localhost) : http://localhost:<port> ;
 * 3. sinon : l'adresse de production.
 * L'en-tête Host de la requête n'est jamais utilisé (il est choisi par le client : un lien de réinitialisation
 * pourrait sinon pointer vers un autre site).
 */
function app_url(string $path = '/'): string
{
    $configured = rtrim((string) getenv('APP_URL'), '/');
    $serverName = $_SERVER['SERVER_NAME'] ?? '';

    if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://#', $configured)) {
        $base = $configured;
    } elseif (in_array($serverName, ['localhost', '127.0.0.1'], true)) {
        $port = (int) ($_SERVER['SERVER_PORT'] ?? 80);
        $base = 'http://' . $serverName . (in_array($port, [80, 443], true) ? '' : ':' . $port);
    } else {
        $base = APP_PRODUCTION_URL;
    }
    return $base . '/' . ltrim($path, '/');
}

/**
 * Lien envoyé par e-mail pour un jeton (token.php redirige vers la bonne page selon son type).
 */
function token_link(string $token): string
{
    return app_url('/token.php?token=' . urlencode($token));
}

/**
 * Envoie un e-mail. La formule de politesse est ajoutée automatiquement.
 *
 * $message       : texte du message ; une ligne vide sépare deux paragraphes.
 * $replyTo       : adresse de réponse (ex. la personne qui écrit via le formulaire de contact) ; par défaut l'expéditeur.
 * $token         : jeton du lien contenu dans l'e-mail (en clair) ;
 * $loggedMessage : texte enregistré dans la table emails à la place du message.
 * $details       : [libellé => valeur], affichés en tableau (ex. montant, bénéficiaire).
 * $action        : ['label' => 'Créer mon compte', 'url' => …] : bouton principal de l'e-mail.
 *
 * Renvoie l'identifiant de l'e-mail enregistré, ou false si l'envoi a échoué (raison écrite dans le journal PHP).
 */
function sendMail(string $to, string $subject, string $message, ?string $replyTo = null, ?string $token = null, ?string $loggedMessage = null, array $details = [], ?array $action = null): string|false
{
    global $mysqlClient;

    $html = render_mail_html($subject, $message, $details, $action);
    $text = render_mail_text($message, $details, $action);
    $replyTo = $replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false ? $replyTo : null;

    $settings = mail_settings();
    $error = $settings['username'] !== '' && $settings['password'] !== ''
        ? send_mail_smtp($settings, $to, $subject, $html, $text, $replyTo)
        : send_mail_function($settings, $to, $subject, $html, $text, $replyTo);

    if ($error !== null) {
        // Visible dans le journal d'erreurs PHP de l'hébergement (sinon l'échec passe inaperçu)
        error_log("sendMail : échec de l'envoi à $to (« $subject ») : $error");
        return false;
    }

    // Le jeton est stocké en clair (invitations) ou haché (autres liens) : on cherche les deux
    $stmt = $mysqlClient->prepare('INSERT INTO emails (ema_to, ema_subject, ema_content, idx_author, idx_token)
        VALUES (:to, :subject, :message, :author, (SELECT id_token FROM tokens WHERE tok_token = :token OR tok_token = :token_hash LIMIT 1))');
    $stmt->execute([
        'to' => $to,
        'subject' => $subject,
        'message' => $loggedMessage ?? render_mail_text($message, $details, null),
        'author' => $_SESSION['user_id'] ?? null,
        'token' => $token,
        'token_hash' => $token ? hash('sha256', $token) : null,
    ]);

    return $mysqlClient->lastInsertId();
}

/**
 * Envoi SMTP authentifié (PHPMailer). Renvoie null si l'e-mail est parti, sinon le message d'erreur.
 *
 * L'expéditeur affiché (From) peut être un alias de la boîte utilisée pour se connecter ; l'adresse de retour
 * (enveloppe) reste ce compte, comme l'exige Infomaniak (sinon erreur « Sender mismatch »).
 * La connexion est gardée ouverte pour les e-mails suivants de la même requête (ex. plusieurs caissiers).
 */
function send_mail_smtp(array $settings, string $to, string $subject, string $html, string $text, ?string $replyTo): ?string
{
    static $mailer = null;

    try {
        if ($mailer === null) {
            $mailer = new PHPMailer(true); // true : les erreurs lèvent une exception
            $mailer->isSMTP();
            $mailer->Host = $settings['host'];
            $mailer->Port = $settings['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $settings['username'];
            $mailer->Password = $settings['password'];
            // Port 465 : connexion chiffrée dès le début (SMTPS) ; 587 : chiffrement négocié (STARTTLS)
            $mailer->SMTPSecure = $settings['port'] === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->SMTPKeepAlive = true;
            $mailer->Timeout = 15;
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Encoding = PHPMailer::ENCODING_BASE64;
            register_shutdown_function(fn() => $mailer->smtpClose());
        }

        $mailer->clearAllRecipients();
        $mailer->clearReplyTos();
        $mailer->setFrom($settings['from'], $settings['from_name'], false);
        $mailer->Sender = $settings['username'];
        $mailer->addAddress($to);
        $mailer->addReplyTo($replyTo ?? $settings['from']);
        $mailer->Subject = $subject;
        $mailer->isHTML(true);
        $mailer->Body = $html;
        $mailer->AltBody = $text;
        $mailer->send();
        return null;
    } catch (MailerException $exception) {
        $error = $mailer?->ErrorInfo ?: $exception->getMessage();
        $mailer = null; // connexion recréée au prochain envoi
        return 'SMTP : ' . $error;
    }
}

/**
 * Envoi avec la fonction mail() de PHP (sans authentification). Renvoie null si l'e-mail est parti, sinon l'erreur.
 */
function send_mail_function(array $settings, string $to, string $subject, string $html, string $text, ?string $replyTo): ?string
{
    $boundary = 'rembourso-' . bin2hex(random_bytes(12));
    // base64 : les accents et les longues lignes passent sans risque dans tous les serveurs de messagerie
    $body = "--$boundary\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text))
        . "--$boundary\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . "--$boundary--\r\n";

    $headers = implode("\r\n", [
        'From: =?UTF-8?B?' . base64_encode($settings['from_name']) . '?= <' . $settings['from'] . '>',
        'Reply-To: ' . ($replyTo ?? $settings['from']),
        'MIME-Version: 1.0',
        "Content-Type: multipart/alternative; boundary=\"$boundary\"",
    ]);
    // Sujet encodé (=?UTF-8?B?…?=) : les accents s'affichent correctement dans les messageries
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    // -f : adresse de retour (enveloppe) = l'expéditeur. Certains hébergements refusent ce paramètre : nouvel essai
    // sans lui (@ : pas d'avertissement pour le 1er essai, un échec définitif est journalisé par sendMail()).
    $sent = @mail($to, $encodedSubject, $body, $headers, '-f' . $settings['from'])
        || mail($to, $encodedSubject, $body, $headers);

    return $sent ? null : 'mail() : ' . (error_get_last()['message'] ?? 'la fonction a renvoyé false');
}

/**
 * Version texte de l'e-mail.
 */
function render_mail_text(string $message, array $details, ?array $action): string
{
    $text = "Bonjour,\n\n" . trim($message);
    if ($details) {
        $text .= "\n";
        foreach ($details as $label => $value) {
            $text .= "\n" . $label . ' : ' . $value;
        }
    }
    if ($action) {
        $text .= "\n\n" . $action['label'] . " :\n" . $action['url'];
    }
    return $text . "\n\nCordialement,\nL'équipe Rembourso";
}

/**
 * Version HTML de l'e-mail (gabarit includes/mail/layout.php).
 * Le message est échappé ; ses adresses http(s) deviennent des liens cliquables.
 */
function render_mail_html(string $title, string $message, array $details, ?array $action): string
{
    $mailTitle = $title;
    $mailDetails = $details;
    $mailAction = $action;
    $mailParagraphs = [];
    foreach (preg_split('/\R{2,}/', trim($message)) as $paragraph) {
        $html = nl2br(e($paragraph), false);
        $html = preg_replace('#https?://[^\s<]+#', '<a href="$0" style="color: #367447;">$0</a>', $html);
        $mailParagraphs[] = $html;
    }

    // ob_start / ob_get_clean : le HTML du gabarit est récupéré dans une variable au lieu d'être affiché
    ob_start();
    include $_SERVER['DOCUMENT_ROOT'] . '/includes/mail/layout.php';
    return ob_get_clean();
}
