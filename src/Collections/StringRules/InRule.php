<?php

declare(strict_types=1);

namespace EnvValidator\Collections\StringRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is in a given list of values.
 *
 * This rule provides a clean, type-safe alternative to the string-based 'in:' rule.
 * It allows validating that a value exists within a predefined set of acceptable values.
 *
 * @example
 * ```php
 * // Basic usage
 * $rule = new InRule(['staging', 'production']);
 *
 * // With custom error message
 * $rule = new InRule(
 *     ['dev', 'test', 'prod'],
 *     'The :attribute must be a valid environment (dev, test, prod).'
 * );
 *
 * // Usage in validation rules
 * $rules = [
 *     'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])],
 *     'LOG_LEVEL' => ['required', new InRule(['debug', 'info', 'warning', 'error'])],
 * ];
 * ```
 */
final class InRule extends AbstractRule
{
    /**
     * Create a new InRule instance.
     *
     * @param  array<int, mixed>  $validValues  Array of valid values
     * @param  string|null  $customMessage  Custom error message
     * @param  bool  $strict  Whether to use strict comparison (default: true)
     */
    public function __construct(
        private readonly array $validValues,
        private readonly ?string $customMessage = null,
        private readonly bool $strict = true
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
        return in_array($value, $this->validValues, $this->strict);
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        if ($this->customMessage !== null) {
            return $this->customMessage;
        }

        $values = array_map(
            static function ($value): string {
                if (is_string($value)) {
                    return $value;
                }
                if (is_scalar($value) || $value === null) {
                    return (string) $value;
                }

                return 'object';
            },
            $this->validValues
        );

        return 'The :attribute must be one of: '.implode(', ', $values).'.';
    }

    /**
     * Get the valid values for this rule.
     *
     * @return array<int, mixed>
     */
    public function getValidValues(): array
    {
        return $this->validValues;
    }

    /**
     * Check if strict comparison is enabled.
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }
}
