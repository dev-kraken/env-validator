<?php

declare(strict_types=1);

namespace EnvValidator\Collections\StringRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value matches a specific pattern.
 *
 * This rule allows validation against any regular expression pattern
 * and provides the ability to customize the error message.
 *
 * @example
 * ```php
 * // Validate an IP address
 * $rule = new PatternRule('/^(\d{1,3}\.){3}\d{1,3}$/');
 *
 * // Validate an IP address with a custom error message
 * $rule = new PatternRule(
 *     '/^(\d{1,3}\.){3}\d{1,3}$/',
 *     'The :attribute must be a valid IP address.'
 * );
 *
 * // Validate a semantic version
 * $rule = new PatternRule('/^v?\d+\.\d+\.\d+$/');
 * ```
 */
final class PatternRule extends AbstractRule
{
    /**
     * Create a new pattern rule instance.
     *
     * @param  string  $pattern  The regular expression pattern to match against
     * @param  string|null  $customMessage  A custom error message
     */
    public function __construct(
        private readonly string $pattern,
        private readonly ?string $customMessage = null
    ) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute being validated
     * @param  mixed  $value  The value being validated
     * @return bool True if validation passes, false otherwise
     */
    public function passes(string $attribute, mixed $value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match($this->pattern, (string) $value) === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        return $this->customMessage ?? 'The :attribute format is invalid.';
    }
}
