<?php

declare(strict_types=1);

namespace EnvValidator;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\InRule;
use EnvValidator\Collections\StringRules\KeyRule;
use EnvValidator\Core\DefaultRulePresets;
use EnvValidator\Core\RuleRegistry;
use EnvValidator\Core\StandaloneValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Validates environment variables against defined rules.
 */
final class EnvValidator
{
    /**
     * Default validation rules for common Laravel environment variables.
     *
     * @var array<string, string|array<int, string|object>>
     */
    private array $defaultRules;

    /**
     * Custom validation rules provided at runtime.
     *
     * @var array<string, mixed>
     */
    private array $customRules = [];

    /**
     * Custom messages for validation errors.
     *
     * @var array<string, string>
     */
    private array $messages = [];

    /**
     * Whether to replace default rules entirely.
     */
    private bool $replaceDefaults = false;

    /**
     * Rule registry for organizing validation rules.
     */
    private RuleRegistry $ruleRegistry;

    /**
     * Create a new EnvValidator instance.
     */
    public function __construct()
    {
        $this->ruleRegistry = new RuleRegistry;
        $this->defaultRules = DefaultRulePresets::laravel();
        $this->registerBuiltInRules();
    }

    /**
     * Get minimal application rules (only the most essential variables).
     *
     * @return array<string, string|array<int, string|object>>
     */
    public function getMinimalRules(): array
    {
        return DefaultRulePresets::minimal();
    }

    /**
     * Use only essential environment rules.
     */
    public function useMinimalRules(): self
    {
        $this->defaultRules = DefaultRulePresets::minimal();
        $this->replaceDefaults = false;

        return $this;
    }

    /**
     * Use full Laravel default rules.
     */
    public function useFullRules(): self
    {
        $this->defaultRules = DefaultRulePresets::laravel();
        $this->replaceDefaults = false;

        return $this;
    }

    /**
     * Use production-optimized rules.
     */
    public function useProductionRules(): self
    {
        $this->defaultRules = DefaultRulePresets::production();
        $this->replaceDefaults = false;

        return $this;
    }

    /**
     * Use API-focused rules.
     */
    public function useApiRules(): self
    {
        $this->defaultRules = DefaultRulePresets::api();
        $this->replaceDefaults = false;

        return $this;
    }

    /**
     * Use a specific rule preset.
     *
     * @param  string  $preset  The preset name (laravel, minimal, production, api)
     */
    public function usePreset(string $preset): self
    {
        $this->defaultRules = match ($preset) {
            'laravel' => DefaultRulePresets::laravel(),
            'minimal' => DefaultRulePresets::minimal(),
            'production' => DefaultRulePresets::production(),
            'api' => DefaultRulePresets::api(),
            'application' => DefaultRulePresets::application(),
            'localization' => DefaultRulePresets::localization(),
            default => throw new InvalidArgumentException("Unknown preset: $preset"),
        };

        $this->replaceDefaults = false;

        return $this;
    }

    /**
     * Register built-in validation rules.
     */
    private function registerBuiltInRules(): void
    {
        // Register network rules
        $this->ruleRegistry->register('network', 'url', UrlRule::class);

        // Register string rules
        $this->ruleRegistry->register('string', 'boolean', BooleanRule::class);
        $this->ruleRegistry->register('string', 'key', KeyRule::class);
        $this->ruleRegistry->register('string', 'in', InRule::class);
    }

    /**
     * Replace the default rules with the given ones.
     *
     * @param  array<string, mixed>  $rules
     */
    public function setRules(array $rules): self
    {
        $this->customRules = $rules;
        $this->replaceDefaults = true;

        return $this;
    }

    /**
     * Add or override a single rule, merging with defaults.
     *
     * @param  array<int, string|object>|string  $rules
     */
    public function addRule(string $key, array|string $rules): self
    {
        $this->customRules[$key] = $rules;
        $this->replaceDefaults = false;

        return $this;
    }

    /**
     * Set custom error messages.
     *
     * @param  array<string, string>  $messages
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Get the current custom messages.
     *
     * @return array<string, string>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Build and return the final set of rules, instantiating any Rule classes.
     *
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        // Load any rules defined in config/env-validator.php (only if in Laravel context)
        /** @var array<string, mixed> $configRules */
        $configRules = [];
        try {
            if (function_exists('config') && class_exists(Application::class)) {
                /** @var mixed $rawConfigRules */
                $rawConfigRules = config('env-validator.rules', []);
                if (is_array($rawConfigRules)) {
                    $configRules = $rawConfigRules;
                }
            }
        } catch (Exception) {
            // Ignore config loading errors in non-Laravel contexts
        }

        // Determine base rules: either only custom or merge defaults + config + custom
        /** @var array<string, mixed> $raw */
        $raw = $this->replaceDefaults
            ? $this->customRules
            : array_merge($this->defaultRules, $configRules, $this->customRules);

        // Instantiate rule class names into objects
        foreach ($raw as $key => $rules) {
            // normalize key to a string
            $keyStr = is_string($key) ? $key : (string) $key;

            if (is_array($rules)) {
                foreach ($rules as $i => $rule) {
                    if (
                        is_string($rule)
                        && class_exists($rule)
                        && is_subclass_of($rule, ValidationRule::class)
                    ) {
                        $iStr = is_int($i) ? $i : 0;
                        $rules[$iStr] = new $rule;
                    }
                }
                // assign the *entire* cleaned array back
                $raw[$keyStr] = $rules;

            } elseif (
                is_string($rules)
                && class_exists($rules)
                && is_subclass_of($rules, ValidationRule::class)
            ) {
                // single-rule case
                $raw[$keyStr] = new $rules;
            }
            // otherwise leave $raw[$keyStr] alone
        }

        return $raw;
    }

    /**
     * Validate the environment variables.
     *
     * @param  array<string, mixed>|null  $env
     *
     * @throws InvalidEnvironmentException
     */
    public function validate(?array $env = null): bool
    {
        // Merge all possible environment sources if not explicitly provided
        if ($env === null) {
            $env = $this->mergeEnvironmentSources();
        }

        $rules = $this->getRules();

        return $this->performValidation($rules, $env);
    }

    /**
     * Validate only specified environment keys.
     *
     * @param  array<int, string>  $keys
     * @param  array<string, mixed>|null  $env
     *
     * @throws InvalidEnvironmentException
     */
    public function validateOnly(array $keys, ?array $env = null): bool
    {
        // Merge all possible environment sources if not explicitly provided
        if ($env === null) {
            $env = $this->mergeEnvironmentSources();
        }

        $rules = array_intersect_key($this->getRules(), array_flip($keys));

        return $this->performValidation($rules, $env);
    }

    /**
     * Standalone validation for non-Laravel usage.
     *
     * @param  array<string, mixed>  $env
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return bool|array<string, array<int, string>>
     */
    public static function validateStandalone(array $env, array $rules, array $messages = []): bool|array
    {
        return StandaloneValidator::validate($env, $rules, $messages);
    }

    /**
     * Merge environment variables from all available sources.
     *
     * @return array<string, mixed>
     */
    private function mergeEnvironmentSources(): array
    {
        // PHP function getenv() returns either an array or false
        $getEnvVars = getenv();
        $envVars = is_array($getEnvVars) ? $getEnvVars : [];

        // Merge from all possible sources
        /** @var array<string, mixed> $merged */
        $merged = array_merge($envVars, $_ENV, $_SERVER);

        return $merged;
    }

    /**
     * Get configuration messages merged with instance messages.
     *
     * @return array<string, string>
     */
    private function getConfigMessages(): array
    {
        /** @var array<string, string> $configMessages */
        $configMessages = [];
        try {
            /** @var mixed $rawConfigMessages */
            $rawConfigMessages = config('env-validator.messages', []);

            if (is_array($rawConfigMessages)) {
                // Ensure all values are strings
                /** @var array<string, string> $configMessages */
                $configMessages = array_filter(
                    $rawConfigMessages,
                    static fn ($value, $key): bool => is_string($key) && is_string($value),
                    ARRAY_FILTER_USE_BOTH
                );
            }
        } catch (Exception) {
            // no need to reassign—$configMessages is already []
        }

        return array_merge($configMessages, $this->messages);
    }

    /**
     * Create readable attribute names from rule keys.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, string>
     */
    private function createReadableAttributes(array $rules): array
    {
        $attributes = [];

        foreach (array_keys($rules) as $key) {
            // Convert APP_ENV to "App Env" for better readability
            $keyStr = is_string($key) ? $key : (string) $key;
            $readableName = strtolower(str_replace(['_', '.'], ' ', $keyStr));
            $attributes[$keyStr] = ucwords($readableName);
        }

        return $attributes;
    }

    /**
     * Process validation errors into clean string messages.
     *
     * @param  array<mixed>  $errors
     * @return array<string>
     */
    private function processErrorMessages(array $errors): array
    {
        return array_map(
            static function (mixed $error): string {
                // 1) If it's already a string, clean up whitespace
                if (is_string($error)) {
                    // preg_replace can return null, so we coalesce to ''
                    return preg_replace('/\s+/', ' ', trim($error)) ?? '';
                }

                // 2) If it's a scalar (int/float/bool) or null
                if (is_scalar($error) || is_null($error)) {
                    return (string) $error;
                }

                // 3) Anything else (array, object, resource, etc.)—fall back to an empty string
                return '';
            },
            $errors
        );
    }

    /**
     * Create a clean validation failure message.
     *
     * @param  array<string>  $errorMessages
     */
    private function createCleanMessage(array $errorMessages): string
    {
        return 'Environment validation failed: '.implode(', ', $errorMessages);
    }

    /**
     * Perform the actual validation using Laravel's validator.
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, mixed>  $env
     * @return true
     *
     * @throws InvalidEnvironmentException
     */
    private function performValidation(array $rules, array $env): bool
    {
        $messages = $this->getConfigMessages();
        $attributes = $this->createReadableAttributes($rules);

        $validator = Validator::make($env, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $errors = Arr::flatten($validator->errors()->toArray());
            $errorMessages = $this->processErrorMessages($errors);
            $cleanMessage = $this->createCleanMessage($errorMessages);

            /** @var array<string, array<int, string>> $errorsArray */
            $errorsArray = $validator->errors()->toArray();

            throw new InvalidEnvironmentException(
                $cleanMessage,
                $errorsArray
            );
        }

        return true;
    }
}
