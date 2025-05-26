<?php

test('env:validate command exists', function () {
    $this->artisan('env:validate --help')
        ->assertExitCode(0);
});

test('env:validate passes with valid environment', function () {
    // Setup valid environment variables
    $env = [
        'APP_NAME' => 'Test App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];

    // Set environment variables for the command
    foreach ($env as $key => $value) {
        $this->app['config']->set("env.{$key}", $value);
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    // Run the command
    $this->artisan('env:validate')
        ->expectsOutput('All environment variables are valid.')
        ->assertExitCode(0);
});

test('env:validate fails with invalid environment variables', function () {
    // Setup invalid environment variables
    $env = [
        'APP_NAME' => 'Test App',
        'APP_ENV' => 'invalid-value', // Invalid
        'APP_KEY' => 'not-a-valid-key', // Invalid
        'APP_DEBUG' => 'true',
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
    ];

    // Set environment variables for the command
    foreach ($env as $key => $value) {
        $this->app['config']->set("env.{$key}", $value);
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    // Run the command
    $this->artisan('env:validate')
        ->expectsOutput('Environment validation failed!')
        ->assertExitCode(1);
});

test('env:validate can validate specific keys', function () {
    // Setup environment with some valid and some invalid variables
    $env = [
        'APP_NAME' => 'Test App', // Valid
        'APP_ENV' => 'invalid-value', // Invalid
        'APP_KEY' => 'base64:'.str_repeat('A', 44), // Valid
        'APP_DEBUG' => 'not-boolean', // Invalid
        'APP_URL' => 'https://example.com', // Valid
    ];

    // Set environment variables for the command
    foreach ($env as $key => $value) {
        $this->app['config']->set("env.{$key}", $value);
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    // Test validating only valid keys
    $this->artisan('env:validate --keys=APP_NAME --keys=APP_KEY --keys=APP_URL')
        ->expectsOutput('Specified environment variables are valid.')
        ->assertExitCode(0);

    // Test validating only invalid keys
    $this->artisan('env:validate --keys=APP_ENV --keys=APP_DEBUG')
        ->assertExitCode(1);
});

test('env:validate command handles missing environment variables', function () {
    // Clear any existing environment variables
    foreach (['APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL', 'APP_LOCALE', 'APP_FALLBACK_LOCALE'] as $key) {
        unset($_ENV[$key]);
        putenv($key);
    }

    // Run the command with missing required variables
    $this->artisan('env:validate')
        ->assertExitCode(1);
});

test('env:validate provides detailed error messages', function () {
    // Setup environment with specific validation errors
    $env = [
        'APP_NAME' => '', // Required but empty
        'APP_ENV' => 'staging', // Valid
        'APP_KEY' => 'invalid-key', // Invalid format
        'APP_DEBUG' => 'true',
        'APP_URL' => 'not-a-url', // Invalid URL
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
    ];

    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    $this->artisan('env:validate')
        ->expectsOutput('Environment validation failed!')
        ->assertExitCode(1);
});
