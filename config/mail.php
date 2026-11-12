<?php
/**
 * Envoi des e-mails : chaque e-mail contient une version HTML (gabarit includes/mail/layout.php)
 * et une version texte (messageries qui n'affichent pas le HTML).
 * Chaque e-mail envoyé est enregistré dans la table emails (avec le jeton du lien qu'il contient,
 * pour connaître la date du dernier envoi d'une invitation).
 */

const APP_PRODUCTION_URL = 'https://rembourso.florianlovis.ch';

/**
 * Adresse complète d'une page du site, pour les liens des e-mails : app_url('/dashboard') → https://rembourso.florianlovis.ch/dashboard
 * En local : http://localhost:<port>. L'en-tête Host de la requête n'est jamais utilisé (il est choisi par le client :
 * un lien de réinitialisation pourrait sinon pointer vers un autre site).
 */
function app_url(string $path = '/'): string
{
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    if (in_array($serverName, ['localhost', '127.0.0.1'], true)) {
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
 * $headers       : en-têtes supplémentaires (ex. « Reply-To: … »).
 * $token         : jeton du lien contenu dans l'e-mail (en clair) ;
 * $loggedMessage : texte enregistré dans la table emails à la place du message.
 * $details       : [libellé => valeur], affichés en tableau (ex. montant, bénéficiaire).
 * $action        : ['label' => 'Créer mon compte', 'url' => …] : bouton principal de l'e-mail.
 *
 * Renvoie l'identifiant de l'e-mail enregistré, ou false si l'envoi a échoué.
 */
function sendMail(string $to, string $subject, string $message, string $headers = '', ?string $token = null, ?string $loggedMessage = null, array $details = [], ?array $action = null): string|false
{
    global $mysqlClient;

    $boundary = 'rembourso-' . bin2hex(random_bytes(12));
    // base64 : les accents et les longues lignes passent sans risque dans tous les serveurs de messagerie
    $body = "--$boundary\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode(render_mail_text($message, $details, $action)))
        . "--$boundary\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode(render_mail_html($subject, $message, $details, $action)))
        . "--$boundary--\r\n";

    $headers = trim($headers) . "\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$boundary\"";
    // Sujet encodé (=?UTF-8?B?…?=) : les accents s'affichent correctement dans les messageries
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    if (!mail($to, $encodedSubject, $body, ltrim($headers))) {
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
