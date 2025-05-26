<?php

use Dotenv\Dotenv;
use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\EnvValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;
use EnvValidator\Facades\EnvValidator as EnvValidatorFacade;
use Illuminate\Support\Facades\Config;
use Random\RandomException;

test('complete integration test with all features', function () {
    // Step 1: Set up configuration
    Config::set('env-validator.rules', [
        'CUSTOM_DATABASE_HOST' => 'required|string',
        'CUSTOM_DATABASE_PORT' => 'required|integer|between:1,65535',
    ]);

    Config::set('env-validator.messages', [
        'APP_KEY.required' => 'Application key is missing! Run php artisan key:generate',
        'CUSTOM_DATABASE_PORT.between' => 'Database port must be between 1 and 65535',
    ]);

    // Step 2: Create environment data
    $validEnv = [
        // Default Laravel variables
        'APP_NAME' => 'Integration Test App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('A', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://integration-test.example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',

        // Custom variables from config
        'CUSTOM_DATABASE_HOST' => 'db.example.com',
        'CUSTOM_DATABASE_PORT' => '3306',
    ];

    // Step 3: Test via facade
    expect(EnvValidatorFacade::validate($validEnv))->toBeTrue();

    // Step 4: Test via dependency injection
    $validator = app(EnvValidator::class);
    expect($validator->validate($validEnv))->toBeTrue();

    // Step 5: Test with additional runtime rules
    $validator->addRule('RUNTIME_VAR', 'required|string|min:5');

    $envWithRuntime = array_merge($validEnv, [
        'RUNTIME_VAR' => 'valid-runtime-value',
    ]);

    expect($validator->validate($envWithRuntime))->toBeTrue();

    // Step 6: Test validation failure with custom messages
    $invalidEnv = $validEnv;
    unset($invalidEnv['APP_KEY']); // Remove required field
    $invalidEnv['CUSTOM_DATABASE_PORT'] = '99999'; // Invalid port

    try {
        EnvValidatorFacade::validate($invalidEnv);

        throw new RuntimeException('Expected validation to fail');
    } catch (InvalidEnvironmentException $e) {
        expect($e->getMessage())->toContain('Application key is missing')
            ->and($e->getMessage())->toContain('Database port must be between');
    }
});

test('validates only specific environment variables in production scenario', function () {
    // Simulate a production deployment scenario where we only want to validate
    // critical variables for a specific service

    $env = [
        'APP_NAME' => 'Production App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('A', 32)),
        'APP_DEBUG' => 'false',  // Valid
        'APP_URL' => 'https://prod.example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',

        // Critical production variables
        'DATABASE_URL' => 'mysql://user:pass@host:3306/dbname',
        'REDIS_URL' => 'redis://localhost:6379',
        'CACHE_DRIVER' => 'redis',

        // This would be invalid if we validated it
        'SOME_OPTIONAL_VAR' => 'invalid-format-that-would-fail',
    ];

    // Add validation rules for production-critical variables
    EnvValidatorFacade::addRule('DATABASE_URL', 'required|string|starts_with:mysql://')
        ->addRule('REDIS_URL', 'required|string|starts_with:redis://')
        ->addRule('CACHE_DRIVER', 'required|in:redis,memcached');

    // Validate only the critical variables (ignoring SOME_OPTIONAL_VAR)
    $criticalVars = ['APP_ENV', 'APP_KEY', 'DATABASE_URL', 'REDIS_URL', 'CACHE_DRIVER'];

    expect(EnvValidatorFacade::validateOnly($criticalVars, $env))->toBeTrue();
});

test(/**
 * @throws RandomException
 * @throws InvalidEnvironmentException
 */ /**
 * @throws RandomException
 * @throws InvalidEnvironmentException
 */ 'handles complex real-world environment configuration', function () {
    // Test a complex environment similar to what you might find in a real Laravel app

    $realWorldEnv = [
        // App basics
        'APP_NAME' => 'My Laravel App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://myapp.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_TIMEZONE' => 'UTC',

        // Database
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_DATABASE' => 'myapp_production',
        'DB_USERNAME' => 'myapp_user',
        'DB_PASSWORD' => 'secure_password_123',

        // Cache & Session
        'CACHE_DRIVER' => 'redis',
        'SESSION_DRIVER' => 'redis',
        'SESSION_LIFETIME' => '120',
        'REDIS_HOST' => '127.0.0.1',
        'REDIS_PASSWORD' => 'redis_password',
        'REDIS_PORT' => '6379',

        // Queue
        'QUEUE_CONNECTION' => 'redis',

        // Mail
        'MAIL_MAILER' => 'smtp',
        'MAIL_HOST' => 'smtp.mailtrap.io',
        'MAIL_PORT' => '587',
        'MAIL_USERNAME' => 'mail_username',
        'MAIL_PASSWORD' => 'mail_password',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_FROM_ADDRESS' => 'noreply@myapp.com',
        'MAIL_FROM_NAME' => 'My Laravel App',

        // AWS
        'AWS_ACCESS_KEY_ID' => 'AKIAIOSFODNN7EXAMPLE',
        'AWS_SECRET_ACCESS_KEY' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
        'AWS_DEFAULT_REGION' => 'us-east-1',
        'AWS_BUCKET' => 'my-app-uploads',

        // Custom app variables
        'API_RATE_LIMIT' => '1000',
        'FEATURE_FLAG_NEW_UI' => 'true',
        'EXTERNAL_API_URL' => 'https://api.external-service.com',
        'EXTERNAL_API_KEY' => 'ext_api_key_12345',
    ];

    // Add comprehensive validation rules
    $validator = app(EnvValidator::class);

    $validator->setRules([
        // Override/extend default rules
        'APP_TIMEZONE' => 'required|string',

        // Database rules
        'DB_CONNECTION' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
        'DB_HOST' => 'required|string',
        'DB_PORT' => 'required|integer|between:1,65535',
        'DB_DATABASE' => 'required|string|min:1',
        'DB_USERNAME' => 'required|string|min:1',
        'DB_PASSWORD' => 'required|string|min:8',

        // Cache & Session
        'CACHE_DRIVER' => 'required|in:file,database,redis,memcached',
        'SESSION_DRIVER' => 'required|in:file,cookie,database,redis,memcached',
        'SESSION_LIFETIME' => 'required|integer|min:1',
        'REDIS_HOST' => 'required_if:CACHE_DRIVER,redis|string',
        'REDIS_PASSWORD' => 'nullable|string',
        'REDIS_PORT' => 'required_if:CACHE_DRIVER,redis|integer|between:1,65535',

        // Queue
        'QUEUE_CONNECTION' => 'required|in:sync,database,redis,sqs',

        // Mail
        'MAIL_MAILER' => 'required|string',
        'MAIL_HOST' => 'required_if:MAIL_MAILER,smtp|string',
        'MAIL_PORT' => 'required_if:MAIL_MAILER,smtp|integer|between:1,65535',
        'MAIL_USERNAME' => 'required_if:MAIL_MAILER,smtp|string',
        'MAIL_PASSWORD' => 'required_if:MAIL_MAILER,smtp|string',
        'MAIL_ENCRYPTION' => 'nullable|in:tls,ssl',
        'MAIL_FROM_ADDRESS' => 'required|email',
        'MAIL_FROM_NAME' => 'required|string',

        // AWS
        'AWS_ACCESS_KEY_ID' => 'required|string|min:16|max:32',
        'AWS_SECRET_ACCESS_KEY' => 'required|string|min:40',
        'AWS_DEFAULT_REGION' => 'required|string',
        'AWS_BUCKET' => 'required|string|min:3|max:63',

        // Custom app variables
        'API_RATE_LIMIT' => 'required|integer|min:1',
        'FEATURE_FLAG_NEW_UI' => ['required', BooleanRule::class],
        'EXTERNAL_API_URL' => ['required', UrlRule::class],
        'EXTERNAL_API_KEY' => 'required|string|min:10',
    ]);

    // This should pass with our comprehensive real-world setup
    expect($validator->validate($realWorldEnv))->toBeTrue();

    // Test that changing one critical value fails validation
    $realWorldEnv['DB_PORT'] = '999999'; // Invalid port

    expect(/**
     * @throws InvalidEnvironmentException
     */ static fn () => $validator->validate($realWorldEnv))
        ->toThrow(InvalidEnvironmentException::class);
});

test('works with environment files and dotenv', function () {
    // Create a temporary .env file for testing
    $envContent = '
APP_NAME="Test App"
APP_ENV=production
APP_KEY=base64:'.base64_encode(str_repeat('A', 32)).'
APP_DEBUG=false
APP_URL=https://test.example.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
CUSTOM_VAR=test_value
';

    $envPath = $this->createTestEnv([
        'APP_NAME' => 'Test App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('A', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://test.example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'CUSTOM_VAR' => 'test_value',
    ]);

    // Load the environment file
    if (class_exists(Dotenv::class)) {
        $dotenv = Dotenv::createImmutable(dirname($envPath), basename($envPath));
        $dotenv->load();
    }

    // Add rule for our custom variable
    EnvValidatorFacade::addRule('CUSTOM_VAR', 'required|string|min:5');

    // Test validation with loaded environment
    expect(EnvValidatorFacade::validate($_ENV))->toBeTrue();
});
