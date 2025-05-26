# Contributing to EnvValidator

Thank you for your interest in contributing to EnvValidator! This guide will help you understand how to contribute effectively to this project.

## Table of Contents

-   [Getting Started](#getting-started)
-   [Development Setup](#development-setup)
-   [Code Architecture](#code-architecture)
-   [Adding New Rule Types](#adding-new-rule-types)
-   [Writing Tests](#writing-tests)
-   [Code Style](#code-style)
-   [Submitting Changes](#submitting-changes)

## Getting Started

EnvValidator is a Laravel package for validating environment variables with support for standalone usage (without Laravel). The package is designed with extensibility and maintainability in mind.

### Prerequisites

-   PHP 8.2 or higher
-   Composer 2.2+
-   Basic understanding of Laravel validation concepts
-   Git for version control

## Development Setup

1. **Clone the repository:**

    ```bash
    git clone https://github.com/your-username/env-validator.git
    cd env-validator
    ```

2. **Install dependencies:**

    ```bash
    composer install
    ```

3. **Run tests:**

    ```bash
    ./vendor/bin/pest
    ```

4. **Run static analysis:**
    ```bash
    ./vendor/bin/phpstan analyse
    ```

## Code Architecture

The project follows SOLID principles and is organized into several key components:

### Core Components

-   **`AbstractRule`**: Base class for all validation rules
-   **`StandaloneValidator`**: Main validator for non-Laravel environments
-   **`RuleRegistry`**: Manages and organizes validation rules
-   **`RuleFactory`**: Creates rule instances dynamically

### Validator System

The standalone validator uses a pluggable system with dedicated validators:

-   **`StringRuleValidator`**: Handles string-based rules like `'in:value1,value2'`
-   **`ValidationRuleValidator`**: Handles Laravel `ValidationRule` objects
-   **`BuiltInRuleValidator`**: Handles package's built-in rules

### Rule Collections

Rules are organized by category:

-   **`StringRules/`**: String validation rules (BooleanRule, KeyRule, PatternRule)
-   **`NumericRules/`**: Numeric validation rules (NumericRule, IntegerRule)
-   **`NetworkRules/`**: Network-related rules (UrlRule, IpRule)

## Adding New Rule Types

### Creating a New Rule

1. **Create the rule class:**

    ```php
    <?php

    namespace EnvValidator\Collections\YourCategory;

    use EnvValidator\Core\AbstractRule;

    final class YourRule extends AbstractRule
    {
        public function passes($attribute, $value)
        {
            // Your validation logic here
            return true;
        }

        public function message()
        {
            return 'The :attribute validation failed.';
        }
    }
    ```

2. **Register the rule (optional):**

    ```php
    // In a service provider or configuration
    $ruleRegistry = app(RuleRegistry::class);
    $ruleRegistry->register('your-category', 'your-rule', YourRule::class);
    ```

3. **Add tests:**

    ```php
    test('it validates with YourRule', function () {
        $validator = new EnvValidator;
        $validator->setRules(['TEST_VAR' => [new YourRule()]]);

        $env = ['TEST_VAR' => 'valid-value'];
        expect($validator->validate($env))->toBeTrue();
    });
    ```

### Creating a Custom Validator

If you need to handle a completely new type of rule format:

1. **Implement the interface:**

    ```php
    <?php

    namespace Your\Namespace;

    use EnvValidator\Contracts\RuleValidatorInterface;

    final class YourCustomValidator implements RuleValidatorInterface
    {
        public function validate(string $key, mixed $rule, mixed $value, array $messages, array &$errors): void
        {
            // Your validation logic
        }

        public function canHandle(mixed $rule): bool
        {
            // Return true if this validator can handle the rule
            return false;
        }
    }
    ```

2. **Register with the validator:**
    ```php
    $validator = new StandaloneValidator();
    $validator->addValidator(new YourCustomValidator());
    ```

## Writing Tests

We use Pest for testing. Tests should be comprehensive and cover:

-   **Happy path scenarios** (valid inputs)
-   **Error scenarios** (invalid inputs)
-   **Edge cases** (empty values, null values, etc.)
-   **Integration scenarios** (multiple rules together)

### Test Examples

```php
test('it validates valid environment variables', function () {
    $validator = new EnvValidator;

    $env = [
        'APP_NAME' => 'Test App',
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
    ];

    expect($validator->validate($env))->toBeTrue();
});

test('it fails with invalid values', function () {
    $validator = new EnvValidator;

    $env = ['APP_DEBUG' => 'invalid-boolean'];

    expect(fn () => $validator->validate($env))
        ->toThrow(InvalidEnvironmentException::class);
});
```

## Code Style

### PHP Standards

-   Follow PSR-12 coding standards
-   Use strict types: `declare(strict_types=1);`
-   Type hint everything (parameters, return types, properties)
-   Use readonly properties where appropriate

### Documentation

-   All public methods must have PHPDoc comments
-   Include `@param` and `@return` tags with proper types
-   Add `@throws` tags for exceptions
-   Include usage examples for complex rules

### Example:

````php
/**
 * Validate that a value matches a specific pattern.
 *
 * @param  string  $pattern  The regular expression pattern
 * @param  string|null  $customMessage  A custom error message
 *
 * @example
 * ```php
 * // Validate an IP address
 * $rule = new PatternRule('/^(\d{1,3}\.){3}\d{1,3}$/');
 * ```
 */
public function __construct(
    private readonly string $pattern,
    private readonly ?string $customMessage = null
) {}
````

### Naming Conventions

-   Classes: `PascalCase` (e.g., `BooleanRule`)
-   Methods: `camelCase` (e.g., `validateField`)
-   Variables: `camelCase` (e.g., `$ruleRegistry`)
-   Constants: `SCREAMING_SNAKE_CASE` (e.g., `DEFAULT_MESSAGE`)

## Submitting Changes

### Before Submitting

1. **Run tests:** `./vendor/bin/pest`
2. **Run static analysis:** `./vendor/bin/phpstan analyse`
3. **Check code style:** Ensure PSR-12 compliance
4. **Update documentation:** If adding new features

### Pull Request Process

1. **Fork the repository**
2. **Create a feature branch:** `git checkout -b feature/your-feature-name`
3. **Make your changes**
4. **Add tests** for your changes
5. **Commit with clear messages:**

    ```
    feat: add support for custom validation patterns

    - Add PatternRule for regex-based validation
    - Include comprehensive tests
    - Update documentation with examples
    ```

6. **Push to your fork:** `git push origin feature/your-feature-name`
7. **Create a Pull Request**

### Commit Message Format

Use conventional commits:

-   `feat:` for new features
-   `fix:` for bug fixes
-   `docs:` for documentation changes
-   `test:` for adding tests
-   `refactor:` for code refactoring
-   `perf:` for performance improvements

## Getting Help

-   **Issues:** Use GitHub issues for bug reports and feature requests
-   **Discussions:** Use GitHub discussions for questions and general discussion
-   **Documentation:** Check the README and code comments

## Recognition

Contributors will be added to the contributors list in the README. Significant contributions may be recognized in release notes.

Thank you for contributing to EnvValidator!
