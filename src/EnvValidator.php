<?php

declare(strict_types=1);

namespace EnvValidator;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\KeyRule;
use EnvValidator\Core\RuleRegistry;
use EnvValidator\Core\StandaloneValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;
use Illuminate\Contracts\Validation\Rule as ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

/**
 * Validates environment variables against defined rules.
 */
final class EnvValidator
{
    /**
     * Default validation rules for common Laravel environment variables.
     *
     * @var array<string, string|array<int, string|class-string>>
     */
    private array $defaultRules = [
        'APP_NAME' => 'required|string',
        'APP_ENV' => 'required|string|in:local,development,staging,production',
        'APP_KEY' => ['required', 'string', KeyRule::class],
        'APP_DEBUG' => ['required', BooleanRule::class],
        'APP_URL' => ['required', UrlRule::class],
        'APP_LOCALE' => 'required|string',
        'APP_FALLBACK_LOCALE' => 'required|string',
        'APP_FAKER_LOCALE' => 'nullable|string',
    ];

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
        $this->registerBuiltInRules();
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
     * @param  array<mixed>|string  $rules
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
     * Build and return the final set of rules, instantiating any Rule classes.
     *
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        // Load any rules defined in config/env-validator.php
        /** @var array<string, mixed> $configRules */
        $configRules = config('env-validator.rules', []);

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
            // PHP function getenv() returns either an array or false
            $getEnvVars = getenv();
            $envVars = is_array($getEnvVars) ? $getEnvVars : [];

            // Merge from all possible sources
            /** @var array<string, mixed> $mergedEnv */
            $mergedEnv = array_merge($envVars, $_ENV, $_SERVER);
            $env = $mergedEnv;
        }

        $rules = $this->getRules();
        /** @var array<string, string> $configMessages */
        $configMessages = config('env-validator.messages', []);
        $messages = array_merge($configMessages, $this->messages);

        // Create custom attribute names without dots or underscores
        $attributes = [];
        foreach (array_keys($rules) as $key) {
            // Convert APP_ENV to "App Env" for better readability
            $keyStr = is_string($key) ? $key : (string) $key;
            $readableName = strtolower(str_replace(['_', '.'], ' ', $keyStr));
            $attributes[$keyStr] = ucwords($readableName);
        }

        $validator = Validator::make($env, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $errors = Arr::flatten($validator->errors()->toArray());

            $errorMessages = array_map(
                static function (mixed $error): string {
                    // 1) If it’s already a string, clean up whitespace
                    if (is_string($error)) {
                        // preg_replace can return null, so we coalesce to ''
                        return preg_replace('/\s+/', ' ', trim($error)) ?? '';
                    }

                    // 2) If it’s a scalar (int/float/bool) or null
                    if (is_scalar($error) || is_null($error)) {
                        return (string) $error;
                    }

                    // 3) Anything else (array, object, resource, etc.)—fall back to an empty string
                    return '';
                },
                $errors
            );

            $cleanMessage = 'Environment validation failed: '.implode(', ', $errorMessages);

            /** @var array<string, array<int, string>> $errorsArray */
            $errorsArray = $validator->errors()->toArray();

            throw new InvalidEnvironmentException(
                $cleanMessage,
                $errorsArray
            );
        }

        return true;
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
            // PHP function getenv() returns either an array or false
            $getEnvVars = getenv();
            $envVars = is_array($getEnvVars) ? $getEnvVars : [];

            // Merge from all possible sources
            /** @var array<string, mixed> $mergedEnv */
            $mergedEnv = array_merge($envVars, $_ENV, $_SERVER);
            $env = $mergedEnv;
        }

        $rules = array_intersect_key($this->getRules(), array_flip($keys));
        /** @var array<string, string> $configMessages */
        $configMessages = config('env-validator.messages', []);
        $messages = array_merge($configMessages, $this->messages);

        // Create custom attribute names without dots or underscores
        $attributes = [];
        foreach (array_keys($rules) as $key) {
            // Convert APP_ENV to "App Env" for better readability
            $keyStr = is_string($key) ? $key : (string) $key;
            $readableName = strtolower(str_replace(['_', '.'], ' ', $keyStr));
            $attributes[$keyStr] = ucwords($readableName);
        }

        $validator = Validator::make($env, $rules, $messages, $attributes);

        if ($validator->fails()) {
            $errors = Arr::flatten($validator->errors()->toArray());

            $errorMessages = array_map(
                static function (mixed $error): string {
                    // 1) If it’s already a string, clean up whitespace (preg_replace may return null)
                    if (is_string($error)) {
                        return preg_replace('/\s+/', ' ', trim($error)) ?? '';
                    }

                    // 2) If it’s a scalar (bool|int|float|string) or null
                    if (is_scalar($error) || is_null($error)) {
                        return (string) $error;
                    }

                    // 3) Otherwise (array, object, resource…), return an empty string
                    return '';
                },
                $errors
            );

            // Create a cleaner message for console context
            $cleanMessage = 'Environment validation failed: '.implode(', ', $errorMessages);

            // Create a detailed exception for application context
            /** @var array<string, array<int, string>> $errorsArray */
            $errorsArray = $validator->errors()->toArray();
            throw new InvalidEnvironmentException(
                $cleanMessage,
                $errorsArray
            );
        }

        return true;
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
}
