<?php

require_once __DIR__.'/../vendor/autoload.php';

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\EmailRule;
use EnvValidator\Collections\StringRules\InRule;
use EnvValidator\EnvValidator;

echo "[*] Required Field Validation Examples\n";
echo "====================================\n\n";

// 1. Basic Required Field Validation
echo "1. Basic Required Field Validation:\n";
echo "   Understanding how required validation works\n\n";

// Test environment with some missing required fields
$testEnv = [
    'APP_NAME' => 'My Application',
    'APP_URL' => 'https://myapp.com',
    // APP_ENV is missing (required)
    // APP_DEBUG is missing (required)
];

// Rules with required fields
$rules = [
    'APP_NAME' => ['required', 'string'],
    'APP_ENV' => ['required', 'string'],
    'APP_DEBUG' => ['required', new BooleanRule],
    'APP_URL' => ['required', new UrlRule],
    'OPTIONAL_SETTING' => ['nullable', 'string'],  // Not required
];

echo "   Testing environment with missing required fields:\n";
$result = EnvValidator::validateStandalone($testEnv, $rules);

if ($result === true) {
    echo "   [✓] Validation passed\n";
} else {
    echo "   [✗] Validation failed with errors:\n";
    foreach ($result as $field => $errors) {
        foreach ($errors as $error) {
            echo "     • $field: $error\n";
        }
    }
}

echo "\n";

// 2. Different Rule Format Examples
echo "2. Different Rule Format Examples:\n";
echo "   Array vs String rule formats for required validation\n\n";

$scenarios = [
    'Array with required first' => ['required', 'string'],
    'Array with required last' => ['string', 'required'],
    'Array with only required' => ['required'],
    'String pipe-separated' => 'required|string',
    'String with only required' => 'required',
];

$emptyEnv = []; // No APP_ENV key

foreach ($scenarios as $description => $rule) {
    echo "   Scenario: $description\n";
    echo '   Rule: '.(is_array($rule) ? json_encode($rule) : "'$rule'")."\n";

    $result = EnvValidator::validateStandalone($emptyEnv, ['APP_ENV' => $rule]);

    if ($result === true) {
        echo "   [!]  Validation passed (this would be a bug!)\n";
    } else {
        echo '   [✓] Validation correctly failed: '.$result['APP_ENV'][0]."\n";
    }
    echo "\n";
}

// 3. Required vs Optional Fields Comparison
echo "3. Required vs Optional Fields Comparison:\n";
echo "   Demonstrating the difference between required and optional fields\n\n";

$partialEnv = [
    'REQUIRED_FIELD' => 'present',
    // OPTIONAL_FIELD is missing
    // ANOTHER_REQUIRED is missing
];

$mixedRules = [
    'REQUIRED_FIELD' => ['required', 'string'],
    'OPTIONAL_FIELD' => ['nullable', 'string'],      // Optional - won't fail if missing
    'ANOTHER_OPTIONAL' => ['string'],                // Optional - won't fail if missing
    'ANOTHER_REQUIRED' => ['required', 'string'],    // Required - will fail if missing
];

echo '   Environment: '.json_encode($partialEnv)."\n";
echo "   Rules:\n";
foreach ($mixedRules as $key => $rule) {
    $isRequired = is_array($rule) ? in_array('required', $rule) : str_contains($rule, 'required');
    $status = $isRequired ? 'REQUIRED' : 'OPTIONAL';
    $ruleStr = is_array($rule) ? json_encode($rule) : "'$rule'";
    echo "     • $key: $ruleStr ($status)\n";
}

echo "\n   Validation result:\n";
$result = EnvValidator::validateStandalone($partialEnv, $mixedRules);

if ($result === true) {
    echo "   [✓] All validations passed\n";
} else {
    echo "   [✗] Validation failed for:\n";
    foreach ($result as $field => $errors) {
        foreach ($errors as $error) {
            echo "     • $field: $error\n";
        }
    }
}

echo "\n";

// 4. Required Fields with Empty Values
echo "4. Required Fields with Empty Values:\n";
echo "   Required fields fail on both missing keys AND empty values\n\n";

$testCases = [
    'Missing key' => [],
    'Empty string' => ['APP_ENV' => ''],
    'Null value' => ['APP_ENV' => null],
    'Valid value' => ['APP_ENV' => 'production'],
];

foreach ($testCases as $caseName => $env) {
    echo "   Test case: $caseName\n";
    $result = EnvValidator::validateStandalone($env, ['APP_ENV' => ['required', 'string']]);

    if ($result === true) {
        echo "   [✓] Validation passed\n";
    } else {
        echo '   [✗] Validation failed: '.$result['APP_ENV'][0]."\n";
    }
    echo "\n";
}

// 5. Real-World Required Field Example
echo "5. Real-World Required Field Example:\n";
echo "   Production application with essential required fields\n\n";

// Simulate a production .env file with some missing required values
$productionEnv = [
    'APP_NAME' => 'Production App',
    'APP_URL' => 'https://myapp.com',
    'APP_ENV' => 'production',
    // Missing: APP_KEY, DB_PASSWORD, MAIL_PASSWORD
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_DATABASE' => 'app_db',
    'DB_USERNAME' => 'app_user',
    'MAIL_HOST' => 'smtp.mailtrap.io',
    'MAIL_PORT' => '587',
    'MAIL_USERNAME' => 'user@example.com',
    'MAIL_FROM_ADDRESS' => 'noreply@myapp.com',
];

// Production rules with required security-critical fields
$productionRules = [
    // Application basics
    'APP_NAME' => ['required', 'string'],
    'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])],
    'APP_KEY' => ['required', 'string', 'min:32'],  // MISSING - Security critical!
    'APP_URL' => ['required', new UrlRule],

    // Database configuration
    'DB_HOST' => ['required', 'string'],
    'DB_PORT' => ['required', 'integer', 'min:1', 'max:65535'],
    'DB_DATABASE' => ['required', 'string'],
    'DB_USERNAME' => ['required', 'string'],
    'DB_PASSWORD' => ['required', 'string', 'min:8'],  // MISSING - Security critical!

    // Mail configuration
    'MAIL_HOST' => ['required', 'string'],
    'MAIL_PORT' => ['required', 'integer'],
    'MAIL_USERNAME' => ['required', 'string'],
    'MAIL_PASSWORD' => ['required', 'string'],  // MISSING - Required for SMTP
    'MAIL_FROM_ADDRESS' => ['required', new EmailRule],

    // Optional but recommended
    'MAIL_FROM_NAME' => ['nullable', 'string'],
    'APP_TIMEZONE' => ['nullable', 'string'],
];

echo "   Validating production environment configuration...\n\n";
$result = EnvValidator::validateStandalone($productionEnv, $productionRules);

if ($result === true) {
    echo "   [✓] Production environment is properly configured!\n";
} else {
    echo "   [✗] Production environment has configuration issues:\n\n";
    echo "   🚨 CRITICAL: Missing required environment variables:\n";
    foreach ($result as $field => $errors) {
        foreach ($errors as $error) {
            echo "     • $field: $error\n";
        }
    }
    echo "\n   [*] Fix these issues before deploying to production!\n";
}

echo "\n";

// 6. Custom Error Messages for Required Fields
echo "6. Custom Error Messages for Required Fields:\n";
echo "   Providing helpful error messages for missing required fields\n\n";

$incompleteEnv = [
    'APP_NAME' => 'Test App',
    // Missing critical fields
];

$rulesWithCustomMessages = [
    'APP_KEY' => ['required', 'string', 'min:32'],
    'DB_PASSWORD' => ['required', 'string', 'min:8'],
    'STRIPE_SECRET_KEY' => ['required', 'string'],
];

$customMessages = [
    'APP_KEY.required' => '🔑 APP_KEY is required! Generate one with: php artisan key:generate',
    'DB_PASSWORD.required' => '🗄️  DB_PASSWORD is required for database security. Set a strong password.',
    'STRIPE_SECRET_KEY.required' => '💳 STRIPE_SECRET_KEY is required for payment processing.',
];

$result = EnvValidator::validateStandalone($incompleteEnv, $rulesWithCustomMessages, $customMessages);

if ($result !== true) {
    echo "   Custom error messages:\n";
    foreach ($result as $field => $errors) {
        foreach ($errors as $error) {
            echo "     $error\n";
        }
    }
}

echo "\n";

// 7. Best Practices Summary
echo "7. Best Practices for Required Field Validation:\n";
echo "   Tips for effective environment variable validation\n\n";

echo "   [✓] DO:\n";
echo "     • Use 'required' for security-critical variables (keys, passwords)\n";
echo "     • Use 'nullable' for truly optional settings\n";
echo "     • Combine 'required' with type validation (['required', 'string'])\n";
echo "     • Provide helpful custom error messages\n";
echo "     • Validate early in application bootstrap\n";
echo "     • Use different rule sets for different environments\n\n";

echo "   [✗] DON'T:\n";
echo "     • Assume environment variables exist without validation\n";
echo "     • Use empty strings as default values for required fields\n";
echo "     • Skip validation in production environments\n";
echo "     • Make security-critical variables optional\n\n";

echo "   [*] Rule Format Compatibility:\n";
echo "     • Array format: ['required', 'string'] [✓] (Recommended)\n";
echo "     • String format: 'required|string' [✓] (Also supported)\n";
echo "     • Both formats work in Laravel and standalone PHP\n\n";

echo ">>  Required Field Validation Examples Complete!\n";
echo "   All examples demonstrate proper required field handling\n";
echo "   Your environment variables are now properly validated! [*]\n";
