<?php

declare(strict_types=1);

namespace EnvValidator\Collections\StringRules;

use EnvValidator\Core\AbstractRule;
use JsonException;

/**
 * Validates that a value is valid JSON.
 *
 * This rule is useful for environment variables that contain JSON configuration,
 * such as API credentials, feature flags, or complex configuration objects.
 *
 * @example
 * ```php
 * // Basic usage
 * $rule = new JsonRule();
 *
 * // With custom error message
 * $rule = new JsonRule('The :attribute must contain valid JSON.');
 *
 * // Usage in validation rules
 * $rules = [
 *     'API_CREDENTIALS' => ['required', 'string', new JsonRule()],
 *     'FEATURE_FLAGS' => ['nullable', new JsonRule()],
 * ];
 * ```
 */
final class JsonRule extends AbstractRule
{
    /**
     * Create a new JsonRule instance.
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

        if (trim($value) === '') {
            return false;
        }

        try {
            json_decode($value, false, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (JsonException) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        return $this->customMessage ?? 'The :attribute must be valid JSON.';
    }
}
