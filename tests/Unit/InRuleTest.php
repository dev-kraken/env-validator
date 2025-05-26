<?php

namespace Tests\Unit;

use EnvValidator\Collections\StringRules\InRule;
use stdClass;

// Tests for the new InRule class
test('InRule validates values correctly', function () {
    $rule = new InRule(['staging', 'production']);

    expect($rule->passes('test', 'staging'))->toBeTrue()
        ->and($rule->passes('test', 'production'))->toBeTrue()
        ->and($rule->passes('test', 'development'))->toBeFalse()
        ->and($rule->passes('test', 'local'))->toBeFalse()
        ->and($rule->passes('test', ''))->toBeFalse()
        ->and($rule->passes('test', null))->toBeFalse();
});

test('InRule works with different data types', function () {
    $rule = new InRule([1, 2, 'yes', 'no', true, false]);

    expect($rule->passes('test', 1))->toBeTrue()
        ->and($rule->passes('test', 2))->toBeTrue()
        ->and($rule->passes('test', 'yes'))->toBeTrue()
        ->and($rule->passes('test', 'no'))->toBeTrue()
        ->and($rule->passes('test', true))->toBeTrue()
        ->and($rule->passes('test', false))->toBeTrue()
        ->and($rule->passes('test', 3))->toBeFalse()
        ->and($rule->passes('test', 'maybe'))->toBeFalse();

});

test('InRule strict mode works correctly', function () {
    $strictRule = new InRule(['1', '2'], null, true);
    $nonStrictRule = new InRule(['1', '2'], null, false);

    // Strict mode: exact type matching
    expect($strictRule->passes('test', '1'))->toBeTrue()
        ->and($strictRule->passes('test', 1))->toBeFalse()
        ->and($nonStrictRule->passes('test', '1'))->toBeTrue()
        ->and($nonStrictRule->passes('test', 1))->toBeTrue();
    // Different type

    // Non-strict mode: loose comparison
    // Same value, different type
});

test('InRule provides default error message', function () {
    $rule = new InRule(['debug', 'info', 'warning', 'error']);

    $message = $rule->message();

    expect($message)->toBe('The :attribute must be one of: debug, info, warning, error.');
});

test('InRule provides custom error message', function () {
    $customMessage = 'The :attribute must be a valid environment (staging or production only).';
    $rule = new InRule(['staging', 'production'], $customMessage);

    expect($rule->message())->toBe($customMessage);
});

test('InRule getValidValues returns correct values', function () {
    $values = ['local', 'development', 'staging', 'production'];
    $rule = new InRule($values);

    expect($rule->getValidValues())->toBe($values);
});

test('InRule isStrict returns correct boolean', function () {
    $strictRule = new InRule(['a', 'b'], null, true);
    $nonStrictRule = new InRule(['a', 'b'], null, false);

    expect($strictRule->isStrict())->toBeTrue()
        ->and($nonStrictRule->isStrict())->toBeFalse();
});

test('InRule handles empty valid values array', function () {
    $rule = new InRule([]);

    expect($rule->passes('test', 'anything'))->toBeFalse()
        ->and($rule->message())->toBe('The :attribute must be one of: .');
});

test('InRule handles complex object values in message', function () {
    $rule = new InRule([['complex'], new stdClass, 'string']);

    $message = $rule->message();

    // Should handle non-string values gracefully
    expect($message)->toContain('object, object, string');
});

test('InRule can be used with common environment values', function () {
    $envRule = new InRule(['local', 'development', 'testing', 'staging', 'production']);

    expect($envRule->passes('APP_ENV', 'local'))->toBeTrue()
        ->and($envRule->passes('APP_ENV', 'development'))->toBeTrue()
        ->and($envRule->passes('APP_ENV', 'testing'))->toBeTrue()
        ->and($envRule->passes('APP_ENV', 'staging'))->toBeTrue()
        ->and($envRule->passes('APP_ENV', 'production'))->toBeTrue()
        ->and($envRule->passes('APP_ENV', 'invalid'))->toBeFalse()
        ->and($envRule->passes('APP_ENV', ''))->toBeFalse();

});

test('InRule can be used with log levels', function () {
    $logRule = new InRule([
        'emergency', 'alert', 'critical', 'error',
        'warning', 'notice', 'info', 'debug',
    ]);

    expect($logRule->passes('LOG_LEVEL', 'emergency'))->toBeTrue()
        ->and($logRule->passes('LOG_LEVEL', 'debug'))->toBeTrue()
        ->and($logRule->passes('LOG_LEVEL', 'invalid'))->toBeFalse();
});

test('InRule can be used with boolean strings', function () {
    $boolRule = new InRule(['true', 'false', '1', '0']);

    expect($boolRule->passes('DEBUG', 'true'))->toBeTrue()
        ->and($boolRule->passes('DEBUG', 'false'))->toBeTrue()
        ->and($boolRule->passes('DEBUG', '1'))->toBeTrue()
        ->and($boolRule->passes('DEBUG', '0'))->toBeTrue()
        ->and($boolRule->passes('DEBUG', 'yes'))->toBeFalse()
        ->and($boolRule->passes('DEBUG', 'no'))->toBeFalse();

});

test('InRule can validate cache drivers', function () {
    $cacheRule = new InRule(['file', 'database', 'redis', 'memcached', 'array']);

    expect($cacheRule->passes('CACHE_DRIVER', 'redis'))->toBeTrue()
        ->and($cacheRule->passes('CACHE_DRIVER', 'file'))->toBeTrue()
        ->and($cacheRule->passes('CACHE_DRIVER', 'invalid'))->toBeFalse();
});

test('InRule message handles numeric and mixed types correctly', function () {
    $mixedRule = new InRule([1, 2.5, 'string', true, false]);

    $message = $mixedRule->message();

    expect($message)->toContain('1, 2.5, string, 1, '); // true/false convert to 1/''
});

test('InRule works in real validation scenario', function () {
    $envRule = new InRule(['staging', 'production'], 'Environment must be staging or production');

    // This would be used in actual validation rules array
    $rules = [
        'APP_ENV' => ['required', 'string', $envRule],
    ];

    expect($envRule->passes('APP_ENV', 'production'))->toBeTrue()
        ->and($envRule->message())->toBe('Environment must be staging or production');
});
