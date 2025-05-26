<?php

/**
 * Env Validator configuration.
 *
 * @author Dev Kraken
 *
 * @link   https://devkraken.com
 * @see    https://github.com/dev-kraken/env-validator
 *
 * @issues https://github.com/dev-kraken/env-validator/issues
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Environment Variables Validation
    |--------------------------------------------------------------------------
    |
    | This configuration file controls how the environment variables are
    | validated in your application. You can define custom rules, error
    | messages, and control the validation behavior.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Automatic Validation
    |--------------------------------------------------------------------------
    |
    | When set to true, environment variables will be automatically validated
    | when your application boots. This is useful for detecting missing or
    | invalid environment variables early in the application lifecycle.
    |
    */
    'auto_validate' => env('ENV_AUTO_VALIDATE', false),

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Rules
    |--------------------------------------------------------------------------
    |
    | Define custom validation rules for your environment variables here.
    | These rules will be merged with the default rules unless you use
    | the setRules() method to replace them entirely.
    |
    | Available rule types:
    | - String Rules: BooleanRule, KeyRule, PatternRule
    | - Numeric Rules: NumericRule, IntegerRule
    | - Network Rules: UrlRule, IpRule
    |
    | Examples:
    | 'APP_URL' => ['required', \EnvValidator\Collections\NetworkRules\UrlRule::class],
    | 'MAIL_PORT' => ['required', \EnvValidator\Collections\NumericRules\IntegerRule::class],
    | 'DEBUG_MODE' => ['required', \EnvValidator\Collections\StringRules\BooleanRule::class],
    |
    */
    'rules' => [
        // Your custom rules here
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Error Messages
    |--------------------------------------------------------------------------
    |
    | You can customize the error messages for specific validation rules.
    | The key should be in the format: 'variable.rule'
    |
    | Examples:
    | 'APP_KEY.required' => 'The application key is missing. Generate one using "php artisan key:generate"',
    | 'APP_URL.url' => 'The application URL must start with https for security reasons.',
    |
    */
    'messages' => [
        // Your custom messages here
    ],

    /*
    |--------------------------------------------------------------------------
    | Validate on Boot
    |--------------------------------------------------------------------------
    |
    | You can specify which environment variables should be validated when
    | auto_validate is enabled. If this array is empty, all variables will
    | be validated. This is useful for validating only critical variables.
    |
    | Examples: ['APP_KEY', 'APP_URL', 'DB_CONNECTION']
    |
    */
    'validate_on_boot' => [
        // List of variables to validate on boot
    ],
];
