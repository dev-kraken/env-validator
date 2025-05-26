<?php

declare(strict_types=1);

namespace EnvValidator\Collections\StringRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is a valid Laravel application key.
 *
 * A valid Laravel application key must:
 * - Start with "base64:"
 * - Be followed by at least 40 characters of base64-encoded data
 * - Contain only alphanumeric characters, plus signs, forward slashes, and equals signs
 *
 * @example
 * ```php
 * // Usage in rules
 * $rules = [
 *     'APP_KEY' => [new KeyRule()],
 * ];
 *
 * // Laravel generates keys like this:
 * // base64:y0KpAOoEIJ3y2PbqsI5fHKLxPUnYDFUJhG+qJOb0mLQ=
 * ```
 */
final class KeyRule extends AbstractRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute being validated
     * @param  mixed  $value  The value being validated
     * @return bool True if validation passes, false otherwise
     */
    public function passes(string $attribute, mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return preg_match('/^base64:[A-Za-z0-9+\/=]{40,}$/', $value) === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        return 'The :attribute must be a valid Laravel application key (base64 encoded string).';
    }
}
