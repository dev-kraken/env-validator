# Changelog

All notable changes to `env-validator` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
