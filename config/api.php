<?php
/**
 * Amorçage des endpoints JSON de /api accessibles sans être connecté
 * (les autres incluent config/api-auth.php, qui inclut ce fichier).
 *
 * Toutes les réponses d'erreur ont la forme :
 *   { "success": false, "error": "<message>", "message": "<message>" }
 * (les deux clés sont présentes pour rester compatibles avec le front).
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_success(array $data = []): never
{
    json_response(['success' => true] + $data);
}

function json_error(string $message, int $status = 400, array $extra = []): never
{
    json_response(['success' => false, 'error' => $message, 'message' => $message] + $extra, $status);
}

function api_require_method(string $method = 'POST'): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        json_error('Méthode non autorisée.', 405);
    }
}

/**
 * Lit le corps JSON de la requête et vérifie le jeton CSRF (en-tête X-CSRF-Token).
 */
function api_json_input(): array
{
    $input = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($input) ? $input : [];
    verify_csrf_request($input);

    return $input;
}

/**
 * Vérifie le jeton CSRF d'une requête multipart/form (champ csrf_token + en-tête).
 */
function api_form_input(): array
{
    verify_csrf_request(null, true);

    return $_POST;
}

/**
 * Identifiant entier reçu du front, ou réponse 404 avec $notFoundMessage.
 */
function api_id(mixed $value, string $notFoundMessage): int
{
    if (!is_scalar($value) || !ctype_digit((string) $value)) {
        json_error($notFoundMessage, 404);
    }
    return (int) $value;
}

/**
 * Exécute $load (lecture par un Manager) et renvoie son résultat ; 404 si le Manager lève une exception
 * (objet inexistant ou appartenant à une autre organisation).
 * Exemple : $category = api_find(fn() => $categoryManager->getCategoryById($id), 'Catégorie non trouvée.');
 */
function api_find(callable $load, string $notFoundMessage): mixed
{
    try {
        return $load();
    } catch (Exception) {
        json_error($notFoundMessage, 404);
    }
}
