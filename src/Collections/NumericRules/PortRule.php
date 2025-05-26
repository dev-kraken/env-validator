<?php

declare(strict_types=1);

namespace EnvValidator\Collections\NumericRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is a valid port number.
 *
 * This rule validates port numbers (1-65535) for environment variables
 * like DB_PORT, REDIS_PORT, MAIL_PORT, etc.
 *
 * @example
 * ```php
 * // Basic usage
 * $rule = new PortRule();
 *
 * // With custom range
 * $rule = new PortRule(1024, 65535); // Only non-privileged ports
 *
 * // With custom error message
 * $rule = new PortRule(1, 65535, 'The :attribute must be a valid port number.');
 *
 * // Usage in validation rules
 * $rules = [
 *     'DB_PORT' => ['required', new PortRule()],
 *     'REDIS_PORT' => ['required', new PortRule(1024, 65535)],
 * ];
 * ```
 */
final class PortRule extends AbstractRule
{
    /**
     * Create a new PortRule instance.
     *
     * @param  int  $min  Minimum port number (default: 1)
     * @param  int  $max  Maximum port number (default: 65535)
     * @param  string|null  $customMessage  Custom error message
     */
    public function __construct(
        private readonly int $min = 1,
        private readonly int $max = 65535,
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
        // Convert to integer if it's a numeric string
        if (is_string($value) && is_numeric($value)) {
            $value = (int) $value;
        }

        if (! is_int($value)) {
            return false;
        }

        return $value >= $this->min && $value <= $this->max;
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        return $this->customMessage ?? "The :attribute must be a valid port number between {$this->min} and {$this->max}.";
    }

    /**
     * Get the minimum port number.
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * Get the maximum port number.
     */
    public function getMax(): int
    {
        return $this->max;
    }
}
