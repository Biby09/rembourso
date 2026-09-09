<?php

function rate_limit_key(string $scope, string $identifier): string
{
    return hash('sha256', $scope . "\0" . $identifier);
}

function rate_limit_consume(string $scope, string $identifier, int $maxAttempts, int $windowSeconds): void
{
    global $mysqlClient;

    $key = rate_limit_key($scope, $identifier);
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $stmt = $mysqlClient->prepare('SELECT attempts, window_started_at FROM rate_limits WHERE rate_key = :rate_key FOR UPDATE');

    $mysqlClient->beginTransaction();
    try {
        $stmt->execute([':rate_key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $now->getTimestamp() - strtotime($row['window_started_at']) >= $windowSeconds) {
            $stmt = $mysqlClient->prepare('INSERT INTO rate_limits (rate_key, attempts, window_started_at) VALUES (:rate_key, 1, :window_started_at) ON DUPLICATE KEY UPDATE attempts = 1, window_started_at = VALUES(window_started_at)');
            $stmt->execute([
                ':rate_key' => $key,
                ':window_started_at' => $now->format('Y-m-d H:i:s')
            ]);
            $attempts = 1;
            $windowStartedAt = $now;
        } else {
            $attempts = (int) $row['attempts'];
            $windowStartedAt = new DateTimeImmutable($row['window_started_at'], new DateTimeZone('UTC'));
        }

        if ($attempts >= $maxAttempts) {
            $retryAfter = max(1, $windowSeconds - ($now->getTimestamp() - $windowStartedAt->getTimestamp()));
            $mysqlClient->commit();
            header('Retry-After: ' . $retryAfter);
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Veuillez réessayer plus tard.']);
            exit;
        }

        $stmt = $mysqlClient->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE rate_key = :rate_key');
        $stmt->execute([':rate_key' => $key]);
        $mysqlClient->commit();
    } catch (Throwable $exception) {
        if ($mysqlClient->inTransaction()) {
            $mysqlClient->rollBack();
        }
        throw $exception;
    }
}

function rate_limit_clear(string $scope, string $identifier): void
{
    global $mysqlClient;

    $stmt = $mysqlClient->prepare('DELETE FROM rate_limits WHERE rate_key = :rate_key');
    $stmt->execute([':rate_key' => rate_limit_key($scope, $identifier)]);
}