<?php

require_once __DIR__.'/../vendor/autoload.php';

use EnvValidator\Collections\StringRules\InRule;
use EnvValidator\Core\DefaultRulePresets;
use EnvValidator\EnvValidator;

echo ":rocket: Rule Objects Demonstration\n";
echo "=============================\n\n";

// 1. Rule object approach
echo "1. Rule Object Approach:\n";
echo "   Code: 'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])]\n";

$objectRules = [
    'APP_ENV' => ['required', 'string', new InRule(['staging', 'production'])],
    'LOG_LEVEL' => ['required', 'string', new InRule(['debug', 'info', 'warning', 'error'])],
    'CACHE_DRIVER' => ['required', 'string', new InRule(['file', 'redis', 'memcached'])],
];

$validator = (new EnvValidator)->setRules($objectRules);
echo "   :white_check_mark: Rules defined successfully\n\n";

// 2. Validation demonstration
echo "2. Validation Demonstration:\n";
$testEnv = [
    'APP_ENV' => 'production',
    'LOG_LEVEL' => 'error',
    'CACHE_DRIVER' => 'redis',
];

echo "   Environment to validate:\n";
foreach ($testEnv as $key => $value) {
    echo "   - $key: $value\n";
}

try {
    $result = $validator->validate($testEnv);
    echo "   :white_check_mark: Rule object validation: PASSED\n";
} catch (Exception $e) {
    echo '   :x: Rule object validation: FAILED - '.$e->getMessage()."\n";
}

echo "\n";

// 3. Reusability demonstration
echo "3. Reusability Benefits:\n";

$environmentRule = new InRule(['local', 'development', 'staging', 'production']);
$logLevelRule = new InRule(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug']);

$reusableRules = [
    'APP_ENV' => ['required', 'string', $environmentRule],
    'TESTING_ENV' => ['required', 'string', $environmentRule], // Reused!
    'FALLBACK_ENV' => ['required', 'string', $environmentRule], // Reused again!
    'LOG_LEVEL' => ['required', 'string', $logLevelRule],
    'ERROR_LOG_LEVEL' => ['required', 'string', $logLevelRule], // Reused!
];

echo "   :white_check_mark: Same rule objects reused across multiple fields\n";
echo "   - environmentRule used 3 times\n";
echo "   - logLevelRule used 2 times\n\n";

// 4. Custom error messages
echo "4. Custom Error Messages:\n";

$productionEnvRule = new InRule(
    ['staging', 'production'],
    'The :attribute must be either staging or production for live environments.'
);

$customMessageRules = [
    'APP_ENV' => ['required', 'string', $productionEnvRule],
];

$validator3 = (new EnvValidator)->setRules($customMessageRules);

try {
    $validator3->validate(['APP_ENV' => 'development']);
} catch (Exception $e) {
    echo '   Custom error message: '.$e->getMessage()."\n";
    echo "   :white_check_mark: Contextual error messages work perfectly\n\n";
}

// 5. Type safety demonstration
echo "5. Type Safety Benefits:\n";

$rule = new InRule(['staging', 'production']);
echo '   Valid values: '.implode(', ', $rule->getValidValues())."\n";
echo '   Strict mode: '.($rule->isStrict() ? 'enabled' : 'disabled')."\n";
echo "   Test 'staging': ".($rule->passes('test', 'staging') ? 'PASS' : 'FAIL')."\n";
echo "   Test 'invalid': ".($rule->passes('test', 'invalid') ? 'PASS' : 'FAIL')."\n";
echo "   :white_check_mark: IDE autocompletion and static analysis friendly\n\n";

// 6. Preset system demonstration
echo "6. Preset System Demonstration:\n";

echo "   DefaultRulePresets (Rule object-based):\n";
$preset = DefaultRulePresets::production();
foreach ($preset as $key => $rule) {
    $ruleStr = is_array($rule) ? 'array['.count($rule).']' : $rule;
    echo "   - $key: $ruleStr\n";
}

echo "\n   :white_check_mark: All presets now use Rule objects for better maintainability\n";
echo "\n";

// 7. Real-world scenario
echo "7. Real-World Scenario - API Gateway Configuration:\n";

$apiGatewayValidator = (new EnvValidator)
    ->useMinimalRules()
    ->addRule('API_VERSION', ['required', 'string', new InRule(['v1', 'v2', 'v3'])])
    ->addRule('RATE_LIMIT_STRATEGY', ['required', new InRule(['fixed', 'sliding', 'token_bucket'])])
    ->addRule('AUTH_PROVIDER', ['required', new InRule(['jwt', 'oauth2', 'session'], 'Authentication provider must be jwt, oauth2, or session')])
    ->addRule('CORS_POLICY', ['required', new InRule(['permissive', 'restrictive', 'disabled'])]);

$apiEnv = [
    'APP_NAME' => 'API Gateway',
    'APP_ENV' => 'production',
    'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
    'APP_DEBUG' => 'false',
    'API_VERSION' => 'v2',
    'RATE_LIMIT_STRATEGY' => 'sliding',
    'AUTH_PROVIDER' => 'jwt',
    'CORS_POLICY' => 'restrictive',
];

try {
    $apiGatewayValidator->validate($apiEnv);
    echo "   :white_check_mark: API Gateway configuration validated successfully\n";
    echo '   Rules count: '.count($apiGatewayValidator->getRules())." (4 minimal + 4 custom)\n";
} catch (Exception $e) {
    echo '   :x: API Gateway validation failed: '.$e->getMessage()."\n";
}

echo "\n";

// 8. Performance and maintainability summary
echo "8. Benefits Summary:\n";
echo "   :sparkles: Type Safety: IDE autocompletion, static analysis\n";
echo "   :recycle: Reusability: Share rule objects across fields\n";
echo "   :test_tube: Testability: Unit test individual rules easily\n";
echo "   :book: Readability: Self-documenting, expressive code\n";
echo "   :speech_balloon: Custom Messages: Context-specific error messages\n";
echo "   :wrench: Extensibility: Add custom logic to rule classes\n";
echo "   :bug: Debugging: Easier to debug rule-specific issues\n";
echo "   :hammer_and_wrench: Maintenance: Centralized logic, easier refactoring\n";

echo "\n:tada: Rule Objects Demonstration Complete!\n";
echo "   :white_check_mark: Rule objects provide better maintainability and type safety\n";
echo "   :white_check_mark: All presets now use Rule objects by default\n";
echo "   :white_check_mark: Backward compatibility with string rules is maintained\n";
