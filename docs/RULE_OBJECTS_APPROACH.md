# Rule Objects in env-validator

## Overview

The env-validator package uses **Rule objects** as the primary approach for defining validation rules. This provides better type safety, reusability, and maintainability compared to traditional string-based rules.

## Benefits

| Aspect              | Rule Objects                   |
| ------------------- | ------------------------------ |
| **Type Safety**     | ✅ Full IDE autocompletion     |
| **Reusability**     | ✅ Share objects across fields |
| **Testing**         | ✅ Easy individual testing     |
| **Custom Messages** | ✅ Rich contextual messages    |
| **Debugging**       | ✅ Clear object inspection     |
| **Maintenance**     | ✅ Centralized rule logic      |

## Implementation Examples

### 1. Basic Usage

```php
use EnvValidator\Collections\StringRules\InRule;

$rules = [
    'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])],
    'LOG_LEVEL' => ['required', 'string', new InRule(['debug', 'info', 'warning', 'error'])],
];
```

### 2. Reusability Benefits

```php
$environmentRule = new InRule(['local', 'development', 'staging', 'production']);

$rules = [
    'APP_ENV' => ['required', 'string', $environmentRule],
    'TESTING_ENV' => ['required', 'string', $environmentRule],    // Reused!
    'FALLBACK_ENV' => ['required', 'string', $environmentRule],   // Reused!
];
```

### 3. Custom Error Messages

```php
$rules = [
    'APP_ENV' => [
        'required',
        'string',
        new InRule(
            ['staging', 'production'],
            'The :attribute must be either staging or production for live environments.'
        )
    ],
];
```

## Available Rule Objects

### InRule Class

The `InRule` class validates that a value exists in a predefined list:

```php
use EnvValidator\Collections\StringRules\InRule;

// Basic usage
$rule = new InRule(['staging', 'production']);

// With custom message
$rule = new InRule(
    ['staging', 'production'],
    'Environment must be staging or production'
);

// With strict/non-strict comparison
$strictRule = new InRule(['1', '2'], null, true);     // Exact type match
$looseRule = new InRule(['1', '2'], null, false);     // Loose comparison

// Methods available
$rule->passes('field', 'staging');        // true
$rule->getValidValues();                   // ['staging', 'production']
$rule->isStrict();                        // true (default)
$rule->message();                         // Error message
```

### Common Use Cases

#### Environment Validation

```php
$envRule = new InRule(['local', 'development', 'staging', 'production']);
```

#### Log Levels (PSR-3 Compatible)

```php
$logRule = new InRule([
    'emergency', 'alert', 'critical', 'error',
    'warning', 'notice', 'info', 'debug'
]);
```

#### Cache Drivers

```php
$cacheRule = new InRule(['file', 'database', 'redis', 'memcached', 'dynamodb']);
```

#### Boolean Strings

```php
$boolRule = new InRule(['true', 'false', '1', '0']);
```

## Migration Strategy

### 1. Gradual Migration

You can mix both approaches during migration:

```php
$rules = [
    // Keep existing string rules
    'APP_NAME' => 'required|string',

    // Migrate to objects for complex rules
    'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])],

    // Mix as needed
    'APP_KEY' => 'required|string|min:32',
    'LOG_LEVEL' => ['required', new InRule(['debug', 'info', 'error'])],
];
```

### 2. Preset Migration

The package includes both approaches in presets:

```php
// Current presets (string-based)
use EnvValidator\Core\DefaultRulePresets;
$rules = DefaultRulePresets::production();

// Future presets (object-based)
use EnvValidator\Core\DefaultRulePresetsV2;
$rules = DefaultRulePresetsV2::production();
```

### 3. Custom Rule Creation

Extend the `InRule` for domain-specific validation:

```php
class EnvironmentRule extends InRule
{
    public function __construct()
    {
        parent::__construct(
            ['local', 'development', 'staging', 'production'],
            'The :attribute must be a valid environment.'
        );
    }
}
```

## Real-World Examples

### API Gateway Configuration

```php
$validator = (new EnvValidator)
    ->useMinimalRules()
    ->addRule('API_VERSION', ['required', 'string', new InRule(['v1', 'v2', 'v3'])])
    ->addRule('RATE_LIMIT_STRATEGY', ['required', new InRule(['fixed', 'sliding', 'token_bucket'])])
    ->addRule('AUTH_PROVIDER', ['required', new InRule(['jwt', 'oauth2', 'session'])])
    ->addRule('CORS_POLICY', ['required', new InRule(['permissive', 'restrictive', 'disabled'])]);
```

### Microservice Environment

```php
$envRule = new InRule(['staging', 'production']);
$logRule = new InRule(['info', 'warning', 'error']);

$rules = [
    'SERVICE_NAME' => ['required', 'string'],
    'SERVICE_ENV' => ['required', 'string', $envRule],
    'LOG_LEVEL' => ['required', 'string', $logRule],
    'HEALTH_CHECK_URL' => ['required', 'url'],
];
```

### Multi-Environment Setup

```php
$environments = new InRule(['dev', 'test', 'staging', 'prod']);
$deploymentTypes = new InRule(['blue', 'green', 'canary']);

$rules = [
    'PRIMARY_ENV' => ['required', 'string', $environments],
    'SECONDARY_ENV' => ['required', 'string', $environments],
    'DEPLOYMENT_TYPE' => ['required', 'string', $deploymentTypes],
];
```

## Benefits Summary

### ✨ Type Safety

-   Full IDE autocompletion and intellisense
-   Static analysis support (PHPStan, Psalm)
-   Compile-time error detection

### 🔄 Reusability

-   Define rule objects once, use everywhere
-   Reduces code duplication
-   Consistent validation logic

### 🧪 Testability

```php
test('environment rule validates correctly', function () {
    $rule = new InRule(['staging', 'production']);

    expect($rule->passes('env', 'staging'))->toBeTrue();
    expect($rule->passes('env', 'invalid'))->toBeFalse();
});
```

### 📖 Readability

-   Self-documenting code
-   Clear intent and purpose
-   Better code organization

### 💬 Custom Messages

-   Context-specific error messages
-   Rich validation feedback
-   User-friendly error reporting

### 🔧 Extensibility

-   Easy to add custom logic
-   Flexible rule composition
-   Inheritance and polymorphism

## Performance Considerations

-   **Memory**: Rule objects use slightly more memory than strings
-   **Speed**: Negligible performance difference in validation
-   **Parsing**: No string parsing overhead with objects
-   **Caching**: Objects can be cached and reused efficiently

## Backward Compatibility

-   ✅ String-based rules continue to work
-   ✅ Existing configurations remain valid
-   ✅ Gradual migration is supported
-   ✅ No breaking changes to public API

## Recommendation

**Use Rule objects for new development** because they provide:

1. **Better Developer Experience**: IDE support, type safety
2. **Maintainable Code**: Reusable, testable, readable
3. **Rich Functionality**: Custom messages, extensibility
4. **Future-Proof**: Better foundation for complex validation

**Keep string rules for**:

-   Simple validation scenarios
-   Legacy code that works well
-   Quick prototyping

## Testing

The package includes comprehensive tests demonstrating both approaches:

```bash
# Run comparison tests
composer test -- tests/Unit/RuleObjectVsStringComparisonTest.php

# Run InRule specific tests
composer test -- tests/Unit/InRuleTest.php

# Run all tests
composer test
```

## Conclusion

While both approaches work perfectly, **Rule objects provide a cleaner, more maintainable solution** for complex validation scenarios. The migration can be gradual, and both approaches can coexist in the same codebase.
