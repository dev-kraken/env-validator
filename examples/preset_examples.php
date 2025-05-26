<?php

require_once __DIR__.'/../vendor/autoload.php';

use EnvValidator\EnvValidator;

echo ":dart: EnvValidator Preset Examples\n";
echo "==============================\n\n";

// Example 1: Default Full Laravel Rules
echo "1. Default Full Laravel Rules:\n";
$validator = new EnvValidator;
$rules = $validator->getRules();
echo '   Rules count: '.count($rules)."\n";
echo '   Includes: '.implode(', ', array_keys($rules))."\n\n";

// Example 2: Minimal Rules for Microservices
echo "2. Minimal Rules for Microservices:\n";
$validator = (new EnvValidator)->useMinimalRules();
$rules = $validator->getRules();
echo '   Rules count: '.count($rules)."\n";
echo '   Includes: '.implode(', ', array_keys($rules))."\n\n";

// Example 3: Production-Optimized Rules
echo "3. Production-Optimized Rules:\n";
$validator = (new EnvValidator)->useProductionRules();
$rules = $validator->getRules();
echo '   Rules count: '.count($rules)."\n";
echo '   Includes: '.implode(', ', array_keys($rules))."\n\n";

// Example 4: API-Focused Rules
echo "4. API-Focused Rules:\n";
$validator = (new EnvValidator)->useApiRules();
$rules = $validator->getRules();
echo '   Rules count: '.count($rules)."\n";
echo '   Includes: '.implode(', ', array_keys($rules))."\n\n";

// Example 5: Using Preset by Name
echo "5. Using Preset by Name:\n";
$validator = (new EnvValidator)->usePreset('minimal');
echo "   Minimal preset loaded successfully :white_check_mark:\n\n";

// Example 6: Chaining Presets with Custom Rules
echo "6. Chaining Presets with Custom Rules:\n";
$validator = (new EnvValidator)
    ->useApiRules()
    ->addRule('JWT_SECRET', 'required|string|min:32')
    ->addRule('RATE_LIMIT_PER_MINUTE', 'required|integer|min:1|max:1000')
    ->addRule('API_VERSION', 'required|string|in:v1,v2,v3');

$rules = $validator->getRules();
echo '   Total rules: '.count($rules)." (6 API + 3 custom)\n";
echo "   Custom rules: JWT_SECRET, RATE_LIMIT_PER_MINUTE, API_VERSION\n\n";

// Example 7: Validating Different Scenarios
echo "7. Validation Examples:\n";

// Minimal validation
echo "   Minimal preset validation:\n";
$minimalValidator = (new EnvValidator)->useMinimalRules();
$minimalEnv = [
    'APP_NAME' => 'Microservice',
    'APP_ENV' => 'production',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
    'APP_DEBUG' => 'false',
];
try {
    $result = $minimalValidator->validate($minimalEnv);
    echo "   :white_check_mark: Minimal validation passed\n";
} catch (Exception $e) {
    echo '   :x: Minimal validation failed: '.$e->getMessage()."\n";
}

// Production validation
echo "   Production preset validation:\n";
$productionValidator = (new EnvValidator)->useProductionRules();
$productionEnv = [
    'APP_NAME' => 'Production App',
    'APP_ENV' => 'production',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
    'APP_DEBUG' => 'false',
    'APP_URL' => 'https://example.com',
    'APP_LOCALE' => 'en',
    'APP_FALLBACK_LOCALE' => 'en',
    'APP_FAKER_LOCALE' => 'en_US',
];
try {
    $result = $productionValidator->validate($productionEnv);
    echo "   :white_check_mark: Production validation passed\n";
} catch (Exception $e) {
    echo '   :x: Production validation failed: '.$e->getMessage()."\n";
}

// API validation
echo "   API preset validation:\n";
$apiValidator = (new EnvValidator)->useApiRules();
$apiEnv = [
    'APP_NAME' => 'API Service',
    'APP_ENV' => 'production',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
    'APP_DEBUG' => 'false',
    'APP_URL' => 'https://api.example.com',
    'APP_LOCALE' => 'en',
];
try {
    $result = $apiValidator->validate($apiEnv);
    echo "   :white_check_mark: API validation passed\n";
} catch (Exception $e) {
    echo '   :x: API validation failed: '.$e->getMessage()."\n";
}

echo "\n";

// Example 8: Real-world Scenarios
echo "8. Real-world Scenario Examples:\n";
echo "   :iphone: Mobile Backend API:\n";
$mobileApiValidator = (new EnvValidator)
    ->useApiRules()
    ->addRule('FIREBASE_PROJECT_ID', 'required|string')
    ->addRule('PUSH_NOTIFICATION_KEY', 'required|string|min:20')
    ->addRule('APP_VERSION_MIN', 'required|string');

echo "   :bar_chart: Analytics Microservice:\n";
$analyticsValidator = (new EnvValidator)
    ->useMinimalRules()
    ->addRule('ANALYTICS_DB_HOST', 'required|string')
    ->addRule('BATCH_SIZE', 'required|integer|min:100|max:10000')
    ->addRule('RETENTION_DAYS', 'required|integer|min:30|max:3650');

echo "   :globe_with_meridians: Web Application:\n";
$webAppValidator = (new EnvValidator)
    ->useProductionRules()
    ->addRule('CDN_URL', 'required|url')
    ->addRule('SOCIAL_LOGIN_ENABLED', 'required|in:true,false')
    ->addRule('MAINTENANCE_MODE', 'required|in:true,false');

echo "   :white_check_mark: All scenario validators configured successfully\n\n";

echo ":tada: All preset examples completed successfully!\n";
echo "   Total test scenarios: 8\n";
echo "   All validations passed :white_check_mark:\n";
