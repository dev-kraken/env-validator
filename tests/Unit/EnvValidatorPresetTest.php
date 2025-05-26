<?php

namespace Tests\Unit;

use EnvValidator\Collections\StringRules\InRule;
use EnvValidator\EnvValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;
use InvalidArgumentException;

// Test preset functionality
test('it creates instance with default full Laravel rules', function () {
    $validator = new EnvValidator;

    $rules = $validator->getRules();

    // Should have all default Laravel rules (application + localization)
    expect($rules)
        ->toHaveKey('APP_NAME')
        ->and($rules)->toHaveKey('APP_ENV')
        ->and($rules)->toHaveKey('APP_KEY')
        ->and($rules)->toHaveKey('APP_DEBUG')
        ->and($rules)->toHaveKey('APP_URL')
        ->and($rules)->toHaveKey('APP_LOCALE')
        ->and($rules)->toHaveKey('APP_FALLBACK_LOCALE')
        ->and($rules)->toHaveKey('APP_FAKER_LOCALE')
        ->and(count($rules))->toBe(8); // Full Laravel preset has 8 rules
});

test('it uses minimal rules preset', function () {
    $validator = (new EnvValidator)->useMinimalRules();

    $rules = $validator->getRules();

    // Should only have essential rules (no APP_URL in minimal)
    expect($rules)
        ->toHaveKey('APP_NAME')
        ->and($rules)->toHaveKey('APP_ENV')
        ->and($rules)->toHaveKey('APP_KEY')
        ->and($rules)->toHaveKey('APP_DEBUG')
        ->and(count($rules))->toBe(4)
        ->and($rules)
        ->not()->toHaveKey('APP_URL')
        ->and($rules)->not()->toHaveKey('APP_LOCALE')
        ->and($rules)->not()->toHaveKey('APP_FALLBACK_LOCALE'); // Minimal preset has 4 rules

    // Should not have URL and localization rules
});

test('it uses production rules preset', function () {
    $validator = (new EnvValidator)->useProductionRules();

    $rules = $validator->getRules();

    // Should have production-critical rules (application + localization, stricter APP_ENV)
    expect($rules)
        ->toHaveKey('APP_NAME')
        ->and($rules)->toHaveKey('APP_ENV')
        ->and($rules)->toHaveKey('APP_KEY')
        ->and($rules)->toHaveKey('APP_DEBUG')
        ->and($rules)->toHaveKey('APP_URL')
        ->and($rules)->toHaveKey('APP_LOCALE')
        ->and($rules)->toHaveKey('APP_FALLBACK_LOCALE')
        ->and($rules)->toHaveKey('APP_FAKER_LOCALE')
        ->and(count($rules))->toBe(8)
        ->and($rules['APP_ENV'])->toBeArray()
        ->and($rules['APP_ENV'])->toHaveCount(3); // Production preset has 8 rules

    // Production APP_ENV should be restricted to staging,production
    // Check that APP_ENV is an array containing an InRule object
    // required, string, InRule object

    // Find the InRule object and check its valid values
    $inRule = null;
    foreach ($rules['APP_ENV'] as $rule) {
        if ($rule instanceof InRule) {
            $inRule = $rule;
            break;
        }
    }

    expect($inRule)->not()->toBeNull()
        ->and($inRule->getValidValues())->toBe(['staging', 'production']);
});

test('it uses api rules preset', function () {
    $validator = (new EnvValidator)->useApiRules();

    $rules = $validator->getRules();

    // Should have API-focused rules (application + APP_LOCALE only)
    expect($rules)
        ->toHaveKey('APP_NAME')
        ->and($rules)->toHaveKey('APP_ENV')
        ->and($rules)->toHaveKey('APP_KEY')
        ->and($rules)->toHaveKey('APP_DEBUG')
        ->and($rules)->toHaveKey('APP_URL')
        ->and($rules)->toHaveKey('APP_LOCALE')
        ->and(count($rules))->toBe(6)
        ->and($rules)
        ->not()->toHaveKey('APP_FALLBACK_LOCALE')
        ->and($rules)->not()->toHaveKey('APP_FAKER_LOCALE'); // API preset has 6 rules

    // Should not have fallback locale or faker locale
});

test('it uses preset by name', function () {
    $minimalValidator = (new EnvValidator)->usePreset('minimal');
    $productionValidator = (new EnvValidator)->usePreset('production');
    $apiValidator = (new EnvValidator)->usePreset('api');
    $laravelValidator = (new EnvValidator)->usePreset('laravel');

    // Test that named presets match method presets
    expect($minimalValidator->getRules())
        ->toEqual((new EnvValidator)->useMinimalRules()->getRules())
        ->and($productionValidator->getRules())
        ->toEqual((new EnvValidator)->useProductionRules()->getRules())
        ->and($apiValidator->getRules())
        ->toEqual((new EnvValidator)->useApiRules()->getRules())
        ->and($laravelValidator->getRules())
        ->toEqual((new EnvValidator)->useFullRules()->getRules());

});

test('it throws exception for invalid preset name', function () {
    expect(static fn () => (new EnvValidator)->usePreset('invalid'))
        ->toThrow(InvalidArgumentException::class, 'Unknown preset: invalid');
});

test('it validates with minimal preset', function () {
    $validator = (new EnvValidator)->useMinimalRules();

    $env = [
        'APP_NAME' => 'Test API',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
    ];

    expect($validator->validate($env))->toBeTrue()
        ->and($validator->validate($env))->toBeTrue();

    // Should work without APP_URL since minimal doesn't require it
});

test('it validates with production preset', function () {
    $validator = (new EnvValidator)->useProductionRules();

    $env = [
        'APP_NAME' => 'Production App',
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

test('it validates with api preset', function () {
    $validator = (new EnvValidator)->useApiRules();

    $env = [
        'APP_NAME' => 'API Service',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://api.example.com',
        'APP_LOCALE' => 'en',
    ];

    expect($validator->validate($env))->toBeTrue();
});

test('it fails validation when required preset variables are missing', function () {
    $validator = (new EnvValidator)->useMinimalRules();

    $env = [
        'APP_NAME' => 'Test API',
        // APP_ENV missing
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
    ];

    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);
});

test('it allows chaining preset with custom rules', function () {
    $validator = (new EnvValidator)
        ->useMinimalRules()
        ->addRule('CUSTOM_API_KEY', 'required|string|min:32')
        ->addRule('CUSTOM_TIMEOUT', 'required|integer|min:1|max:300');

    $rules = $validator->getRules();

    // Should have minimal rules plus custom ones
    expect($rules)
        ->toHaveKey('APP_NAME') // from minimal preset
        ->and($rules)->toHaveKey('CUSTOM_API_KEY') // custom rule
        ->and($rules)->toHaveKey('CUSTOM_TIMEOUT') // custom rule
        ->and(count($rules))->toBe(6); // 4 minimal + 2 custom

    $env = [
        'APP_NAME' => 'Test API',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
        'CUSTOM_API_KEY' => str_repeat('a', 32),
        'CUSTOM_TIMEOUT' => '30',
    ];

    expect($validator->validate($env))->toBeTrue();
});

test('it allows switching between presets', function () {
    $validator = new EnvValidator;

    // Start with minimal
    $validator->useMinimalRules();
    $minimalRulesCount = count($validator->getRules());

    // Switch to production
    $validator->useProductionRules();
    $productionRulesCount = count($validator->getRules());

    // Switch to API
    $validator->useApiRules();
    $apiRulesCount = count($validator->getRules());

    expect($minimalRulesCount)->toBe(4)
        ->and($productionRulesCount)->toBe(8)
        ->and($apiRulesCount)->toBe(6);
});

test('it preserves custom messages when using presets', function () {
    $validator = new EnvValidator;

    $customMessages = [
        'APP_KEY.required' => 'Custom message: Application key is required',
    ];

    $validator->setMessages($customMessages);
    $validator->useMinimalRules();

    expect($validator->getMessages())->toHaveKey('APP_KEY.required');
});

test('it can validate only specific variables with presets', function () {
    $validator = (new EnvValidator)->useProductionRules();

    $env = [
        'APP_NAME' => 'Production App',
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        // Missing locale variables but we're only validating APP variables
    ];

    expect($validator->validateOnly(['APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL'], $env))
        ->toBeTrue();
});

test('it validates production preset environment restrictions', function () {
    $validator = (new EnvValidator)->useProductionRules();

    $env = [
        'APP_NAME' => 'Production App',
        'APP_ENV' => 'local', // Invalid for production preset
        'APP_KEY' => 'base64:'.str_repeat('A', 44),
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en',
        'APP_FAKER_LOCALE' => 'en_US',
    ];

    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);
});

test('it can use application and localization presets separately', function () {
    $validator = (new EnvValidator)->usePreset('application');
    $appRules = $validator->getRules();

    expect($appRules)
        ->toHaveKey('APP_NAME')
        ->and($appRules)->toHaveKey('APP_ENV')
        ->and($appRules)->toHaveKey('APP_KEY')
        ->and($appRules)->toHaveKey('APP_DEBUG')
        ->and($appRules)->toHaveKey('APP_URL')
        ->and(count($appRules))->toBe(5);

    $validator = (new EnvValidator)->usePreset('localization');
    $localeRules = $validator->getRules();

    expect($localeRules)
        ->toHaveKey('APP_LOCALE')
        ->and($localeRules)->toHaveKey('APP_FALLBACK_LOCALE')
        ->and($localeRules)->toHaveKey('APP_FAKER_LOCALE')
        ->and(count($localeRules))->toBe(3);
});
