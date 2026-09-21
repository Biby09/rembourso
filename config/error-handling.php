<?php

function log_server_exception(Throwable $exception, string $context): void
{
    error_log($context . ': ' . $exception->getMessage());
    if (!function_exists('sendMail')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/config/object.php';
    }
    sendMail('webmaster@florianlovis.ch','Server Exception',$context . ': ' . $exception->getMessage());

}