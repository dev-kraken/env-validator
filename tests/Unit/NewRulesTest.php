<?php

use EnvValidator\Collections\NumericRules\PortRule;
use EnvValidator\Collections\StringRules\EmailRule;
use EnvValidator\Collections\StringRules\JsonRule;
use EnvValidator\EnvValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;

describe('EmailRule', function () {
    test('validates valid email addresses', function () {
        $rule = new EmailRule;

        expect($rule->passes('email', 'test@example.com'))->toBeTrue()
            ->and($rule->passes('email', 'user.name+tag@domain.co.uk'))->toBeTrue()
            ->and($rule->passes('email', 'admin@example.localhost'))->toBeTrue();
    });

    test('rejects invalid email addresses', function () {
        $rule = new EmailRule;

        expect($rule->passes('email', 'invalid-email'))->toBeFalse()
            ->and($rule->passes('email', '@example.com'))->toBeFalse()
            ->and($rule->passes('email', 'test@'))->toBeFalse()
            ->and($rule->passes('email', ''))->toBeFalse()
            ->and($rule->passes('email', null))->toBeFalse()
            ->and($rule->passes('email', 123))->toBeFalse();
    });

    test('provides default error message', function () {
        $rule = new EmailRule;

        expect($rule->message())->toBe('The :attribute must be a valid email address.');
    });

    test('provides custom error message', function () {
        $rule = new EmailRule('Custom email validation message.');

        expect($rule->message())->toBe('Custom email validation message.');
    });
});

describe('JsonRule', function () {
    test('validates valid JSON strings', function () {
        $rule = new JsonRule;

        expect($rule->passes('json', '{"key": "value"}'))->toBeTrue()
            ->and($rule->passes('json', '[]'))->toBeTrue()
            ->and($rule->passes('json', '["item1", "item2"]'))->toBeTrue()
            ->and($rule->passes('json', '{"nested": {"key": "value"}}'))->toBeTrue()
            ->and($rule->passes('json', 'null'))->toBeTrue()
            ->and($rule->passes('json', 'true'))->toBeTrue()
            ->and($rule->passes('json', '123'))->toBeTrue()
            ->and($rule->passes('json', '"string"'))->toBeTrue();
    });

    test('rejects invalid JSON strings', function () {
        $rule = new JsonRule;

        expect($rule->passes('json', '{invalid json}'))->toBeFalse()
            ->and($rule->passes('json', '{"key": value}'))->toBeFalse()
            ->and($rule->passes('json', "{'key': 'value'}"))->toBeFalse()
            ->and($rule->passes('json', '{'))->toBeFalse()
            ->and($rule->passes('json', ''))->toBeFalse()
            ->and($rule->passes('json', '   '))->toBeFalse()
            ->and($rule->passes('json', null))->toBeFalse()
            ->and($rule->passes('json', 123))->toBeFalse()
            ->and($rule->passes('json', []))->toBeFalse();
    });

    test('provides default error message', function () {
        $rule = new JsonRule;

        expect($rule->message())->toBe('The :attribute must be valid JSON.');
    });

    test('provides custom error message', function () {
        $rule = new JsonRule('Custom JSON validation message.');

        expect($rule->message())->toBe('Custom JSON validation message.');
    });
});

describe('PortRule', function () {
    test('validates valid port numbers', function () {
        $rule = new PortRule;

        expect($rule->passes('port', 80))->toBeTrue()
            ->and($rule->passes('port', 443))->toBeTrue()
            ->and($rule->passes('port', 3000))->toBeTrue()
            ->and($rule->passes('port', 65535))->toBeTrue()
            ->and($rule->passes('port', 1))->toBeTrue()
            ->and($rule->passes('port', '8080'))->toBeTrue()
            ->and($rule->passes('port', '22'))->toBeTrue();
        // String numbers
    });

    test('rejects invalid port numbers', function () {
        $rule = new PortRule;

        expect($rule->passes('port', 0))->toBeFalse()
            ->and($rule->passes('port', 65536))->toBeFalse()
            ->and($rule->passes('port', -1))->toBeFalse()
            ->and($rule->passes('port', 'not-a-number'))->toBeFalse()
            ->and($rule->passes('port', null))->toBeFalse()
            ->and($rule->passes('port', []))->toBeFalse()
            ->and($rule->passes('port', 3.14))->toBeFalse();
    });

    test('validates with custom range', function () {
        $rule = new PortRule(1024, 8080);

        expect($rule->passes('port', 1024))->toBeTrue()
            ->and($rule->passes('port', 8080))->toBeTrue()
            ->and($rule->passes('port', 3000))->toBeTrue()
            ->and($rule->passes('port', 80))->toBeFalse()
            ->and($rule->passes('port', 9000))->toBeFalse();
        // Below minimum
        // Above maximum
    });

    test('provides default error message', function () {
        $rule = new PortRule;

        expect($rule->message())->toBe('The :attribute must be a valid port number between 1 and 65535.');
    });

    test('provides custom error message', function () {
        $rule = new PortRule(1, 65535, 'Custom port validation message.');

        expect($rule->message())->toBe('Custom port validation message.');
    });

    test('provides range-specific error message', function () {
        $rule = new PortRule(1024, 8080);

        expect($rule->message())->toBe('The :attribute must be a valid port number between 1024 and 8080.');
    });

    test('provides getter methods', function () {
        $rule = new PortRule(1024, 8080);

        expect($rule->getMin())->toBe(1024)
            ->and($rule->getMax())->toBe(8080);
    });
});

describe('New Rules Integration', function () {
    test('EmailRule works in validation scenario', function () {
        $validator = new EnvValidator;
        $validator->setRules([
            'ADMIN_EMAIL' => ['required', new EmailRule],
        ]);

        $validEnv = ['ADMIN_EMAIL' => 'admin@example.com'];
        expect($validator->validate($validEnv))->toBeTrue();

        $invalidEnv = ['ADMIN_EMAIL' => 'invalid-email'];
        expect(static fn () => $validator->validate($invalidEnv))
            ->toThrow(InvalidEnvironmentException::class);
    });

    test('JsonRule works in validation scenario', function () {
        $validator = new EnvValidator;
        $validator->setRules([
            'API_CONFIG' => ['required', new JsonRule],
        ]);

        $validEnv = ['API_CONFIG' => '{"key": "value"}'];
        expect($validator->validate($validEnv))->toBeTrue();

        $invalidEnv = ['API_CONFIG' => '{invalid json}'];
        expect(static fn () => $validator->validate($invalidEnv))
            ->toThrow(InvalidEnvironmentException::class);
    });

    test('PortRule works in validation scenario', function () {
        $validator = new EnvValidator;
        $validator->setRules([
            'DB_PORT' => ['required', new PortRule],
        ]);

        $validEnv = ['DB_PORT' => '3306'];
        expect($validator->validate($validEnv))->toBeTrue();

        $invalidEnv = ['DB_PORT' => '99999'];
        expect(static fn () => $validator->validate($invalidEnv))
            ->toThrow(InvalidEnvironmentException::class);
    });
});
