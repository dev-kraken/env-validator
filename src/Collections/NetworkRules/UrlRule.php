<?php

declare(strict_types=1);

namespace EnvValidator\Collections\NetworkRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is a valid URL.
 *
 * This rule checks if the value is a valid URL using PHP's built-in
 * filter_var() function with the FILTER_VALIDATE_URL filter.
 *
 * Accepted URL formats include:
 * - http://example.com
 * - https://example.com
 * - http://localhost:8000
 * - https://example.com/path?query=param
 * - ftp://example.com
 *
 * @example
 * ```php
 * // Usage in rules
 * $rules = [
 *     'APP_URL' => [new UrlRule()],
 *     'API_ENDPOINT' => [UrlRule::class],
 * ];
 * ```
 */
final class UrlRule extends AbstractRule
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

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        return 'The :attribute must be a valid URL.';
    }
}
