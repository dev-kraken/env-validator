<?php

declare(strict_types=1);

namespace EnvValidator\Contracts;

/**
 * Interface for rule validators in the standalone validation system.
 *
 * This interface defines the contract for validating specific types of rules
 * in the environment validator. Implementations should handle validation
 * for a specific rule type and add errors to the error collection.
 */
interface RuleValidatorInterface
{
    /**
     * Validate a field value against a specific rule type.
     *
     * @param  string  $key  The field name being validated
     * @param  mixed  $rule  The rule to validate against
     * @param  mixed  $value  The field value to validate
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, array<int, string>>  $errors  Error collection (passed by reference)
     */
    public function validate(
        string $key,
        mixed $rule,
        mixed $value,
        array $messages,
        array &$errors
    ): void;

    /**
     * Check if this validator can handle the given rule.
     *
     * @param  mixed  $rule  The rule to check
     * @return bool True if this validator can handle the rule, false otherwise
     */
    public function canHandle(mixed $rule): bool;
}
