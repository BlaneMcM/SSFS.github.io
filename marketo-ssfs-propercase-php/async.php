<?php
/**
 * POST /submitAsyncAction (rewritten from async.php -- see .htaccess)
 *
 * Invoked by Marketo when the flow step runs in a Smart Campaign.
 * Per the SSFS spec, this must accept the request and respond promptly;
 * results are then POSTed back to the callbackUrl Marketo supplied.
 *
 * Shared-hosting note: true fire-and-forget "respond now, keep working
 * after" (like Node's approach) relies on things like
 * fastcgi_finish_request(), which isn't reliably available on every
 * shared-hosting PHP setup. So this version does the work first
 * (proper-case the batch, POST the callback), then returns 201. For a
 * low-volume demo/testing instance this is simpler and more portable;
 * it just means the response to Marketo takes as long as the batch
 * + callback takes, rather than returning instantly. If you outgrow
 * that, look into whether your host's PHP-FPM setup supports
 * fastcgi_finish_request().
 */

require __DIR__ . '/common.php';
require __DIR__ . '/lib/properCase.php';

requireApiKey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['message' => 'Method not allowed']);
    exit;
}

$invocation = readJsonBody();

if (!$invocation || empty($invocation['callbackUrl']) || empty($invocation['token'])) {
    sendJson(400, ['message' => 'Malformed invocation payload']);
    exit;
}

$callbackData = [];

foreach (($invocation['objectData'] ?? []) as $record) {
    $leadId = $record['objectContext']['id'] ?? null;

    if ($leadId === null) {
        // Can't attribute this result to a lead at all; skip it.
        continue;
    }

    $firstRaw = $record['objectContext']['FirstNameValue'] ?? null;
    $lastRaw = $record['objectContext']['LastNameValue'] ?? null;

    $leadData = ['id' => $leadId];
    $activityData = ['success' => true];
    $processedAny = false;

    if (is_string($firstRaw) && trim($firstRaw) !== '') {
        $formatted = toProperCase($firstRaw);
        $leadData['FirstNameFormatted'] = $formatted;
        $activityData['firstNameOriginal'] = $firstRaw;
        $activityData['firstNameFormatted'] = $formatted;
        $processedAny = true;
    }

    if (is_string($lastRaw) && trim($lastRaw) !== '') {
        $formatted = toProperCase($lastRaw);
        $leadData['LastNameFormatted'] = $formatted;
        $activityData['lastNameOriginal'] = $lastRaw;
        $activityData['lastNameFormatted'] = $formatted;
        $processedAny = true;
    }

    if (!$processedAny) {
        $activityData = [
            'success' => false,
            'errorCode' => 'MISSING_VALUE',
            'reason' => 'Neither FirstNameValue nor LastNameValue was supplied for this lead.',
        ];
    }

    $callbackData[] = [
        'leadData' => $leadData,
        'activityData' => $activityData,
    ];
}

$munchkinId = $invocation['context']['subscription']['munchkinId'] ?? '';

$callbackBody = [
    'munchkinId' => $munchkinId,
    'objectData' => $callbackData,
];

postCallback(
    $invocation['callbackUrl'],
    $callbackBody,
    $invocation['apiCallBackKey'] ?? '',
    $invocation['token'] ?? ''
);

sendJson(201, ['message' => 'accepted']);

/**
 * POST the results back to Marketo's callback URL. Per the SSFS spec,
 * this callback is authenticated via headers (x-api-key = the
 * apiCallBackKey from the original invocation, x-callback-token = the
 * one-time token from the original invocation) -- NOT by putting those
 * values in the JSON body. Uses curl if available (virtually all
 * shared hosts have it); falls back to a plain stream-context POST
 * otherwise.
 */
function postCallback(string $url, array $payload, string $apiCallBackKey, string $token): void
{
    $json = json_encode($payload);
    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . $apiCallBackKey,
        'x-callback-token: ' . $token,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('Callback to Marketo failed: ' . curl_error($ch));
        }
        curl_close($ch);
        return;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers) . "\r\n",
            'content' => $json,
            'timeout' => 15,
        ],
    ]);

    $result = @file_get_contents($url, false, $context);
    if ($result === false) {
        error_log('Callback to Marketo failed (file_get_contents fallback)');
    }
}
