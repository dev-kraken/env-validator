<?php

declare(strict_types=1);

namespace EnvValidator\Collections\NumericRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is an integer.
 *
 * This rule ensures that the value is a valid integer,
 * optionally within a specified range.
 *
 * @example
 * ```php
 * // Validate an integer
 * $rule = new IntegerRule();
 *
 * // Validate an integer between 1 and 100
 * $rule = new IntegerRule(min: 1, max: 100);
 *
 * // Validate a positive integer
 * $rule = new IntegerRule(min: 1);
 * ```
 */
final class IntegerRule extends AbstractRule
{
    /**
     * Create a new integer rule instance.
     *
     * @param  int|null  $min  Minimum allowed value (inclusive)
     * @param  int|null  $max  Maximum allowed value (inclusive)
     */
    public function __construct(
        private readonly ?int $min = null,
        private readonly ?int $max = null
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
        // Check if the value is an integer or a string representation of an integer
        if (! is_numeric($value) || (string) (int) $value !== (string) $value) {
            return false;
        }

        $intValue = (int) $value;

        // Check minimum value constraint
        if ($this->min !== null && $intValue < $this->min) {
            return false;
        }

        // Check maximum value constraint
        if ($this->max !== null && $intValue > $this->max) {
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

        if ($this->min !== null && $this->max !== null) {
            $constraints[] = "between {$this->min} and {$this->max}";
        } elseif ($this->min !== null) {
            $constraints[] = "at least {$this->min}";
        } elseif ($this->max !== null) {
            $constraints[] = "at most {$this->max}";
        }

        if (empty($constraints)) {
            return 'The :attribute must be an integer.';
        }

        return 'The :attribute must be an integer '.implode(' and ', $constraints).'.';
    }
}
