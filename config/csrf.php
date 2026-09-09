<?php
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function verify_csrf_request(?array $payload = null, bool $requireField = false): void {
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $fieldToken = $payload['csrf_token'] ?? $_POST['csrf_token'] ?? null;

    $validHeader = verify_csrf_token($headerToken);
    $validField = $fieldToken !== null && is_string($fieldToken)
        && $validHeader && hash_equals($headerToken, $fieldToken);

    if (!$validHeader || ($requireField && !$validField) || ($fieldToken !== null && !$validField)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Requête CSRF invalide']);
        exit;
    }
}