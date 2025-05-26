<?php

declare(strict_types=1);

namespace EnvValidator\Core;

use EnvValidator\Contracts\RuleValidatorInterface;
use EnvValidator\Core\Validators\BuiltInRuleValidator;
use EnvValidator\Core\Validators\StringRuleValidator;
use EnvValidator\Core\Validators\ValidationRuleValidator;

/**
 * Standalone validator for environments without Laravel.
 *
 * This class provides environment variable validation without requiring
 * the full Laravel framework. It uses a pluggable validator system
 * that supports various validation strategies through dedicated validators.
 */
final class StandaloneValidator
{
    /**
     * Rule validators for different types of validation rules.
     *
     * @var array<RuleValidatorInterface>
     */
    private array $validators;

    /**
     * Create a new StandaloneValidator instance.
     */
    public function __construct()
    {
        $this->validators = [
            new StringRuleValidator,
            new ValidationRuleValidator,
            new BuiltInRuleValidator,
        ];
    }

    /**
     * Validate environment variables against defined rules.
     *
     * @param  array<string, mixed>  $env  Environment variables to validate
     * @param  array<string, mixed>  $rules  Validation rules
     * @param  array<string, string>  $messages  Custom error messages
     * @return bool|array<string, array<int, string>> True if valid, array of errors otherwise
     */
    public static function validate(array $env, array $rules, array $messages = []): bool|array
    {
        return (new self)->performValidation($env, $rules, $messages);
    }

    /**
     * Perform the actual validation logic.
     *
     * @param  array<string, mixed>  $env  Environment variables to validate
     * @param  array<string, mixed>  $rules  Validation rules
     * @param  array<string, string>  $messages  Custom error messages
     * @return bool|array<string, array<int, string>> True if valid, array of errors otherwise
     */
    public function performValidation(array $env, array $rules, array $messages = []): bool|array
    {
        $errors = [];

        foreach ($rules as $key => $rule) {
            $this->validateField($key, $rule, $env, $messages, $errors);
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Add a custom validator to the validation chain.
     *
     * @param  RuleValidatorInterface  $validator  The validator to add
     */
    public function addValidator(RuleValidatorInterface $validator): self
    {
        $this->validators[] = $validator;

        return $this;
    }

    /**
     * Validate a single field against its rules.
     *
     * @param  string  $key  The field name
     * @param  mixed  $rule  The validation rule(s)
     * @param  array<string, mixed>  $env  Environment variables
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, array<int, string>>  $errors  Error collection (passed by reference)
     */
    private function validateField(
        string $key,
        mixed $rule,
        array $env,
        array $messages,
        array &$errors
    ): void {
        // Check required validation first
        if ($this->isRequiredAndMissing($rule, $env, $key)) {
            $errors[$key][] = $messages["$key.required"] ?? "The $key field is required.";

            return;
        }

        // Skip if key is not present and not required
        if (! isset($env[$key])) {
            return;
        }

        // Apply all applicable validation rules
        $this->applyValidationRules($key, $rule, $env[$key], $messages, $errors);
    }

    /**
     * Check if a field is required but missing.
     *
     * @param  mixed  $rule  The validation rule
     * @param  array<string, mixed>  $env  Environment variables
     * @param  string  $key  The field name
     */
    private function isRequiredAndMissing(mixed $rule, array $env, string $key): bool
    {
        return is_string($rule)
            && str_contains($rule, 'required')
            && (! isset($env[$key]) || $env[$key] === '');
    }

    /**
     * Apply all validation rules to a field value.
     *
     * @param  string  $key  The field name
     * @param  mixed  $rule  The validation rule(s)
     * @param  mixed  $value  The field value
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, array<int, string>>  $errors  Error collection (passed by reference)
     */
    private function applyValidationRules(
        string $key,
        mixed $rule,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        // Handle array of rules
        if (is_array($rule)) {
            foreach ($rule as $singleRule) {
                $this->applySingleRule($key, $singleRule, $value, $messages, $errors);
            }

            return;
        }

        // Handle single rule
        $this->applySingleRule($key, $rule, $value, $messages, $errors);
    }

    /**
     * Apply a single validation rule using the validator chain.
     *
     * @param  string  $key  The field name
     * @param  mixed  $rule  The validation rule
     * @param  mixed  $value  The field value
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, array<int, string>>  $errors  Error collection (passed by reference)
     */
    private function applySingleRule(
        string $key,
        mixed $rule,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        // Try each validator until one can handle the rule
        foreach ($this->validators as $validator) {
            if ($validator->canHandle($rule)) {
                $validator->validate($key, $rule, $value, $messages, $errors);

                return;
            }
        }

        // If no validator can handle the rule, log or handle gracefully
        // For now, we'll simply ignore unknown rule types
    }
}
