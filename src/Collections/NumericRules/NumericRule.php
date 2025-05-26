<?php

declare(strict_types=1);

namespace EnvValidator\Collections\NumericRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is numeric and optionally within a specific range.
 *
 * This rule can validate:
 * - If a value is numeric
 * - If a value is within a specified range (min/max)
 * - If a value is an integer (no decimals)
 *
 * @example
 * ```php
 * // Validate a numeric value
 * $rule = new NumericRule();
 *
 * // Validate an integer (no decimals)
 * $rule = new NumericRule(allowDecimals: false);
 *
 * // Validate a value between 1 and 100
 * $rule = new NumericRule(min: 1, max: 100);
 *
 * // Validate an integer between 1 and 100
 * $rule = new NumericRule(min: 1, max: 100, allowDecimals: false);
 * ```
 */
final class NumericRule extends AbstractRule
{
    /**
     * Create a new numeric rule instance.
     *
     * @param  float|null  $min  Minimum allowed value (inclusive)
     * @param  float|null  $max  Maximum allowed value (inclusive)
     * @param  bool  $allowDecimals  Whether to allow decimal values
     */
    public function __construct(
        private readonly ?float $min = null,
        private readonly ?float $max = null,
        private readonly bool $allowDecimals = true
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
        // Check if the value is numeric
        if (! is_numeric($value)) {
            return false;
        }

        // Convert to float for comparison
        $numericValue = (float) $value;

        // Check if decimals are not allowed but the value has decimals
        if (! $this->allowDecimals && floor($numericValue) !== $numericValue) {
            return false;
        }

        // Check minimum value constraint
        if ($this->min !== null && $numericValue < $this->min) {
            return false;
        }

        // Check maximum value constraint
        if ($this->max !== null && $numericValue > $this->max) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        $constraints = [];

        if (! $this->allowDecimals) {
            $constraints[] = 'an integer';
        }

        if ($this->min !== null && $this->max !== null) {
            $constraints[] = "between {$this->min} and {$this->max}";
        } elseif ($this->min !== null) {
            $constraints[] = "at least {$this->min}";
        } elseif ($this->max !== null) {
            $constraints[] = "at most {$this->max}";
        }

        if (empty($constraints)) {
            return 'The :attribute must be a numeric value.';
        }

        return 'The :attribute must be '.implode(' and ', $constraints).'.';
    }
}
