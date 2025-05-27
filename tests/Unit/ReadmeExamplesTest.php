<?php

namespace Tests\Unit;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\InRule;
use EnvValidator\EnvValidator;

test('README basic standalone example works correctly', function () {
    // This is the exact code from README Basic Example (String Rules)
    $rules = [
        'APP_ENV' => 'required|string',
        'APP_DEBUG' => 'required|boolean',
        'APP_URL' => 'required|url',
        'DB_HOST' => 'required|string',
        'DB_PASSWORD' => 'required|string',
    ];

    // Test with valid environment
    $validEnv = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'DB_HOST' => 'localhost',
        'DB_PASSWORD' => 'securepassword',
    ];

    $result = EnvValidator::validateStandalone($validEnv, $rules);
    expect($result)->toBeTrue();

    // Test with missing required fields
    $invalidEnv = [
        'APP_ENV' => 'production',
        // Missing APP_DEBUG, APP_URL, DB_HOST, DB_PASSWORD
    ];

    $result = EnvValidator::validateStandalone($invalidEnv, $rules);
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_DEBUG')
        ->and($result)->toHaveKey('APP_URL')
        ->and($result)->toHaveKey('DB_HOST')
        ->and($result)->toHaveKey('DB_PASSWORD')
        ->and($result['APP_DEBUG'][0])->toContain('required')
        ->and($result['APP_URL'][0])->toContain('required')
        ->and($result['DB_HOST'][0])->toContain('required')
        ->and($result['DB_PASSWORD'][0])->toContain('required');
});

test('README advanced standalone example works correctly', function () {
    // This is the exact code from README Advanced Example (Rule Objects)
    $validator = new EnvValidator;
    $validator->setRules([
        'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])],
        'APP_DEBUG' => ['required', new BooleanRule],
        'APP_URL' => ['required', new UrlRule],
    ]);

    // Test with valid environment
    $validEnv = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
    ];

    $result = EnvValidator::validateStandalone($validEnv, $validator->getRules());
    expect($result)->toBeTrue();

    // Test with missing required fields
    $invalidEnv = [
        'APP_ENV' => 'staging',
        // Missing APP_DEBUG and APP_URL
    ];

    $result = EnvValidator::validateStandalone($invalidEnv, $validator->getRules());
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_DEBUG')
        ->and($result)->toHaveKey('APP_URL')
        ->and($result['APP_DEBUG'][0])->toContain('required')
        ->and($result['APP_URL'][0])->toContain('required');

    // Test with invalid values that pass required but fail other rules
    $invalidValuesEnv = [
        'APP_ENV' => 'invalid-env', // Not in allowed list
        'APP_DEBUG' => 'not-boolean', // Invalid boolean
        'APP_URL' => 'not-a-url', // Invalid URL
    ];

    $result = EnvValidator::validateStandalone($invalidValuesEnv, $validator->getRules());
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_ENV')
        ->and($result)->toHaveKey('APP_DEBUG')
        ->and($result)->toHaveKey('APP_URL')
        ->and($result['APP_ENV'][0])->toContain('must be one of')
        ->and($result['APP_DEBUG'][0])->toContain('boolean')
        ->and($result['APP_URL'][0])->toContain('valid URL');
});

test('README required field validation section examples work correctly', function () {
    // This tests the examples from the "Required Field Validation" section
    $rules = [
        'APP_KEY' => ['required', 'string'],                 // Array format (recommended)
        'APP_ENV' => 'required|string',                       // String format
        'DB_PASSWORD' => ['required'],                        // Required only
        'API_URL' => ['string', 'required', new UrlRule],  // Required anywhere in array
    ];

    // Test with missing required fields
    $emptyEnv = [];

    $result = EnvValidator::validateStandalone($emptyEnv, $rules);
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_KEY')
        ->and($result)->toHaveKey('APP_ENV')
        ->and($result)->toHaveKey('DB_PASSWORD')
        ->and($result)->toHaveKey('API_URL')
        ->and($result['APP_KEY'][0])->toContain('required')
        ->and($result['APP_ENV'][0])->toContain('required')
        ->and($result['DB_PASSWORD'][0])->toContain('required')
        ->and($result['API_URL'][0])->toContain('required');

    // Test with empty string values (should fail required validation)
    $emptyStringEnv = [
        'APP_KEY' => '',
        'APP_ENV' => '',
        'DB_PASSWORD' => '',
        'API_URL' => '',
    ];

    $result = EnvValidator::validateStandalone($emptyStringEnv, $rules);
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_KEY')
        ->and($result)->toHaveKey('APP_ENV')
        ->and($result)->toHaveKey('DB_PASSWORD')
        ->and($result)->toHaveKey('API_URL');

    // Test with valid values
    $validEnv = [
        'APP_KEY' => str_repeat('a', 32), // 32 character string
        'APP_ENV' => 'production',
        'DB_PASSWORD' => 'secret123',
        'API_URL' => 'https://api.example.com',
    ];

    $result = EnvValidator::validateStandalone($validEnv, $rules);
    expect($result)->toBeTrue();
});

test('README examples handle mixed required and optional fields correctly', function () {
    // Testing mixed scenarios similar to what's shown in the README
    $rules = [
        'REQUIRED_FIELD' => ['required', 'string'],
        'OPTIONAL_FIELD' => ['nullable', 'string'],
        'ANOTHER_OPTIONAL' => ['string'], // Not required
        'REQUIRED_WITH_RULE' => ['required', new BooleanRule],
    ];

    $partialEnv = [
        'REQUIRED_FIELD' => 'present',
        'REQUIRED_WITH_RULE' => 'true',
        // Optional fields are missing - this should be OK
    ];

    $result = EnvValidator::validateStandalone($partialEnv, $rules);
    expect($result)->toBeTrue();

    // Test when required fields are missing
    $missingRequiredEnv = [
        'OPTIONAL_FIELD' => 'optional value',
        'ANOTHER_OPTIONAL' => 'another optional value',
        // Required fields are missing
    ];

    $result = EnvValidator::validateStandalone($missingRequiredEnv, $rules);
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('REQUIRED_FIELD')
        ->and($result)->toHaveKey('REQUIRED_WITH_RULE')
        ->and($result)->not()->toHaveKey('OPTIONAL_FIELD')
        ->and($result)->not()->toHaveKey('ANOTHER_OPTIONAL');
});

test('README examples work with custom error messages', function () {
    // Test the custom error message example from the README
    $rules = [
        'APP_KEY' => ['required', 'string'],
        'DB_PASSWORD' => ['required', 'string'],
    ];

    $customMessages = [
        'APP_KEY.required' => 'APP_KEY is required! Generate one with: php artisan key:generate',
        'DB_PASSWORD.required' => 'DB_PASSWORD is required for database security.',
    ];

    $emptyEnv = [];

    $result = EnvValidator::validateStandalone($emptyEnv, $rules, $customMessages);
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_KEY')
        ->and($result)->toHaveKey('DB_PASSWORD')
        ->and($result['APP_KEY'][0])->toBe('APP_KEY is required! Generate one with: php artisan key:generate')
        ->and($result['DB_PASSWORD'][0])->toBe('DB_PASSWORD is required for database security.');
});
