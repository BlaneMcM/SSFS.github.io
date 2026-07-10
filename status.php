<?php
require __DIR__ . '/common.php';
requireApiKey();

sendJson(200, [
    'info' => [],
    'warn' => [],
    'error' => [],
]);
