<?php

namespace Tests\Unit;

use EnvValidator\Collections\NumericRules\NumericRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\PatternRule;
use EnvValidator\EnvValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;
use Illuminate\Support\Facades\Config;

// Mock Laravel Config facade
function config($key, $default = null)
{
    return Config::get($key, $default);
}

// Test basic validation functionality
test('it validates valid environment variables', function () {
    $validator = new EnvValidator;

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

    expect($validator->validate($env))->toBeTrue();
});

test('it fails validation with invalid environment variables', function () {
    $validator = new EnvValidator;

    $env = [
        'APP_NAME' => 'Testing App',
        'APP_ENV' => 'invalid-env', // Invalid value
        'APP_KEY' => 'not-a-valid-key',
        'APP_DEBUG' => 'not-a-boolean',
        'APP_URL' => 'not-a-url',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
    ];

    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);
});

test('it validates only specified environment variables', function () {
    $validator = new EnvValidator;

    $env = [
        'APP_NAME' => 'Testing App',
        'APP_ENV' => 'invalid-env', // Invalid but not checked
        'APP_KEY' => 'base64:'.str_repeat('A', 44), // Valid
        'APP_DEBUG' => 'false', // Valid
    ];

    // Should pass because we're only validating APP_NAME, APP_KEY and APP_DEBUG
    expect($validator->validateOnly(['APP_NAME', 'APP_KEY', 'APP_DEBUG'], $env))->toBeTrue();
});

test('it allows adding custom rules', function () {
    $validator = new EnvValidator;

    $validator->addRule('CUSTOM_VAR', 'required|integer|min:10');

    $env = [
        'APP_NAME' => 'Testing App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'CUSTOM_VAR' => '5', // Too small
    ];

    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);

    $env['CUSTOM_VAR'] = '15'; // Valid value

    expect($validator->validate($env))->toBeTrue();
});

test('it allows setting multiple custom rules at once', function () {
    $validator = new EnvValidator;

    $customRules = [
        'CUSTOM_VAR_1' => 'required|string',
        'CUSTOM_VAR_2' => 'required|integer',
    ];

    // (1) Replace defaults with our custom rules
    $validator->setRules($customRules);
    expect($validator->getRules())->toBe($customRules);

    // (2) Now merge custom rules into defaults on a fresh instance
    $validator = new EnvValidator;
    $validator
        ->addRule('CUSTOM_VAR_1', 'required|string')
        ->addRule('CUSTOM_VAR_2', 'required|integer');

    $allRules = $validator->getRules();

    expect($allRules)
        ->toHaveKey('APP_NAME')        // default still present
        ->and($allRules)->toHaveKey('CUSTOM_VAR_1')
        ->and($allRules)->toHaveKey('CUSTOM_VAR_2');
});

test('it allows setting custom error messages', function () {
    $validator = new EnvValidator;

    $customMessages = [
        'APP_KEY.required' => 'Custom message: The application key is missing',
    ];

    $validator->setMessages($customMessages);

    $env = [
        'APP_NAME' => 'Testing App',
        'APP_ENV' => 'production',
        // APP_KEY is missing
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
    ];

    expect(static fn () => $validator->validate($env))
        ->toThrowValidationException('Custom message');
});

// Test standalone validation without Laravel
test('it validates environment in standalone mode', function () {
    $env = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
    ];

    $rules = [
        'APP_ENV' => 'required|in:local,development,staging,production',
        'APP_DEBUG' => 'required',
        'APP_URL' => 'required|url',
    ];

    $result = EnvValidator::validateStandalone($env, $rules);
    expect($result)->toBeTrue();

    // Test with invalid data
    $env['APP_ENV'] = 'invalid-env';
    $result = EnvValidator::validateStandalone($env, $rules);
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('APP_ENV');
});

// Test NumericRule
test('it validates numeric values with NumericRule', function () {
    $validator = new EnvValidator;

    // Test without constraints
    $validator->setRules([
        'NUMERIC_VAR' => [new NumericRule],
    ]);

    $env = ['NUMERIC_VAR' => '123.45'];
    expect($validator->validate($env))->toBeTrue();

    $env = ['NUMERIC_VAR' => 'not-a-number'];
    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);

    // Test with integer constraint
    $validator->setRules([
        'INTEGER_VAR' => [new NumericRule(allowDecimals: false)],
    ]);

    $env = ['INTEGER_VAR' => '123'];
    expect($validator->validate($env))->toBeTrue();

    $env = ['INTEGER_VAR' => '123.45'];
    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);

    // Test with range constraint
    $validator->setRules([
        'RANGE_VAR' => [new NumericRule(min: 1, max: 100)],
    ]);

    $env = ['RANGE_VAR' => '50'];
    expect($validator->validate($env))->toBeTrue();

    $env = ['RANGE_VAR' => '101'];
    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);

    $env = ['RANGE_VAR' => '0'];
    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);
});

// Test PatternRule
test('it validates patterns with PatternRule', function () {
    $validator = new EnvValidator;

    // Test IP address pattern
    $validator->setRules([
        'IP_VAR' => [new PatternRule('/^(\d{1,3}\.){3}\d{1,3}$/', 'The IP address format is invalid.')],
    ]);

    $env = ['IP_VAR' => '192.168.1.1'];
    expect($validator->validate($env))->toBeTrue();

    $env = ['IP_VAR' => 'not-an-ip'];
    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);

    // Test with custom message
    $customMessage = 'Please enter a valid IP address (e.g., 192.168.1.1)';
    $validator->setRules([
        'IP_VAR' => [new PatternRule('/^(\d{1,3}\.){3}\d{1,3}$/', $customMessage)],
    ]);

    $env = ['IP_VAR' => 'not-an-ip'];

    try {
        $validator->validate($env);
    } catch (InvalidEnvironmentException $e) {
        expect($e->getMessage())->toContain($customMessage);
    }
});

// Test BooleanRule with various formats
test('it validates boolean values in different formats', function () {
    $validator = new EnvValidator;

    $validator->setRules([
        'BOOL_VAR' => [new BooleanRule],
    ]);

    $validValues = [
        'true', 'false',
        'yes', 'no',
        'on', 'off',
        '1', '0',
        true, false,
        1, 0,
    ];

    foreach ($validValues as $value) {
        $env = ['BOOL_VAR' => $value];
        expect($validator->validate($env))->toBeTrue();
    }

    $invalidValues = [
        'invalid', '2', 'truthy', 'falsey', 'enabled', 'disabled',
    ];

    foreach ($invalidValues as $value) {
        $env = ['BOOL_VAR' => $value];
        expect(fn () => $validator->validate($env))
            ->toThrow(InvalidEnvironmentException::class);
    }
});
