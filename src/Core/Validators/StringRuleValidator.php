<?php

declare(strict_types=1);

namespace EnvValidator\Core\Validators;

use EnvValidator\Contracts\RuleValidatorInterface;

/**
 * Validator for string-based rules like 'in:value1,value2,value3'.
 *
 * This validator handles parsing and validation of rules that are
 * defined as strings with specific patterns, such as enum validation.
 */
final class StringRuleValidator implements RuleValidatorInterface
{
    /**
     * Validate a field value against string-based rules.
     */
    public function validate(
        string $key,
        mixed $rule,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        if (! is_string($rule)) {
            return;
        }

        // Handle 'in:' validation (enum validation)
        if (preg_match('/in:([^|]+)/', $rule, $matches)) {
            $this->validateInRule($key, $matches[1], $value, $messages, $errors);
        }
    }

    /**
     * Check if this validator can handle the given rule.
     */
    public function canHandle(mixed $rule): bool
    {
        return is_string($rule) && (
            str_contains($rule, 'in:')
        );
    }

    /**
     * Validate 'in:' rule (enum validation).
     *
     * @param  string  $key  The field name
     * @param  string  $allowedValues  Comma-separated allowed values
     * @param  mixed  $value  The field value
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, array<int, string>>  $errors  Error collection (passed by reference)
     */
    private function validateInRule(
        string $key,
        string $allowedValues,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        $allowed = explode(',', $allowedValues);

        if (! in_array($value, $allowed, true)) {
            $errors[$key][] = $messages["$key.in"] ??
                "The $key must be one of: ".implode(', ', $allowed);
        }
    }
}
