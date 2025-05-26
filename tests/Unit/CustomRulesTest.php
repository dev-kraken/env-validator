<?php

namespace Tests\Unit;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\KeyRule;

// Test BooleanRule
test('BooleanRule validates boolean values correctly', function ($value, $shouldPass) {
    $rule = new BooleanRule;
    expect($rule->passes('test_attribute', $value))->toBe($shouldPass);
})->with([
    'true string' => ['true', true],
    'false string' => ['false', true],
    '1 string' => ['1', true],
    '0 string' => ['0', true],
    'yes string' => ['yes', true],
    'no string' => ['no', true],
    'on string' => ['on', true],
    'off string' => ['off', true],
    'TRUE uppercase' => ['TRUE', true],
    'FALSE uppercase' => ['FALSE', true],
    'Yes mixed case' => ['Yes', true],
    'No mixed case' => ['No', true],
    'true boolean' => [true, true],
    'false boolean' => [false, true],
    '1 integer' => [1, true],
    '0 integer' => [0, true],
    'invalid string' => ['not-a-boolean', false],
    'invalid integer' => [2, false],
    'null' => [null, false],
    'empty string' => ['', false],
]);

test('BooleanRule provides a validation message', function () {
    $rule = new BooleanRule;
    expect($rule->message())->toBeString()
        ->and($rule->message())->toContain(':attribute');
});

// Test KeyRule
test('KeyRule validates Laravel APP_KEY correctly', function ($value, $shouldPass) {
    $rule = new KeyRule;
    expect($rule->passes('test_attribute', $value))->toBe($shouldPass);
})->with([
    'valid base64 key' => ['base64:'.str_repeat('A', 44), true],
    'valid base64 key with mixed chars' => ['base64:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmn=', true],
    'missing base64 prefix' => [str_repeat('A', 44), false],
    'too short' => ['base64:ABC', false],
    'invalid characters' => ['base64:!@#$%^&*()', false],
    'invalid prefix' => ['b64:'.str_repeat('A', 44), false],
    'empty string' => ['', false],
    'null' => [null, false],
]);

test('KeyRule provides a validation message', function () {
    $rule = new KeyRule;
    expect($rule->message())->toBeString()
        ->and($rule->message())->toContain(':attribute');
});

// Test UrlRule
test('UrlRule validates URLs correctly', function ($value, $shouldPass) {
    $rule = new UrlRule;
    expect($rule->passes('test_attribute', $value))->toBe($shouldPass);
})->with([
    'http url' => ['http://example.com', true],
    'https url' => ['https://example.com', true],
    'domain with path' => ['https://example.com/path', true],
    'domain with query' => ['https://example.com?q=test', true],
    'domain with port' => ['https://example.com:8080', true],
    'localhost' => ['http://localhost', true],
    'ip address' => ['http://127.0.0.1', true],
    'missing protocol' => ['example.com', false],
    'invalid protocol' => ['ftp://example.com', true], // ftp is still a valid URL protocol
    'invalid characters' => ['http://ex ample.com', false],
    'empty string' => ['', false],
    'null' => [null, false],
]);

test('UrlRule provides a validation message', function () {
    $rule = new UrlRule;
    expect($rule->message())->toBeString()
        ->and($rule->message())->toContain(':attribute');
});
