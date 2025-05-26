<?php

namespace Tests\Unit;

use EnvValidator\Collections\StringRules\InRule;
use EnvValidator\Core\DefaultRulePresets;
use EnvValidator\EnvValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;

// Tests demonstrating the benefits and capabilities of Rule objects
test('it demonstrates Rule object reusability benefits', function () {
    // Define reusable rule instances
    $environmentRule = new InRule(['local', 'development', 'staging', 'production']);
    $logLevelRule = new InRule(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug']);
    $booleanString = new InRule(['true', 'false', '1', '0']);

    // Reuse across multiple field definitions
    $rules = [
        'APP_ENV' => ['required', 'string', $environmentRule],
        'TESTING_ENV' => ['required', 'string', $environmentRule], // Same rule reused
        'LOG_LEVEL' => ['required', 'string', $logLevelRule],
        'ERROR_LOG_LEVEL' => ['required', 'string', $logLevelRule], // Same rule reused
        'FEATURE_FLAG_A' => ['required', 'string', $booleanString],
        'FEATURE_FLAG_B' => ['required', 'string', $booleanString], // Same rule reused
    ];

    $validator = (new EnvValidator)->setRules($rules);
    $env = [
        'APP_ENV' => 'production',
        'TESTING_ENV' => 'staging',
        'LOG_LEVEL' => 'info',
        'ERROR_LOG_LEVEL' => 'error',
        'FEATURE_FLAG_A' => 'true',
        'FEATURE_FLAG_B' => 'false',
    ];

    expect($validator->validate($env))->toBeTrue()
        ->and(count($validator->getRules()))->toBe(6);
});

test('it demonstrates custom error messages with Rule objects', function () {
    $envRule = new InRule(
        ['staging', 'production'],
        'The :attribute must be either staging or production for live environments.'
    );

    $rules = [
        'APP_ENV' => ['required', 'string', $envRule],
    ];

    $validator = (new EnvValidator)->setRules($rules);

    $invalidEnv = ['APP_ENV' => 'development'];

    try {
        $validator->validate($invalidEnv);
        expect(false)->toBeTrue('Should have thrown validation exception');
    } catch (InvalidEnvironmentException $e) {
        // The custom message should be included in the error
        expect($e->getMessage())->toContain('staging or production for live environments');
    }
});

test('it shows type safety benefits of Rule objects', function () {
    $rule = new InRule(['staging', 'production']);

    // IDE and static analysis can provide autocompletion and type checking
    expect($rule->getValidValues())->toBe(['staging', 'production'])
        ->and($rule->isStrict())->toBeTrue()
        ->and($rule->passes('test', 'staging'))->toBeTrue()
        ->and($rule->passes('test', 'development'))->toBeFalse()
        ->and($rule->message())->toContain('staging, production');
});

test('it demonstrates Rule object approach with DefaultRulePresets', function () {
    // Rule object approach using DefaultRulePresets
    $rules = DefaultRulePresets::production();

    // Test validation with environment
    $env = [
        'APP_NAME' => 'Production App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];

    $validator = (new EnvValidator)->setRules($rules);
    expect($validator->validate($env))->toBeTrue()
        ->and(count($rules))->toBe(8);
    // All production rules
});

test('it demonstrates advanced rule configurations with presets', function () {
    $loggingRules = DefaultRulePresets::logging();
    $cacheRules = DefaultRulePresets::cache();
    $queueRules = DefaultRulePresets::queue();
    $advancedRules = array_merge($loggingRules, $cacheRules, $queueRules);

    $env = [
        'LOG_CHANNEL' => 'stack',
        'LOG_LEVEL' => 'info',
        'CACHE_DRIVER' => 'redis',
        'SESSION_DRIVER' => 'database',
        'SESSION_LIFETIME' => '120',
        'QUEUE_CONNECTION' => 'sqs',
    ];

    $validator = (new EnvValidator)->setRules($advancedRules);
    expect($validator->validate($env))->toBeTrue();

    // Should fail with invalid values
    $invalidEnv = $env;
    $invalidEnv['LOG_LEVEL'] = 'invalid_level';

    expect(static fn () => $validator->validate($invalidEnv))
        ->toThrow(InvalidEnvironmentException::class);
});

test('it shows fluent API benefits with Rule objects', function () {
    $validator = (new EnvValidator)
        ->useMinimalRules()
        ->addRule('API_VERSION', ['required', 'string', new InRule(['v1', 'v2', 'v3'])])
        ->addRule('RATE_LIMIT_STRATEGY', ['required', new InRule(['fixed', 'sliding', 'token_bucket'])])
        ->addRule('AUTH_PROVIDER', ['required', new InRule(['jwt', 'oauth2', 'session'])]);

    $env = [
        'APP_NAME' => 'API Gateway',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'APP_DEBUG' => 'false',
        'API_VERSION' => 'v2',
        'RATE_LIMIT_STRATEGY' => 'sliding',
        'AUTH_PROVIDER' => 'jwt',
    ];

    expect($validator->validate($env))->toBeTrue()
        ->and(count($validator->getRules()))->toBe(7);
    // 4 minimal + 3 custom
});

test('it demonstrates testing individual rules', function () {
    $environmentRule = new InRule(['local', 'development', 'staging', 'production']);

    // Easy to unit test individual rules
    expect($environmentRule->passes('APP_ENV', 'production'))->toBeTrue()
        ->and($environmentRule->passes('APP_ENV', 'invalid'))->toBeFalse()
        ->and($environmentRule->message())->toBe('The :attribute must be one of: local, development, staging, production.');

    // Test with custom message
    $customRule = new InRule(['yes', 'no'], 'Please answer yes or no.');
    expect($customRule->message())->toBe('Please answer yes or no.');
});

test('it shows Rule object extensibility and maintainability', function () {
    // Create a complex rule with multiple validations
    $productionEnvironmentRule = new InRule(
        ['staging', 'production'],
        'Production deployments require staging or production environment.'
    );

    $rules = [
        'APP_ENV' => ['required', 'string', $productionEnvironmentRule],
        'DEPLOYMENT_ENV' => ['required', 'string', $productionEnvironmentRule], // Reused
    ];

    $validator = (new EnvValidator)->setRules($rules);

    $validEnv = [
        'APP_ENV' => 'production',
        'DEPLOYMENT_ENV' => 'staging',
    ];

    expect($validator->validate($validEnv))->toBeTrue();

    // Test that both fields use the same rule logic
    $invalidEnv = [
        'APP_ENV' => 'development',
        'DEPLOYMENT_ENV' => 'local',
    ];

    expect(static fn () => $validator->validate($invalidEnv))
        ->toThrow(InvalidEnvironmentException::class);
});

test('it demonstrates Rule object benefits summary', function () {
    // This test documents the benefits rather than testing functionality

    $benefits = [
        'type_safety' => 'IDE autocompletion and static analysis',
        'reusability' => 'Rule objects can be shared across multiple fields',
        'testability' => 'Individual rules can be unit tested in isolation',
        'readability' => 'More expressive and self-documenting code',
        'custom_messages' => 'Better context-specific error messages',
        'extensibility' => 'Easy to add custom logic to rule classes',
        'debugging' => 'Easier to debug rule-specific issues',
        'maintenance' => 'Centralized rule logic and easier refactoring',
    ];

    expect(count($benefits))->toBe(8)
        ->and($benefits)->toHaveKey('type_safety')
        ->and($benefits)->toHaveKey('reusability')
        ->and($benefits)->toHaveKey('testability');
});

test('it demonstrates preset system with Rule objects', function () {
    // Test multiple presets to show Rule object usage
    $presets = [
        'minimal' => DefaultRulePresets::minimal(),
        'production' => DefaultRulePresets::production(),
        'database' => DefaultRulePresets::database(),
        'cache' => DefaultRulePresets::cache(),
        'logging' => DefaultRulePresets::logging(),
    ];

    foreach ($presets as $presetName => $rules) {
        expect(is_array($rules))->toBeTrue()
            ->and(count($rules))->toBeGreaterThan(0);

        // Each preset should contain properly structured rules
        foreach ($rules as $key => $rule) {
            expect(is_string($key))->toBeTrue()
                ->and(is_array($rule) || is_string($rule))->toBeTrue();
        }
    }

    // Test that presets can be combined
    $combinedRules = array_merge(
        DefaultRulePresets::minimal(),
        DefaultRulePresets::database()
    );

    expect(count($combinedRules))->toBeGreaterThan(count(DefaultRulePresets::minimal()));
});
