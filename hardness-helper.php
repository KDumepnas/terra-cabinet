<?php
declare(strict_types=1);

/** Validate and normalize an optional Mohs hardness value or range. */
function normalize_hardness(string $input): array
{
    $input = trim($input);
    if ($input === '') return ['value' => '', 'error' => null];

    if (!preg_match('/^(\d+(?:\.\d+)?)\s*(?:(?:-|–|to)\s*(\d+(?:\.\d+)?))?\s*(?:\(\s*mohs\s+scale\s*\))?$/i', $input, $matches)) {
        return ['value' => $input, 'error' => 'Enter a Mohs hardness from 1 to 10, such as 7 or 3.5-4.'];
    }

    $low = (float)$matches[1];
    $high = isset($matches[2]) && $matches[2] !== '' ? (float)$matches[2] : null;
    if ($low < 1 || $low > 10 || ($high !== null && ($high < 1 || $high > 10))) {
        return ['value' => $input, 'error' => 'Mohs hardness values must be between 1 and 10.'];
    }
    if ($high !== null && $low > $high) {
        return ['value' => $input, 'error' => 'The lower hardness value must come first.'];
    }

    $format = static function (float $value): string {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    };
    $value = $format($low) . ($high === null ? '' : '-' . $format($high));
    return ['value' => $value . ' (Mohs scale)', 'error' => null];
}
