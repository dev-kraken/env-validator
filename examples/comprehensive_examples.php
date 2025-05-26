<?php

require_once __DIR__.'/../vendor/autoload.php';

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\NumericRules\PortRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\EmailRule;
use EnvValidator\Collections\StringRules\InRule;
use EnvValidator\Collections\StringRules\JsonRule;
use EnvValidator\Core\AbstractRule;
use EnvValidator\Core\DefaultRulePresets;
use EnvValidator\EnvValidator;

echo ":rocket: EnvValidator Comprehensive Examples\n";
echo "=====================================\n\n";

// 1. Basic Usage with Rule Objects
echo "1. Basic Usage with Rule Objects:\n";
echo "   Modern, type-safe approach\n\n";

$validator = new EnvValidator;
$validator->setRules([
    'APP_NAME' => ['required', 'string'],
    'APP_ENV' => ['required', 'string', new InRule(['local', 'staging', 'production'])],
    'APP_DEBUG' => ['required', new BooleanRule],
    'APP_URL' => ['required', new UrlRule],
    'ADMIN_EMAIL' => ['required', new EmailRule],
    'DB_PORT' => ['required', new PortRule],
    'API_CONFIG' => ['nullable', new JsonRule],
]);

$sampleEnv = [
    'APP_NAME' => 'My Application',
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_URL' => 'https://myapp.com',
    'ADMIN_EMAIL' => 'admin@myapp.com',
    'DB_PORT' => '3306',
    'API_CONFIG' => '{"timeout": 30, "retries": 3}',
];

try {
    // Use standalone validation to avoid Laravel facade issues
    $result = EnvValidator::validateStandalone($sampleEnv, $validator->getRules());
    if ($result === true) {
        echo "   :white_check_mark: Validation passed!\n\n";
    } else {
        echo "   :x: Validation failed with errors\n\n";
    }
} catch (Exception $e) {
    echo "   :x: Validation failed: {$e->getMessage()}\n\n";
}

// 2. Preset System Examples
echo "2. Preset System Examples:\n";
echo "   Pre-configured rule sets for common scenarios\n\n";

// Laravel application
echo "   Laravel Application:\n";
$laravelValidator = (new EnvValidator)->usePreset('laravel');
echo '   - Rules count: '.count($laravelValidator->getRules())."\n";

// Microservice (using direct method call since preset isn't registered)
echo "   Microservice:\n";
$microserviceValidator = new EnvValidator;
$microserviceValidator->setRules(DefaultRulePresets::microservice());
echo '   - Rules count: '.count($microserviceValidator->getRules())."\n";

// Production deployment
echo "   Production Deployment:\n";
$productionValidator = (new EnvValidator)->useProductionRules();
echo '   - Rules count: '.count($productionValidator->getRules())."\n\n";

// 3. Advanced Rule Configurations
echo "3. Advanced Rule Configurations:\n";
echo "   Custom rules with specific requirements\n\n";

$advancedValidator = new EnvValidator;
$advancedValidator->setRules([
    // Environment with strict validation
    'APP_ENV' => [
        'required',
        'string',
        new InRule(
            ['staging', 'production'],
            'Production environments must be either staging or production.'
        ),
    ],

    // Port with custom range (non-privileged ports only)
    'API_PORT' => [
        'required',
        new PortRule(1024, 65535, 'API port must be a non-privileged port (1024-65535).'),
    ],

    // Email with custom message
    'NOTIFICATION_EMAIL' => [
        'required',
        new EmailRule('Please provide a valid email for notifications.'),
    ],

    // JSON configuration with validation
    'FEATURE_FLAGS' => [
        'nullable',
        new JsonRule('Feature flags must be valid JSON configuration.'),
    ],
]);

echo "   :white_check_mark: Advanced rules configured\n\n";

// 4. Real-World Application Examples
echo "4. Real-World Application Examples:\n\n";

// E-commerce application
echo "   E-commerce Application:\n";
$ecommerceRules = array_merge(
    DefaultRulePresets::laravel(),
    [
        'STRIPE_PUBLISHABLE_KEY' => ['required', 'string', 'min:32'],
        'STRIPE_SECRET_KEY' => ['required', 'string', 'min:32'],
        'PAYMENT_WEBHOOK_SECRET' => ['required', 'string'],
        'INVENTORY_API_URL' => ['required', new UrlRule],
        'INVENTORY_API_KEY' => ['required', 'string', 'min:20'],
        'NOTIFICATION_EMAIL' => ['required', new EmailRule],
        'REDIS_PORT' => ['required', new PortRule],
        'FEATURE_FLAGS' => ['nullable', new JsonRule],
    ]
);

echo '   - Total rules: '.count($ecommerceRules)."\n";

// API Gateway
echo "   API Gateway:\n";
$apiGatewayRules = array_merge(
    DefaultRulePresets::api(),
    [
        'RATE_LIMIT_PER_MINUTE' => ['required', 'integer', 'min:1', 'max:10000'],
        'JWT_SECRET' => ['required', 'string', 'min:32'],
        'CORS_ORIGINS' => ['nullable', new JsonRule],
        'HEALTH_CHECK_PORT' => ['required', new PortRule(1024, 65535)],
        'MONITORING_WEBHOOK' => ['nullable', new UrlRule],
    ]
);

echo '   - Total rules: '.count($apiGatewayRules)."\n";

// Microservice with database
echo "   Microservice with Database:\n";
$microserviceDbRules = array_merge(
    DefaultRulePresets::microservice(),
    DefaultRulePresets::database(),
    [
        'SERVICE_PORT' => ['required', new PortRule(3000, 9000)],
        'HEALTH_CHECK_ENDPOINT' => ['required', new UrlRule],
        'METRICS_PORT' => ['required', new PortRule(9090, 9999)],
    ]
);

echo '   - Total rules: '.count($microserviceDbRules)."\n\n";

// 5. Environment-Specific Validation
echo "5. Environment-Specific Validation:\n";
echo "   Different rules for different environments\n\n";

function getValidatorForEnvironment(string $environment): EnvValidator
{
    $validator = new EnvValidator;

    return match ($environment) {
        'local', 'development' => $validator->useMinimalRules(),
        'testing' => $validator->setRules(array_merge(
            DefaultRulePresets::minimal(),
            ['TEST_DATABASE_URL' => ['required', new UrlRule]]
        )),
        'staging' => $validator->setRules(array_merge(
            DefaultRulePresets::production(),
            ['STAGING_API_KEY' => ['required', 'string']]
        )),
        'production' => $validator->setRules(array_merge(
            DefaultRulePresets::production(),
            [
                'MONITORING_API_KEY' => ['required', 'string', 'min:32'],
                'BACKUP_WEBHOOK_URL' => ['required', new UrlRule],
            ]
        )),
        default => $validator->usePreset('laravel'),
    };
}

foreach (['local', 'testing', 'staging', 'production'] as $env) {
    $envValidator = getValidatorForEnvironment($env);
    echo "   {$env}: ".count($envValidator->getRules())." rules\n";
}

echo "\n";

// 6. Custom Rule Example
echo "6. Custom Rule Example:\n";
echo "   Creating application-specific validation rules\n\n";

class ApiKeyRule extends AbstractRule
{
    public function __construct(
        private readonly string $prefix = 'ak_',
        private readonly int $minLength = 32
    ) {}

    public function passes(string $attribute, mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return str_starts_with($value, $this->prefix)
            && strlen($value) >= $this->minLength;
    }

    public function message(): string
    {
        return "The :attribute must be a valid API key starting with '{$this->prefix}' and at least {$this->minLength} characters long.";
    }
}

$customValidator = new EnvValidator;
$customValidator->setRules([
    'EXTERNAL_API_KEY' => ['required', new ApiKeyRule('ak_', 40)],
    'INTERNAL_API_KEY' => ['required', new ApiKeyRule('ik_', 32)],
]);

echo "   :white_check_mark: Custom API key rules created\n\n";

// 7. Error Handling Examples
echo "7. Error Handling Examples:\n";
echo "   Comprehensive error handling and reporting\n\n";

// Use string rules for standalone compatibility
$errorValidator = new EnvValidator;
$errorValidator->setRules([
    'REQUIRED_FIELD' => ['required', 'string'],
    'EMAIL_FIELD' => ['required', 'email'],
    'PORT_FIELD' => ['required', 'integer', 'min:1', 'max:65535'],
]);

$invalidEnv = [
    'REQUIRED_FIELD' => '',
    'EMAIL_FIELD' => 'invalid-email',
    'PORT_FIELD' => '99999',
];

try {
    // Use standalone validation
    $result = EnvValidator::validateStandalone($invalidEnv, $errorValidator->getRules());
    if ($result !== true && is_array($result)) {
        echo "   Validation Errors:\n";
        foreach ($result as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                echo "     * {$field}: {$error}\n";
            }
        }
    } else {
        echo "   :white_check_mark: No validation errors found\n";
    }
} catch (Exception $e) {
    echo "   :x: Validation error: {$e->getMessage()}\n";
}

echo "\n";

// 8. Performance and Best Practices
echo "8. Performance and Best Practices:\n";
echo "   Optimized validation strategies\n\n";

// Validate only critical variables on boot
$criticalValidator = new EnvValidator;
$criticalValidator->setRules(DefaultRulePresets::minimal());

echo "   :white_check_mark: Critical validation (4 rules) - Fast boot time\n";

// Full validation for deployment checks
$deploymentValidator = new EnvValidator;
$deploymentValidator->setRules(array_merge(
    DefaultRulePresets::production(),
    DefaultRulePresets::database(),
    DefaultRulePresets::cache(),
    DefaultRulePresets::mail()
));

echo '   :white_check_mark: Deployment validation ('.count($deploymentValidator->getRules())." rules) - Comprehensive checks\n";

echo "\n:tada: Comprehensive Examples Complete!\n";
echo "   :white_check_mark: All validation patterns demonstrated\n";
echo "   :white_check_mark: Production-ready configurations shown\n";
echo "   :white_check_mark: Best practices highlighted\n";
