<?php

declare(strict_types=1);

namespace EnvValidator\Collections\StringRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is a valid email address.
 *
 * This rule provides email validation for environment variables like
 * MAIL_FROM_ADDRESS, ADMIN_EMAIL, etc.
 *
 * @example
 * ```php
 * // Basic usage
 * $rule = new EmailRule();
 *
 * // With custom error message
 * $rule = new EmailRule('The :attribute must be a valid email address.');
 *
 * // Usage in validation rules
 * $rules = [
 *     'MAIL_FROM_ADDRESS' => ['required', 'string', new EmailRule()],
 *     'ADMIN_EMAIL' => ['nullable', new EmailRule()],
 * ];
 * ```
 */
final class EmailRule extends AbstractRule
{
    /**
     * Create a new EmailRule instance.
     *
     * @param  string|null  $customMessage  Custom error message
     */
    public function __construct(
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
        if (! is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        return $this->customMessage ?? 'The :attribute must be a valid email address.';
    }
}
