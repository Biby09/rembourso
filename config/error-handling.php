<?php

function log_server_exception(Throwable $exception, string $context): void
{
    error_log($context . ': ' . $exception->getMessage());
}