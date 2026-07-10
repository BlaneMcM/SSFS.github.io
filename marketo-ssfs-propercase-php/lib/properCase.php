<?php
/**
 * properCase.php
 *
 * Converts a name (first or last) that may have been entered in any
 * combination of upper/lower case into "proper case" for use in
 * personalization (emails) and general data hygiene.
 *
 * Handles:
 *   - plain names:                "MARY"        -> "Mary"
 *   - Mc surnames:                 "mcmichen"    -> "McMichen"
 *   - Mac surnames:                 "macdonald"  -> "MacDonald"
 *   - apostrophe surnames:          "o'rielly"   -> "O'Rielly"
 *   - hyphenated names:             "MARY-JANE"  -> "Mary-Jane"
 *   - multi-word surname particles: "van der berg" -> "Van der Berg"
 *
 * This is a heuristic, not a lookup against a name database, so it will
 * not be perfect for every surname on earth. The exception lists below
 * (MAC_EXCEPTIONS, NAME_PARTICLES) are the places to extend it for your
 * own data set.
 *
 * This is a line-for-line port of lib/properCase.js from the Node
 * version of this project, so behavior matches exactly (verified
 * against the same test vectors — see test/properCaseTest.php).
 */

// Common English words that start with "mac" but are NOT "Mac + Surname"
// (e.g. we want "Macy" to stay "Macy", not become "MacY").
const MAC_EXCEPTIONS = [
    'mack', 'macy', 'mace', 'macon', 'mach',
    'machine', 'macaroni', 'macaque', 'macau',
];

// Lower-case "particles" commonly found in multi-word surnames.
// These stay lower-case unless they are the very first word of the
// whole name (e.g. "Van der Berg" when the surname starts with "van",
// but lower-cased if it appears later in the name).
const NAME_PARTICLES = [
    'van', 'von', 'der', 'den', 'de', 'del', 'della', 'delle',
    'di', 'da', 'du', 'dos', 'das', 'la', 'le', 'bin', 'ibn', 'al',
];

/**
 * @param string|null $input   the raw name value, any mix of case
 * @param bool $treatParticlesAsLowercase lower-case surname particles
 *        (van, der, de, ...) except when they lead the name
 * @return string|null
 */
function toProperCase($input, bool $treatParticlesAsLowercase = true)
{
    if (!is_string($input)) {
        return $input;
    }

    $trimmed = preg_replace('/\s+/', ' ', trim($input));
    if ($trimmed === '') {
        return $trimmed;
    }

    $words = explode(' ', $trimmed);
    $casedWords = [];

    foreach ($words as $wordIndex => $word) {
        $hyphenParts = explode('-', $word);
        $casedHyphenParts = [];

        foreach ($hyphenParts as $partIndex => $part) {
            $isFirstOverall = ($wordIndex === 0 && $partIndex === 0);
            $casedHyphenParts[] = caseNamePart($part, $isFirstOverall, $treatParticlesAsLowercase);
        }

        $casedWords[] = implode('-', $casedHyphenParts);
    }

    return implode(' ', $casedWords);
}

function caseNamePart(string $part, bool $isFirstOverall, bool $treatParticlesAsLowercase): string
{
    if ($part === '') {
        return $part;
    }

    $lower = mb_strtolower($part);

    if ($treatParticlesAsLowercase && !$isFirstOverall && in_array($lower, NAME_PARTICLES, true)) {
        return $lower;
    }

    // apostrophe surnames: O'Rielly, D'Angelo, D'Souza (curly or straight quote)
    $aposChar = null;
    if (strpos($lower, "\u{2019}") !== false) {
        $aposChar = "\u{2019}";
    } elseif (strpos($lower, "'") !== false) {
        $aposChar = "'";
    }

    if ($aposChar !== null) {
        $segments = explode($aposChar, $lower);
        $capitalized = array_map('capitalizeSegment', $segments);
        return implode($aposChar, $capitalized);
    }

    return capitalizeSegment($lower);
}

function capitalizeSegment(string $segment): string
{
    if ($segment === '') {
        return $segment;
    }

    // Mc prefix: McMichen, McDonald, McGregor ...
    if (mb_strlen($segment) > 2 && mb_substr($segment, 0, 2) === 'mc') {
        return 'Mc' . capitalizeFirst(mb_substr($segment, 2));
    }

    // Mac prefix: MacDonald, MacMillan ... (skip known non-surname exceptions)
    if (mb_strlen($segment) > 3 && mb_substr($segment, 0, 3) === 'mac' && !in_array($segment, MAC_EXCEPTIONS, true)) {
        return 'Mac' . capitalizeFirst(mb_substr($segment, 3));
    }

    return capitalizeFirst($segment);
}

function capitalizeFirst(string $str): string
{
    if ($str === '') {
        return $str;
    }
    return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
}
