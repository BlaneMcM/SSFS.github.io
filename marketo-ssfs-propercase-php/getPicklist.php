<?php
require __DIR__ . '/common.php';
requireApiKey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['message' => 'Method not allowed']);
    exit;
}

// Marketo POSTs a body like {"name": "nameType", "type": "flow"} when
// polling for choices. We only have one picklist attribute, so we
// return the same two choices regardless of what was asked for.
$request = readJsonBody();

sendJson(200, [
    'choices' => [
        ['submittedValue' => 'First', 'displayValue' => ['en_US' => 'First Name']],
        ['submittedValue' => 'Last', 'displayValue' => ['en_US' => 'Last Name']],
    ],
]);
