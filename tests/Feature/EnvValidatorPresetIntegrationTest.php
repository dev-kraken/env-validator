<?php

namespace Tests\Feature;

use EnvValidator\EnvValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;

// Integration tests for preset functionality
test('it handles microservice environment validation with minimal preset', function () {
    $validator = (new EnvValidator)->useMinimalRules();

    // Typical microservice environment (minimal requirements)
    $microserviceEnv = [
        'APP_NAME' => 'User Service',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'APP_DEBUG' => 'false',
    ];

    expect($validator->validate($microserviceEnv))->toBeTrue();

    // Should fail with invalid environment
    $invalidEnv = $microserviceEnv;
    $invalidEnv['APP_ENV'] = 'invalid-environment';

    expect(fn () => $validator->validate($invalidEnv))
        ->toThrow(InvalidEnvironmentException::class);
});

test('it handles production deployment validation with production preset', function () {
    $validator = (new EnvValidator)->useProductionRules();

    // Complete production environment
    $productionEnv = [
        'APP_NAME' => 'E-commerce Platform',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://shop.example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];

    expect($validator->validate($productionEnv))->toBeTrue();

    // Should fail when critical production variables are missing
    $incompleteEnv = $productionEnv;
    unset($incompleteEnv['APP_URL']);

    expect(static fn () => $validator->validate($incompleteEnv))
        ->toThrow(InvalidEnvironmentException::class);
});

test('it handles api service validation with api preset', function () {
    $validator = (new EnvValidator)->useApiRules();

    // Typical API service environment
    $apiEnv = [
        'APP_NAME' => 'REST API Gateway',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://api.gateway.example.com',
        'APP_LOCALE' => 'en',
    ];

    expect($validator->validate($apiEnv))->toBeTrue();

    // Should not require fallback locale (not needed for APIs)
    $apiEnvWithoutFallback = $apiEnv;
    // API preset doesn't require APP_FALLBACK_LOCALE
    expect($validator->validate($apiEnvWithoutFallback))->toBeTrue();
});

test('it demonstrates preset comparison for different deployment scenarios', function () {
    $baseEnv = [
        'APP_NAME' => 'Sample Application',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'APP_DEBUG' => 'false',
    ];

    // Minimal preset - works with just basic app config
    $minimalValidator = (new EnvValidator)->useMinimalRules();
    expect($minimalValidator->validate($baseEnv))->toBeTrue();

    // Production preset - requires URL and localization
    $productionValidator = (new EnvValidator)->useProductionRules();
    expect(static fn () => $productionValidator->validate($baseEnv))
        ->toThrow(InvalidEnvironmentException::class); // Missing URL and locale config

    // Add URL and localization for production
    $productionEnv = array_merge($baseEnv, [
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ]);

    expect($productionValidator->validate($productionEnv))->toBeTrue();

    // API preset - requires URL and locale but not fallback locale
    $apiValidator = (new EnvValidator)->useApiRules();
    $apiEnv = array_merge($baseEnv, [
        'APP_URL' => 'https://api.example.com',
        'APP_LOCALE' => 'en',
    ]);

    expect($apiValidator->validate($apiEnv))->toBeTrue();
});

test('it handles custom rules addition to presets in real scenarios', function () {
    // Scenario: API service with custom authentication and rate limiting
    $validator = (new EnvValidator)
        ->useApiRules()
        ->addRule('JWT_SECRET', 'required|string|min:32')
        ->addRule('RATE_LIMIT_PER_MINUTE', 'required|integer|min:1|max:1000')
        ->addRule('API_VERSION', 'required|string|in:v1,v2,v3')
        ->addRule('CORS_ALLOWED_ORIGINS', 'required|string');

    $apiWithCustomEnv = [
        'APP_NAME' => 'Authentication API',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://auth.api.example.com',
        'APP_LOCALE' => 'en',
        // Custom variables
        'JWT_SECRET' => str_repeat('super-secret-jwt-key-', 2),
        'RATE_LIMIT_PER_MINUTE' => '100',
        'API_VERSION' => 'v2',
        'CORS_ALLOWED_ORIGINS' => 'https://app.example.com,https://admin.example.com',
    ];

    expect($validator->validate($apiWithCustomEnv))->toBeTrue();

    // Should fail with invalid API version
    $invalidApiEnv = $apiWithCustomEnv;
    $invalidApiEnv['API_VERSION'] = 'v4'; // Invalid version

    expect(fn () => $validator->validate($invalidApiEnv))
        ->toThrow(InvalidEnvironmentException::class);
});

test('it validates complex multi-service environment configurations', function () {
    // Scenario: Different services in a microservices architecture

    // Service 1: User Management (minimal)
    $userService = (new EnvValidator)->useMinimalRules();
    $userEnv = [
        'APP_NAME' => 'User Management Service',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('u', 32)),
        'APP_DEBUG' => 'false',
    ];
    expect($userService->validate($userEnv))->toBeTrue();

    // Service 2: Order Processing (API with custom rules)
    $orderService = (new EnvValidator)
        ->useApiRules()
        ->addRule('PAYMENT_GATEWAY_URL', 'required|url')
        ->addRule('PAYMENT_API_KEY', 'required|string|min:20');

    $orderEnv = [
        'APP_NAME' => 'Order Processing Service',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('o', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://orders.services.example.com',
        'APP_LOCALE' => 'en',
        'PAYMENT_GATEWAY_URL' => 'https://api.paymentgateway.com',
        'PAYMENT_API_KEY' => 'pk_live_'.str_repeat('x', 20),
    ];
    expect($orderService->validate($orderEnv))->toBeTrue();

    // Service 3: Main Web App (production)
    $webService = (new EnvValidator)->useProductionRules();
    $webEnv = [
        'APP_NAME' => 'E-commerce Web Application',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('w', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://shop.example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];
    expect($webService->validate($webEnv))->toBeTrue();
});

test('it handles environment transition scenarios', function () {
    // Scenario: Moving from development to production
    $validator = new EnvValidator;

    // Development environment (minimal requirements)
    $devEnv = [
        'APP_NAME' => 'My App',
        'APP_ENV' => 'local',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('d', 32)),
        'APP_DEBUG' => 'true',
    ];

    // Use minimal preset for development
    $validator->useMinimalRules();
    expect($validator->validate($devEnv))->toBeTrue();

    // Transition to production preset
    $validator->useProductionRules();

    // Same environment should fail production validation
    expect(static fn () => $validator->validate($devEnv))
        ->toThrow(InvalidEnvironmentException::class);

    // Upgrade environment for production
    $prodEnv = [
        'APP_NAME' => 'My App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('p', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://myapp.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];

    expect($validator->validate($prodEnv))->toBeTrue();
});

test('it handles preset chaining with different application types', function () {
    // SPA Frontend API
    $spaValidator = (new EnvValidator)
        ->useApiRules()
        ->addRule('FRONTEND_URL', 'required|url')
        ->addRule('CORS_ENABLED', 'required|in:true,false');

    $spaEnv = [
        'APP_NAME' => 'SPA Backend API',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('s', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://api.spa.example.com',
        'APP_LOCALE' => 'en',
        'FRONTEND_URL' => 'https://spa.example.com',
        'CORS_ENABLED' => 'true',
    ];

    expect($spaValidator->validate($spaEnv))->toBeTrue();

    // Background Job Processor
    $jobValidator = (new EnvValidator)
        ->useMinimalRules()
        ->addRule('QUEUE_DRIVER', 'required|in:redis,sqs,database')
        ->addRule('WORKER_TIMEOUT', 'required|integer|min:30|max:3600');

    $jobEnv = [
        'APP_NAME' => 'Background Job Processor',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('j', 32)),
        'APP_DEBUG' => 'false',
        'QUEUE_DRIVER' => 'redis',
        'WORKER_TIMEOUT' => '300',
    ];

    expect($jobValidator->validate($jobEnv))->toBeTrue();
});
