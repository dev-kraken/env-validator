<?php

declare(strict_types=1);

namespace EnvValidator\Core\Validators;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\KeyRule;
use EnvValidator\Contracts\RuleValidatorInterface;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validator for built-in rule classes.
 *
 * This validator handles validation using the package's built-in rules
 * such as BooleanRule, UrlRule, and KeyRule. It provides a centralized
 * way to manage and extend built-in rule validation.
 */
final class BuiltInRuleValidator implements RuleValidatorInterface
{
    /**
     * Built-in rule classes mapped to their factory methods.
     *
     * @var array<class-string<ValidationRule>, string>
     */
    private static array $ruleMap = [
        BooleanRule::class => 'validateWithBooleanRule',
        UrlRule::class => 'validateWithUrlRule',
        KeyRule::class => 'validateWithKeyRule',
    ];

    /**
     * Validate a field value against built-in rules.
     */
    public function validate(
        string $key,
        mixed $rule,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        $ruleClass = $this->getRuleClass($rule);

        if ($ruleClass === null || ! isset(self::$ruleMap[$ruleClass])) {
            return;
        }

        $method = self::$ruleMap[$ruleClass];
        $this->$method($key, $value, $messages, $errors);
    }

    /**
     * Check if this validator can handle the given rule.
     */
    public function canHandle(mixed $rule): bool
    {
        return $this->getRuleClass($rule) !== null;
    }

    /**
     * Get the rule class from various rule formats.
     */
    private function getRuleClass(mixed $rule): ?string
    {
        // Handle direct class names
        if (is_string($rule) && isset(self::$ruleMap[$rule])) {
            return $rule;
        }

        // Handle string rules that contain class names (including with namespaces)
        if (is_string($rule)) {
            foreach (array_keys(self::$ruleMap) as $ruleClass) {
                // Check if the full class name is in the string
                if (str_contains($rule, $ruleClass)) {
                    return $ruleClass;
                }

                // Also check for the short class name (e.g., "BooleanRule" from full namespaced class)
                $shortClassName = basename(str_replace('\\', '/', $ruleClass));
                if (str_contains($rule, $shortClassName)) {
                    return $ruleClass;
                }
            }
        }

        // Handle object instances
        if (is_object($rule)) {
            $ruleClass = get_class($rule);
            if (isset(self::$ruleMap[$ruleClass])) {
                return $ruleClass;
            }
        }

        return null;
    }

    /**
     * Validate using BooleanRule.
     *
     * @param  string  $key  The field name
     * @param  mixed  $value  The field value
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, array<int, string>>  $errors  Error collection (passed by reference)
     */
    private function validateWithBooleanRule(
        string $key,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        $rule = new BooleanRule;
        if (! $rule->passes($key, $value)) {
            $message = $rule->message();
            $errors[$key][] = $messages["$key.boolean"] ?? $message;
        }
    }

    /**
     * Validate using UrlRule.
     *
     * @param  string  $key  The field name
     * @param  mixed  $value  The field value
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, array<int, string>>  $errors  Error collection (passed by reference)
     */
    private function validateWithUrlRule(
        string $key,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        $rule = new UrlRule;
        if (! $rule->passes($key, $value)) {
            $message = $rule->message();
            $errors[$key][] = $messages["$key.url"] ?? $message;
        }
    }

    /**
     * Validate using KeyRule.
     *
     * @param  string  $key  The field name
     * @param  mixed  $value  The field value
     * @param  array<string, string>  $messages  Custom error messages
     * @param  array<string, array<int, string>>  $errors  Error collection (passed by reference)
     */
    private function validateWithKeyRule(
        string $key,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        $rule = new KeyRule;
        if (! $rule->passes($key, $value)) {
            $message = $rule->message();
            $errors[$key][] = $messages["$key.key"] ?? $message;
        }
    }

    /**
     * Register a new built-in rule.
     *
     * This allows extending the built-in rule system at runtime.
     *
     * @param  class-string<ValidationRule>  $ruleClass  The rule class
     * @param  string  $validationMethod  The method to call for validation
     */
    public static function registerRule(string $ruleClass, string $validationMethod): void
    {
        self::$ruleMap[$ruleClass] = $validationMethod;
    }
}
