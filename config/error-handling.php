<?php

/**
 * Journalise une exception serveur et prévient le webmaster par e-mail.
 * À réserver aux erreurs inattendues (pas aux 404 / erreurs de saisie).
 */
function log_server_exception(Throwable $exception, string $context): void
{
    error_log($context . ': ' . $exception->getMessage());

    if (function_exists('sendMail')) {
        sendMail('webmaster@florianlovis.ch', 'Server Exception', $context . ': ' . $exception->getMessage());
    }
}
