<?php

use EnvValidator\Exceptions\InvalidEnvironmentException;
use EnvValidator\Facades\EnvValidator;
use Illuminate\Support\Facades\Config;

test('the service provider registers the validator', function () {
    expect(app('env-validator'))->toBeInstanceOf(\EnvValidator\EnvValidator::class);
});

test('the facade works', function () {
    $env = [
        'APP_NAME' => 'Testing App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];

    expect(EnvValidator::validate($env))->toBeTrue();
});

test('validator is configured from config file', function () {
    // Setup custom rules in the config
    Config::set('env-validator.rules', [
        'CUSTOM_CONFIG_VAR' => 'required|string',
    ]);

    // Create a new instance (it should load the config)
    $validator = app('env-validator');

    // Check that rules from config were loaded
    $rules = $validator->getRules();
    expect($rules)->toHaveKey('CUSTOM_CONFIG_VAR')
        ->and($rules['CUSTOM_CONFIG_VAR'])->toBe('required|string');
});

test('validator merges config rules with default rules', function () {
    // Setup custom rules that override a default rule
    Config::set('env-validator.rules', [
        'APP_URL' => 'required|string|starts_with:https', // Override the default rule
        'CUSTOM_VAR' => 'required|string', // Add a new rule
    ]);

    // Create new instance to load config
    $validator = app('env-validator');
    $rules = $validator->getRules();

    // Original rules should still exist
    expect($rules)->toHaveKey('APP_NAME')
        ->and($rules)->toHaveKey('APP_KEY')
        ->and($rules)->toHaveKey('CUSTOM_VAR')
        ->and($rules)->toHaveKey('APP_URL')
        ->and($rules['APP_URL'])->toBe('required|string|starts_with:https');

    // Custom rule should be added

    // Overridden rule should have new value
});

test('the facade allows fluent method chaining', function () {
    $result = EnvValidator::addRule('TEST_VAR', 'required')
        ->addRule('ANOTHER_VAR', 'string')
        ->setMessages(['TEST_VAR.required' => 'Test var is required']);

    // The fluent methods should return the validator instance
    expect($result)->toBeInstanceOf(\EnvValidator\EnvValidator::class);

    // Check that rules were actually added
    $rules = $result->getRules();
    expect($rules)->toHaveKey('TEST_VAR')
        ->and($rules)->toHaveKey('ANOTHER_VAR');
});

test('validator works with dependency injection', function () {
    // This test simulates how the validator would be used in a controller

    $this->app->singleton('test.controller', function ($app) {
        return new class($app->make(\EnvValidator\EnvValidator::class))
        {
            protected \EnvValidator\EnvValidator $validator;

            public function __construct(\EnvValidator\EnvValidator $validator)
            {
                $this->validator = $validator;
            }

            public function validate(array $env)
            {
                return $this->validator->validate($env);
            }
        };
    });

    $controller = app('test.controller');

    $env = [
        'APP_NAME' => 'Testing App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];

    expect($controller->validate($env))->toBeTrue();
});

test('validator throws appropriate exception with invalid input', function () {
    $env = [
        'APP_NAME' => 'Testing App',
        'APP_ENV' => 'invalid-environment',
        'APP_DEBUG' => 'not-boolean',
    ];

    expect(fn () => EnvValidator::validate($env))
        ->toThrow(InvalidEnvironmentException::class);
});

test('validateOnly method works as expected', function () {
    $env = [
        'APP_NAME' => 'Testing App', // Valid
        'APP_ENV' => 'invalid-value', // Invalid but not checked
        'APP_DEBUG' => 'true', // Valid
    ];

    // Validate only APP_NAME and APP_DEBUG
    expect(EnvValidator::validateOnly(['APP_NAME', 'APP_DEBUG'], $env))->toBeTrue()
        ->and(/**
         * @throws InvalidEnvironmentException
         */ fn () => EnvValidator::validateOnly(['APP_ENV'], $env))
        ->toThrow(InvalidEnvironmentException::class);

    // Validate APP_ENV which is invalid
});
