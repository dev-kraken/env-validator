<?php

declare(strict_types=1);

namespace EnvValidator\Collections\StringRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is a boolean.
 *
 * Accepted values:
 * - true/false (boolean)
 * - "true"/"false" (string, case-insensitive)
 * - 1/0 (integer)
 * - "1"/"0" (string)
 * - "yes"/"no" (string, case-insensitive)
 * - "on"/"off" (string, case-insensitive)
 *
 * @example
 * ```php
 * // Usage in rules
 * $rules = [
 *     'DEBUG_MODE' => [new BooleanRule()],
 *     'FEATURE_FLAG' => [BooleanRule::class],
 * ];
 * ```
 */
final class BooleanRule extends AbstractRule
{
    /**
     * The valid boolean values.
     *
     * @var array<int, string|int|bool>
     */
    private const VALID_VALUES = [
        'true', 'false', '1', '0', 'yes', 'no', 'on', 'off',
        1, 0, true, false,
    ];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute being validated
     * @param  mixed  $value  The value being validated
     * @return bool True if validation passes, false otherwise
     */
    public function passes(string $attribute, mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        // Already a boolean
        if (is_bool($value)) {
            return true;
        }

        // Normalize string representation to lowercase
        if (is_string($value)) {
            $value = strtolower($value);
        }

        return in_array($value, self::VALID_VALUES, true);
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        return 'The :attribute must be a boolean value (true, false, 1, 0, yes, no, on, off).';
    }
}
