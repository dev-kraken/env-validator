# Changelog

All notable changes to `env-validator` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-05-27

### :bug: Fixed

-   **Critical Fix**: Required field validation now works correctly with array rules in standalone PHP
    -   Fixed `StandaloneValidator::isRequiredAndMissing()` to properly handle array rules like `['required', 'string']`
    -   Previously only worked with string rules like `'required|string'`
    -   Now both `['required', 'string']` and `'required|string'` formats work correctly
    -   Ensures missing required environment variables are properly detected and reported

### :art: Added

-   **Environment File Synchronization System** - Complete solution for keeping `.env` and `.env.example` synchronized
    -   **New Service**: `EnvExampleSyncService` with intelligent categorization and security features
    -   **New Command**: `php artisan env:sync` with multiple options (`--check`, `--force`, `--no-values`, `--remove-extra`)
    -   **New Facade**: `EnvSync` for programmatic usage in applications
    -   **Standalone PHP Support**: Works seamlessly in non-Laravel applications with explicit path configuration
    -   **Security Features**: Automatic sensitive data masking and smart example value generation
    -   **28 comprehensive tests** covering all sync scenarios and edge cases
-   **New Example File**: `examples/required_field_examples.php` - Comprehensive required field validation examples
    -   Demonstrates different rule formats (array vs string)
    -   Shows required vs optional field handling
    -   Includes real-world production scenarios
    -   Covers custom error messages for required fields
    -   Best practices and troubleshooting guide
-   **New Example File**: `examples/env_sync_examples.php` - Complete environment synchronization demonstrations
    -   Shows real-world scenarios where developers add new environment variables
    -   Demonstrates security features and sensitive data handling
    -   Includes CI/CD integration examples and best practices
    -   Covers programmatic usage and validation rule suggestions
-   **New Example File**: `examples/standalone_env_sync_examples.php` - Standalone PHP environment synchronization
    -   Comprehensive guide for using environment sync in non-Laravel applications
    -   Demonstrates explicit path configuration and error handling
    -   Shows integration with deployment scripts and validation workflows
    -   Covers different project structures and best practices
-   **New Test File**: `tests/Unit/ReadmeExamplesTest.php` - Comprehensive test coverage for README examples
    -   Tests both basic (string rules) and advanced (Rule objects) standalone examples
    -   Validates required field validation section examples work correctly
    -   Ensures mixed required/optional field scenarios work as documented
    -   Verifies custom error message functionality in standalone mode
    -   Guarantees all README code examples are functional and accurate

### :books: Improved

-   **Enhanced Documentation**: Added comprehensive "Environment File Synchronization" section to README
    -   Complete command reference with examples and sample outputs
    -   **Standalone PHP Usage**: Detailed documentation for non-Laravel applications
    -   Security features documentation with sensitive data handling
    -   CI/CD integration examples and best practices
    -   Programmatic usage with facade and service examples
    -   Different project structure configurations and deployment script integration
    -   Updated features list and package description to highlight sync capabilities
-   **Enhanced Documentation**: Added dedicated "Required Field Validation" section to README
    -   Clear examples of both array and string rule formats
    -   Explanation of when required validation fails
    -   Updated examples section with new file listings
-   **Fixed README**: Corrected Standalone PHP Usage examples
    -   Added missing `use` statements for Rule objects
    -   Changed from `$validator->validate()` (Laravel) to `EnvValidator::validateStandalone()` (standalone)
    -   Added both basic (string rules) and advanced (rule objects) examples
    -   All examples now work correctly without fatal errors

---

[1.1.0]: https://github.com/dev-kraken/env-validator/releases/tag/v1.1.0

## [1.0.0] - 2025-05-26

### :rocket: Initial Release

#### :art: Intelligent Rule Presets System

-   `DefaultRulePresets` class for organized rule management
-   **10 built-in presets** for different use cases:
    -   `laravel` - Complete Laravel application rules (default)
    -   `minimal` - Essential variables only (perfect for microservices)
    -   `production` - Production-ready settings with stricter validation
    -   `api` - API-focused applications
    -   `microservice` - Microservice-specific validation
    -   `docker` - Docker/containerized applications
    -   `database` - Database configuration validation
    -   `cache` - Cache and session driver validation
    -   `queue` - Queue connection validation
    -   `mail` - Email service configuration
    -   `logging` - Logging configuration
-   Fluent API methods:
    -   `->useMinimalRules()` - Switch to minimal preset
    -   `->useProductionRules()` - Switch to production preset
    -   `->useApiRules()` - Switch to API preset
    -   `->useFullRules()` - Switch back to full Laravel preset
    -   `->usePreset('name')` - Use any preset by name

#### :broom: Code Quality & Architecture

-   Clean, duplicate-free codebase architecture
-   Helper methods for common operations:
    -   `mergeEnvironmentSources()` - Centralized environment variable merging
    -   `getConfigMessages()` - Config message loading and merging
    -   `createReadableAttributes()` - Attribute name creation from rule keys
    -   `processErrorMessages()` - Error message processing and cleanup
    -   `createCleanMessage()` - Clean validation failure message creation
-   Maintainable and consistent code structure
-   Enhanced type safety with proper PHPDoc annotations

#### :gear: Core Features

-   **Type-safe validation** with PHP 8.2+ support
-   **Laravel integration** with auto-discovery and Artisan commands
-   **Standalone PHP support** for non-Laravel applications
-   **9 Rule Objects** for extensible validation:
    -   `BooleanRule` - Boolean value validation
    -   `InRule` - Value-in-list validation with strict/loose modes
    -   `KeyRule` - Laravel application key validation
    -   `PatternRule` - Regex pattern validation
    -   `EmailRule` - Email address validation
    -   `JsonRule` - JSON string validation
    -   `NumericRule` - Numeric value validation
    -   `PortRule` - Port number validation (1-65535)
    -   `UrlRule` - URL validation
-   **Artisan command** (`php artisan env:validate`)
-   Custom error messages with context
-   Selective validation of specific environment variables

#### :wrench: Technical Excellence

-   **147 tests** with 432 assertions - comprehensive test coverage
-   **PHPStan level max** with zero errors - perfect static analysis
-   **Laravel Pint compliant** - consistent code style
-   **PHP 8.2+ type safety** with strict types and comprehensive PHPDoc
-   **Production-ready architecture** with SOLID principles
-   **GitHub CI/CD** with automated testing across PHP 8.2/8.3 and Laravel 11/12
-   **Optimized performance** with efficient environment merging
-   **Clean codebase** with clear separation of concerns

#### :books: Documentation & Examples

-   **Comprehensive README** with real-world examples
-   **Professional documentation** with badges and modern formatting
-   **8 usage scenarios** covering e-commerce, microservices, APIs
-   **Rule objects vs string rules** comparison and benefits
-   **Best practices guide** for different deployment types

---

[1.0.0]: https://github.com/dev-kraken/env-validator/releases/tag/v1.0.0
