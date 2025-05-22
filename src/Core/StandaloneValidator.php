<?php

declare(strict_types=1);

namespace EnvValidator\Core;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\KeyRule;
use Illuminate\Contracts\Validation\Rule;

/**
 * Standalone validator for environments without Laravel.
 */
final class StandaloneValidator
{
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
        /** @var array<string, array<int, string>> $errors */
        $errors = [];

        foreach ($rules as $key => $rule) {
            // Required validation
            if (is_string($rule) && str_contains($rule, 'required') && (! isset($env[$key]) || $env[$key] === '')) {
                $errors[$key][] = $messages["$key.required"] ?? "The $key field is required.";

                continue;
            }

            // Skip if key is not present in environment and not required
            if (! isset($env[$key])) {
                continue;
            }

            // Enum validation (in:value1,value2,...)
            if (is_string($rule) && preg_match('/in:([^|]+)/', $rule, $matches)) {
                $allowed = explode(',', $matches[1]);
                if (! in_array($env[$key], $allowed, true)) {
                    $errors[$key][] = $messages["$key.in"] ??
                        "The $key must be one of: ".implode(', ', $allowed);
                }
            }

            // Boolean validation
            if (self::shouldApplyRule($rule, BooleanRule::class)) {
                $booleanRule = new BooleanRule;
                if (! $booleanRule->passes($key, $env[$key])) {
                    $message = $booleanRule->message();
                    $errors[$key][] = $messages["$key.boolean"] ?? (is_array($message) ? implode(', ', $message) : $message);
                }
            }

            // URL validation
            if (self::shouldApplyRule($rule, UrlRule::class)) {
                $urlRule = new UrlRule;
                if (! $urlRule->passes($key, $env[$key])) {
                    $message = $urlRule->message();
                    $errors[$key][] = $messages["$key.url"] ?? (is_array($message) ? implode(', ', $message) : $message);
                }
            }

            // Laravel application key validation
            if (self::shouldApplyRule($rule, KeyRule::class)) {
                $keyRule = new KeyRule;
                if (! $keyRule->passes($key, $env[$key])) {
                    $message = $keyRule->message();
                    $errors[$key][] = $messages["$key.key"] ?? (is_array($message) ? implode(', ', $message) : $message);
                }
            }

            // Custom rule object validation
            if ($rule instanceof Rule && ! $rule->passes($key, $env[$key])) {
                $message = $rule->message(); // No need for method_exists
                $errors[$key][] = $messages["$key.custom"] ??
                    (is_array($message) ? implode(', ', $message) : $message);
            }

            // Handle array of rules
            if (is_array($rule)) {
                foreach ($rule as $singleRule) {
                    if ($singleRule instanceof Rule && ! $singleRule->passes($key, $env[$key])) {
                        $message = $singleRule->message(); // Safe to call directly
                        $errors[$key][] = $messages["$key.custom"] ??
                            (is_array($message) ? implode(', ', $message) : (string) $message);
                    }
                }
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Determine if a rule should be applied.
     *
     * @param  mixed  $rule  The rule to check
     * @param  class-string<Rule>  $ruleClass  The class name of the rule
     */
    private static function shouldApplyRule(mixed $rule, string $ruleClass): bool
    {
        return (is_string($rule) && str_contains($rule, $ruleClass)) ||
               (is_array($rule) && in_array($ruleClass, $rule, true)) ||
               (is_object($rule) && $rule instanceof $ruleClass);
    }
}
