<?php
/**
 * common.php
 * Shared helpers included by every endpoint: config loading, simple
 * API-key auth check, and a small JSON response helper.
 */

if (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
} else {
    // No config.php yet -- runs "open" (no key check) so you can test
    // locally/immediately after upload. Copy config.sample.php to
    // config.php and set a real key before pointing Marketo at this.
    define('SERVICE_API_KEY', '');
}

function requireApiKey(): void
{
    if (SERVICE_API_KEY === '') {
        return; // no key configured yet -- dev mode
    }

    // Marketo sends the key as the x-api-key header (per openapi.yaml).
    // We also accept it as a ?apiKey= query parameter purely so you can
    // smoke-test an endpoint from a plain browser address bar, which
    // can't send custom headers. Marketo never uses this second form.
    $provided = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['apiKey'] ?? '');

    if (!hash_equals(SERVICE_API_KEY, $provided)) {
        sendJson(401, ['message' => 'Unauthorized']);
        exit;
    }
}

function sendJson(int $statusCode, $body): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($body);
}

function readJsonBody()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return null;
    }
    return json_decode($raw, true);
}
