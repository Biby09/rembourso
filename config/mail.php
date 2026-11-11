<?php
/**
 * Envoi des e-mails. Chaque e-mail envoyé est enregistré dans la table emails
 * (avec le jeton du lien qu'il contient, pour connaître la date du dernier envoi d'une invitation).
 */

/**
 * Envoie un e-mail en texte brut, entouré de la formule de politesse.
 * $token : jeton du lien contenu dans l'e-mail (en clair) ; $loggedMessage : texte enregistré à la place du message
 * (ex. sans le jeton d'un lien de réinitialisation). Renvoie l'identifiant de l'e-mail enregistré, ou false.
 */
function sendMail(string $to, string $subject, string $message, string $headers = '', ?string $token = null, ?string $loggedMessage = null): string|false
{
    global $mysqlClient;

    $mailBody = "Bonjour,\n\n" . $message . "\n\nCordialement,\nL'équipe Rembourso";
    if (!mail($to, $subject, $mailBody, $headers)) {
        return false;
    }

    // Le jeton est stocké en clair (invitations) ou haché (autres liens) : on cherche les deux
    $stmt = $mysqlClient->prepare('INSERT INTO emails (ema_to, ema_subject, ema_content, idx_author, idx_token)
        VALUES (:to, :subject, :message, :author, (SELECT id_token FROM tokens WHERE tok_token = :token OR tok_token = :token_hash LIMIT 1))');
    $stmt->execute([
        'to' => $to,
        'subject' => $subject,
        'message' => $loggedMessage ?? $message,
        'author' => $_SESSION['user_id'] ?? null,
        'token' => $token,
        'token_hash' => $token ? hash('sha256', $token) : null,
    ]);

    return $mysqlClient->lastInsertId();
}

/**
 * Lien envoyé par e-mail pour un jeton (token.php redirige vers la bonne page selon son type).
 */
function token_link(string $token): string
{
    return $_SERVER['SERVER_NAME'] . '/token.php?token=' . $token;
}
