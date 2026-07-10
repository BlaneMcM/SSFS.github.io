<?php
/**
 * Simple, dependency-free test runner for properCase.php.
 * Run with: php test/properCaseTest.php
 *
 * These are the same test vectors used in the Node version of this
 * project (test/properCase.test.js), so behavior should match exactly.
 */

require __DIR__ . '/../lib/properCase.php';

$tests = [
    ['MARY', 'Mary'],
    ['mary', 'Mary'],
    ['mArY', 'Mary'],

    ['mcmichen', 'McMichen'],
    ['MCMICHEN', 'McMichen'],
    ['McMichen', 'McMichen'],
    ['mcdonald', 'McDonald'],
    ['mcgregor', 'McGregor'],

    ['macdonald', 'MacDonald'],
    ['MACDONALD', 'MacDonald'],
    ['macmillan', 'MacMillan'],

    ['macy', 'Macy'],
    ['MACK', 'Mack'],
    ['mace', 'Mace'],

    ["o'rielly", "O'Rielly"],
    ["O'RIELLY", "O'Rielly"],
    ["d'angelo", "D'Angelo"],
    ["d\u{2019}souza", "D\u{2019}Souza"],

    ['mary-jane', 'Mary-Jane'],
    ['SMITH-JONES', 'Smith-Jones'],
    ['jean-luc', 'Jean-Luc'],

    ['van der berg', 'Van der Berg'],
    ['VAN DER BERG', 'Van der Berg'],
    ['de la cruz', 'De la Cruz'],

    ['', ''],
    ['  mary   smith  ', 'Mary Smith'],
];

$pass = 0;
$fail = 0;

foreach ($tests as [$input, $expected]) {
    $actual = toProperCase($input);
    if ($actual === $expected) {
        $pass++;
        echo "OK   : " . var_export($input, true) . " -> " . var_export($actual, true) . "\n";
    } else {
        $fail++;
        echo "FAIL : " . var_export($input, true) . " -> " . var_export($actual, true)
            . " (expected " . var_export($expected, true) . ")\n";
    }
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
