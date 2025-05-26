<?php

namespace Tests\Unit;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\KeyRule;
use EnvValidator\EnvValidator;

test('validateStandalone returns true for valid data', function () {
    $env = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'DATABASE_PORT' => '3306',
    ];

    $rules = [
        'APP_ENV' => 'required|in:local,development,staging,production',
        'APP_DEBUG' => 'required',
        'APP_URL' => 'required|url',
        'DATABASE_PORT' => 'required|integer',
    ];

    $result = EnvValidator::validateStandalone($env, $rules);
    expect($result)->toBeTrue();
});

test('validateStandalone returns errors array for invalid data', function () {
    $env = [
        'APP_ENV' => 'invalid-environment',
        'APP_DEBUG' => 'not-a-boolean',
        'APP_URL' => 'not-a-url',
        'DATABASE_PORT' => 'not-a-number',
    ];

    $rules = [
        'APP_ENV' => 'required|in:local,development,staging,production',
        'APP_DEBUG' => 'required',
        'APP_URL' => 'required|url',
        'DATABASE_PORT' => 'required|integer',
    ];

    $result = EnvValidator::validateStandalone($env, $rules);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_ENV')
        ->and($result['APP_ENV'])->toBeArray()
        ->and($result['APP_ENV'][0])->toContain('must be one of');
});

test('validateStandalone handles required fields correctly', function () {
    $env = [
        'APP_ENV' => 'production',
        // APP_DEBUG is missing
        'APP_URL' => 'https://example.com',
    ];

    $rules = [
        'APP_ENV' => 'required|in:local,development,staging,production',
        'APP_DEBUG' => 'required',
        'APP_URL' => 'required|url',
        'OPTIONAL_VAR' => 'string', // Not required
    ];

    $result = EnvValidator::validateStandalone($env, $rules);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_DEBUG')
        ->and($result['APP_DEBUG'][0])->toContain('required')
        ->and($result)->not->toHaveKey('OPTIONAL_VAR');
});

test('validateStandalone handles boolean rule', function () {
    $env = [
        'BOOL_TRUE' => 'true',
        'BOOL_FALSE' => 'false',
        'BOOL_YES' => 'yes',
        'BOOL_NO' => 'no',
        'BOOL_1' => '1',
        'BOOL_0' => '0',
        'BOOL_ON' => 'on',
        'BOOL_OFF' => 'off',
        'BOOL_INVALID' => 'maybe',
    ];

    $rules = [
        'BOOL_TRUE' => 'required|boolean',
        'BOOL_FALSE' => 'required|boolean',
        'BOOL_YES' => 'required|boolean',
        'BOOL_NO' => 'required|boolean',
        'BOOL_1' => 'required|boolean',
        'BOOL_0' => 'required|boolean',
        'BOOL_ON' => 'required|boolean',
        'BOOL_OFF' => 'required|boolean',
        'BOOL_INVALID' => 'required|boolean',
    ];

    // For standalone mode, we need to adjust our rules to use the class name
    $rules = array_map(function ($rule) {
        return str_replace('boolean', BooleanRule::class, $rule);
    }, $rules);

    $result = EnvValidator::validateStandalone($env, $rules);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('BOOL_INVALID')
        ->and($result['BOOL_INVALID'][0])->toContain('boolean');

    // Remove the invalid boolean and test again
    unset($env['BOOL_INVALID'], $rules['BOOL_INVALID']);

    $result = EnvValidator::validateStandalone($env, $rules);
    expect($result)->toBeTrue();
});

test('validateStandalone handles URL rule', function () {
    $env = [
        'VALID_URL' => 'https://example.com',
        'INVALID_URL' => 'not-a-url',
    ];

    $rules = [
        'VALID_URL' => 'required|'.UrlRule::class,
        'INVALID_URL' => 'required|'.UrlRule::class,
    ];

    $result = EnvValidator::validateStandalone($env, $rules);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('INVALID_URL')
        ->and($result['INVALID_URL'][0])->toContain('valid URL')
        ->and($result)->not->toHaveKey('VALID_URL');
});

test('validateStandalone handles Laravel key rule', function () {
    $env = [
        'VALID_KEY' => 'base64:'.str_repeat('A', 44),
        'INVALID_KEY' => 'not-a-laravel-key',
    ];

    $rules = [
        'VALID_KEY' => 'required|'.KeyRule::class,
        'INVALID_KEY' => 'required|'.KeyRule::class,
    ];

    $result = EnvValidator::validateStandalone($env, $rules);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('INVALID_KEY')
        ->and($result['INVALID_KEY'][0])->toContain('Laravel application key')
        ->and($result)->not->toHaveKey('VALID_KEY');
});

test('validateStandalone handles in: rule correctly', function () {
    $env = [
        'VALID_ENV' => 'production',
        'INVALID_ENV' => 'unknown',
    ];

    $rules = [
        'VALID_ENV' => 'required|in:local,development,staging,production',
        'INVALID_ENV' => 'required|in:local,development,staging,production',
    ];

    $result = EnvValidator::validateStandalone($env, $rules);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('INVALID_ENV')
        ->and($result['INVALID_ENV'][0])->toContain('must be one of')
        ->and($result)->not->toHaveKey('VALID_ENV');
});

test('validateStandalone skips validation for non-required missing fields', function () {
    $env = [
        'REQUIRED_FIELD' => 'value',
        // OPTIONAL_FIELD is missing
    ];

    $rules = [
        'REQUIRED_FIELD' => 'required|string',
        'OPTIONAL_FIELD' => 'string', // Not required
        'ANOTHER_OPTIONAL' => 'integer', // Not required and missing
    ];

    $result = EnvValidator::validateStandalone($env, $rules);

    expect($result)->toBeTrue();
});

test('validateStandalone with custom messages', function () {
    $env = [
        'TEST_VAR' => '', // Required but empty
    ];

    $rules = [
        'TEST_VAR' => 'required|string',
    ];

    $messages = [
        'TEST_VAR.required' => 'Custom error message for TEST_VAR',
    ];

    $result = EnvValidator::validateStandalone($env, $rules, $messages);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('TEST_VAR')
        ->and($result['TEST_VAR'][0])->toBe('Custom error message for TEST_VAR');
    // The message should match our custom message
});
