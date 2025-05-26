<a href="https://devkraken.com/">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="images/header-dark.png">
    <img alt="Header Image" src="images/header-light.png">
  </picture>
</a>

# EnvValidator

> **Type-safe environment variable validation for Laravel and PHP applications**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dev-kraken/env-validator.svg?style=flat-square)](https://packagist.org/packages/dev-kraken/env-validator)
[![Total Downloads](https://img.shields.io/packagist/dt/dev-kraken/env-validator.svg?style=flat-square)](https://packagist.org/packages/dev-kraken/env-validator)
[![Build Status](https://github.com/dev-kraken/env-validator/actions/workflows/ci.yml/badge.svg)](https://github.com/dev-kraken/env-validator/actions)
[![License](https://img.shields.io/packagist/l/dev-kraken/env-validator.svg?style=flat-square)](https://packagist.org/packages/dev-kraken/env-validator)
[![PHP Version](https://img.shields.io/packagist/php-v/dev-kraken/env-validator.svg?style=flat-square)](https://packagist.org/packages/dev-kraken/env-validator)
[![Tests](https://img.shields.io/github/actions/workflow/status/dev-kraken/env-validator/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/dev-kraken/env-validator/actions)

EnvValidator is a modern, type-safe PHP package for validating environment variables in Laravel and standalone PHP applications. It provides robust validation with clear error messages, intelligent presets, and extensible rule objects.

## :sparkles: Features

-   :lock: **Type-safe validation** with PHP 8.2+ and PHPStan level max
-   :dart: **Smart presets** for common scenarios (Laravel, microservices, production, etc.)
-   :jigsaw: **Extensible rule system** with custom Rule objects
-   :rocket: **Laravel integration** with auto-discovery and Artisan commands
-   :package: **Standalone support** for non-Laravel PHP applications
-   :shield: **Production-ready** with comprehensive test coverage
-   :memo: **Clear error messages** with detailed validation feedback

## :rocket: Quick Start

### Installation

```bash
composer require dev-kraken/env-validator
```

### Laravel Usage

```php
use EnvValidator\Facades\EnvValidator;

// Validate with default Laravel rules
EnvValidator::validate();

// Use preset for different scenarios
EnvValidator::useProductionRules()->validate();
EnvValidator::useMinimalRules()->validate();
EnvValidator::useApiRules()->validate();
```

### Standalone PHP Usage

```php
use EnvValidator\EnvValidator;

$validator = new EnvValidator();
$validator->setRules([
    'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])],
    'APP_DEBUG' => ['required', new BooleanRule()],
    'APP_URL' => ['required', new UrlRule()],
]);

$validator->validate($_ENV);
```

## :clipboard: Built-in Rule Presets

| Preset         | Description                  | Use Case                         |
| -------------- | ---------------------------- | -------------------------------- |
| `laravel`      | Complete Laravel application | Full-featured web applications   |
| `minimal`      | Essential variables only     | Microservices, lightweight apps  |
| `production`   | Production-ready settings    | Deployment environments          |
| `api`          | API-focused applications     | REST APIs, headless applications |
| `microservice` | Microservice-specific        | Containerized services           |
| `docker`       | Docker/containerized apps    | Container deployments            |

### Preset Usage Examples

```php
// Laravel application
$validator = (new EnvValidator())->usePreset('laravel');

// Microservice
$validator = (new EnvValidator())->usePreset('microservice');

// Production deployment
$validator = (new EnvValidator())->useProductionRules();

// Custom combination
$validator = (new EnvValidator())
    ->useMinimalRules()
    ->addRule('CUSTOM_API_KEY', ['required', 'string', 'min:32']);
```

## :dart: Rule Objects vs String Rules

EnvValidator uses **Rule objects** for better type safety and maintainability:

### :white_check_mark: Rule Objects (Recommended)

```php
use EnvValidator\Collections\StringRules\{InRule, BooleanRule};
use EnvValidator\Collections\NetworkRules\UrlRule;

$rules = [
    'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])],
    'APP_DEBUG' => ['required', new BooleanRule()],
    'APP_URL' => ['required', new UrlRule()],
];
```

**Benefits:**

-   :mag: **IDE autocompletion** and type hints
-   :test_tube: **Easy unit testing** of individual rules
-   :recycle: **Reusable** across multiple fields
-   :art: **Custom error messages** with context
-   :wrench: **Better debugging** and inspection

### :books: Available Rule Objects

#### String Rules

-   `BooleanRule` - Validates boolean values (true, false, 1, 0, yes, no, etc.)
-   `InRule` - Validates value is in a list of allowed values
-   `KeyRule` - Validates Laravel application keys (base64: format)
-   `PatternRule` - Validates against regex patterns
-   `EmailRule` - Validates email addresses
-   `JsonRule` - Validates JSON strings

#### Numeric Rules

-   `NumericRule` - Validates numeric values
-   `IntegerRule` - Validates integer values
-   `PortRule` - Validates port numbers (1-65535)

#### Network Rules

-   `UrlRule` - Validates URLs
-   `IpRule` - Validates IP addresses

## :hammer_and_wrench: Advanced Usage

### Custom Rules

```php
use EnvValidator\Core\AbstractRule;

class CustomRule extends AbstractRule
{
    public function passes($attribute, $value): bool
    {
        return str_starts_with($value, 'custom_');
    }

    public function message(): string
    {
        return 'The :attribute must start with "custom_".';
    }
}

// Usage
$validator->addRule('CUSTOM_FIELD', [new CustomRule()]);
```

### Environment-Specific Validation

```php
// Development environment
if (app()->environment('local', 'development')) {
    $validator->useMinimalRules();
} else {
    // Production environment
    $validator->useProductionRules();
}
```

### Conditional Validation

```php
$rules = [
    'DB_HOST' => ['required_unless:DB_CONNECTION,sqlite', 'string'],
    'DB_PORT' => ['required_unless:DB_CONNECTION,sqlite', new PortRule()],
    'REDIS_HOST' => ['required_if:CACHE_DRIVER,redis', 'string'],
];
```

## :art: Laravel Integration

### Artisan Commands

```bash
# Validate all environment variables
php artisan env:validate

# Validate specific variables
php artisan env:validate --keys=APP_KEY --keys=APP_URL

# Verbose output with debugging info
php artisan env:validate -v
```

### Service Provider Configuration

```php
// config/env-validator.php
return [
    'validate_on_boot' => ['APP_KEY', 'APP_ENV'], // Validate on app boot
    'rules' => [
        'CUSTOM_VAR' => ['required', 'string'],
    ],
    'messages' => [
        'APP_KEY.required' => 'Application key is required for security.',
    ],
];
```

### Facade Usage

```php
use EnvValidator\Facades\EnvValidator;

// Method chaining
EnvValidator::useProductionRules()
    ->addRule('API_KEY', ['required', 'string', 'min:32'])
    ->validate();

// Validate specific keys only
EnvValidator::validateOnly(['APP_KEY', 'APP_ENV']);
```

## :test_tube: Testing

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test -- --coverage

# Run specific test suite
vendor/bin/pest tests/Unit
vendor/bin/pest tests/Feature
```

### Code Quality

```bash
# Static analysis
composer analyse

# Code style check
composer cs

# Fix code style
composer cs:fix

# Run all checks
composer check
```

## :book: Examples

### Real-World Scenarios

```php
// E-commerce application
$validator = (new EnvValidator())
    ->usePreset('laravel')
    ->addRules([
        'STRIPE_KEY' => ['required', 'string', 'min:32'],
        'STRIPE_SECRET' => ['required', 'string', 'min:32'],
        'PAYMENT_WEBHOOK_SECRET' => ['required', 'string'],
    ]);

// Microservice with health checks
$validator = (new EnvValidator())
    ->usePreset('microservice')
    ->addRules([
        'HEALTH_CHECK_ENDPOINT' => ['required', new UrlRule()],
        'SERVICE_TIMEOUT' => ['required', 'integer', 'min:1', 'max:300'],
    ]);

// Multi-environment configuration
$rules = match(env('APP_ENV')) {
    'production' => DefaultRulePresets::production(),
    'staging' => DefaultRulePresets::production(),
    'testing' => DefaultRulePresets::minimal(),
    default => DefaultRulePresets::laravel(),
};
```

## :handshake: Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

```bash
git clone https://github.com/dev-kraken/env-validator.git
cd env-validator
composer install
composer test
```

## :page_facing_up: License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## :lock: Security

If you discover any security vulnerabilities, please send an email to soman@devkraken.com instead of using the issue tracker.

## :telephone_receiver: Support

-   :email: **Email**: soman@devkraken.com
-   :bug: **Issues**: [GitHub Issues](https://github.com/dev-kraken/env-validator/issues)
-   :speech_balloon: **Discussions**: [GitHub Discussions](https://github.com/dev-kraken/env-validator/discussions)

---

<p align="center">
Made with :heart: by <a href="https://devkraken.com">Dev Kraken</a>
</p>
