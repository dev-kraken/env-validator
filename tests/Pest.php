<?php

use EnvValidator\Exceptions\InvalidEnvironmentException;
use EnvValidator\Tests\TestCase;
use Random\RandomException;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/
uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/
expect()->extend('toBeValidEnv', function () {
    try {
        // Invoke the closure (e.g. fn() => $validator->validate($env))
        ($this->value)();

        return $this;
    } catch (Exception $e) {
        throw new RuntimeException('Environment validation failed: '.$e->getMessage());
    }
});

expect()->extend('toThrowValidationException', function (?string $expectedMessage = null) {
    try {
        // Invoke the closure
        ($this->value)();

        // If no exception was thrown, fail the test
        throw new RuntimeException('Expected validation to fail, but it passed.');
    } catch (InvalidEnvironmentException $e) {
        if ($expectedMessage !== null) {
            expect($e->getMessage())->toContain($expectedMessage);
        }

        return $this;
    }
});

/*
|--------------------------------------------------------------------------
| Other Helpers
|--------------------------------------------------------------------------
*/
expect()->extend('toBeValidLaravelKey', function () {
    $pattern = '/^base64:[A-Za-z0-9+\/=]{40,}$/';
    expect(preg_match($pattern, $this->value))->toBe(1, 'Expected a valid Laravel application key');

    return $this;
});

expect()->extend('toBeValidUrl', function () {
    expect(filter_var($this->value, FILTER_VALIDATE_URL))->not->toBeFalse();

    return $this;
});

expect()->extend('toBeBooleanString', function () {
    $booleanValues = ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'];
    expect(in_array(strtolower($this->value), $booleanValues))->toBeTrue();

    return $this;
});

function mockEnvironmentVariables(array $env): array
{
    return $env;
}

/**
 * @throws RandomException
 */
function generateValidLaravelKey(): string
{
    return 'base64:'.base64_encode(random_bytes(32));
}

/**
 * @throws RandomException
 */
function getValidDefaultEnv(): array
{
    return [
        'APP_NAME' => 'Test App',
        'APP_ENV' => 'testing',
        'APP_KEY' => generateValidLaravelKey(),
        'APP_DEBUG' => 'true',
        'APP_URL' => 'http://localhost',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];
}

function getInvalidDefaultEnv(): array
{
    return [
        'APP_NAME' => '',
        'APP_ENV' => 'invalid-env',
        'APP_KEY' => 'invalid-key',
        'APP_DEBUG' => 'not-boolean',
        'APP_URL' => 'not-a-url',
        'APP_LOCALE' => '',
        'APP_FALLBACK_LOCALE' => '',
    ];
}
