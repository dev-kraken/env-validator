<?php

require_once __DIR__.'/../vendor/autoload.php';

use EnvValidator\Services\EnvExampleSyncService;

echo "[*] Standalone PHP Environment File Synchronization\n";
echo "=================================================\n\n";

// For standalone PHP, we need to specify absolute paths since base_path() isn't available
$projectDir = __DIR__; // or getcwd() or any directory you prefer
$envPath = $projectDir.'/.env';
$examplePath = $projectDir.'/.env.example';

echo "[*] Note: In standalone PHP, you must provide absolute paths\n";
echo "[*] Working directory: {$projectDir}\n";
echo "[*] .env path: {$envPath}\n";
echo "[*] .env.example path: {$examplePath}\n\n";

// Scenario 1: Create sample files for demonstration
echo "[*] Scenario 1: Setting up demo environment files\n";
echo "===============================================\n\n";

// Create a sample .env file
$envContent = <<<'ENV'
# Standalone PHP Application Configuration
APP_NAME=StandaloneApp
APP_ENV=production
APP_DEBUG=false
APP_URL=https://standalone-app.com

# Database Configuration  
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=standalone_db
DB_USERNAME=app_user
DB_PASSWORD=secure_password_123

# New variables (missing from .env.example)
API_KEY=api_key_12345
REDIS_HOST=localhost
REDIS_PORT=6379
MAIL_FROM=noreply@standalone-app.com
LOG_LEVEL=info
FEATURE_ENABLED=true
ENV;

// Create existing .env.example (missing some keys)
$exampleContent = <<<'EXAMPLE'
# Standalone PHP Application Configuration
APP_NAME=your_app_name
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=

# This key is extra (not in .env)
OLD_CONFIG=legacy_value
EXAMPLE;

file_put_contents($envPath, $envContent);
file_put_contents($examplePath, $exampleContent);

echo "[✓] Created demo .env and .env.example files\n\n";

// Scenario 2: Initialize the sync service for standalone PHP
echo "[*] Scenario 2: Using EnvExampleSyncService in standalone PHP\n";
echo "==========================================================\n\n";

// IMPORTANT: For standalone PHP, always provide explicit paths
$syncService = new EnvExampleSyncService($envPath, $examplePath);

echo "[*] Checking synchronization status...\n";
$report = $syncService->getSyncReport();

echo 'Status: '.strtoupper($report['status'])."\n";
echo "Message: {$report['message']}\n\n";

if (isset($report['total_env_keys'])) {
    echo "[*] Statistics:\n";
    echo "   • .env keys: {$report['total_env_keys']}\n";
    echo "   • .env.example keys: {$report['total_example_keys']}\n\n";
}

if (! empty($report['missing_in_example'])) {
    echo "[*] Missing keys in .env.example (categorized):\n";
    foreach ($report['missing_in_example'] as $category => $keys) {
        $categoryName = ucfirst(str_replace('_', ' ', $category));
        echo "  [*] {$categoryName}:\n";
        foreach ($keys as $key => $value) {
            // Mask sensitive values for display
            $displayValue = str_contains(strtoupper($key), 'PASSWORD') || str_contains(strtoupper($key), 'SECRET')
                ? str_repeat('*', min(strlen($value), 8))
                : (strlen($value) > 30 ? substr($value, 0, 27).'...' : $value);
            echo "    • {$key} = {$displayValue}\n";
        }
    }
    echo "\n";
}

if (! empty($report['extra_in_example'])) {
    echo "[*]  Extra keys in .env.example:\n";
    foreach ($report['extra_in_example'] as $key) {
        echo "  • {$key}\n";
    }
    echo "\n";
}

// Scenario 3: Synchronize files in standalone PHP
echo "[*] Scenario 3: Synchronizing files in standalone mode\n";
echo "===================================================\n\n";

echo "[*] Performing synchronization with the following options:\n";
echo "  [✓] Add missing keys to .env.example\n";
echo "  [*]  Remove extra keys from .env.example\n";
echo "  [*] Generate appropriate example values\n";
echo "  [*] Mask sensitive values\n\n";

$syncOptions = [
    'add_missing' => true,
    'remove_extra' => true,
    'generate_values' => true,
];

$result = $syncService->syncToExample($syncOptions);

if ($result['success']) {
    echo "[✓] {$result['message']}\n\n";

    if (! empty($result['changes'])) {
        if (! empty($result['changes']['added'])) {
            echo "[*] Added keys:\n";
            foreach ($result['changes']['added'] as $key) {
                echo "  • {$key}\n";
            }
            echo "\n";
        }

        if (! empty($result['changes']['removed'])) {
            echo "[*] Removed keys:\n";
            foreach ($result['changes']['removed'] as $key) {
                echo "  • {$key}\n";
            }
            echo "\n";
        }
    }
} else {
    echo "[✗] Sync failed: {$result['message']}\n\n";
}

// Scenario 4: Show the updated .env.example
echo "[*] Updated .env.example content:\n";
echo "==============================\n";
echo file_get_contents($examplePath);
echo "\n";

// Scenario 5: Working with different project structures
echo "[*]  Scenario 5: Different project structures\n";
echo "============================================\n\n";

// Example for different directory structures
$examples = [
    'Current directory' => [
        'env' => getcwd().'/.env',
        'example' => getcwd().'/.env.example',
    ],
    'Config directory' => [
        'env' => getcwd().'/config/.env',
        'example' => getcwd().'/config/.env.example',
    ],
    'Absolute paths' => [
        'env' => '/var/www/app/.env',
        'example' => '/var/www/app/.env.example',
    ],
    'Custom names' => [
        'env' => getcwd().'/environment.conf',
        'example' => getcwd().'/environment.example.conf',
    ],
];

echo "[*] Example path configurations for different project structures:\n\n";

foreach ($examples as $description => $paths) {
    echo "[*] {$description}:\n";
    echo "   \$syncService = new EnvExampleSyncService(\n";
    echo "       '{$paths['env']}',\n";
    echo "       '{$paths['example']}'\n";
    echo "   );\n\n";
}

// Scenario 6: Integration with validation
echo "[*] Scenario 6: Integration with environment validation\n";
echo "====================================================\n\n";

echo "[*] Complete standalone workflow:\n";
echo "```php\n";
echo "use EnvValidator\\EnvValidator;\n";
echo "use EnvValidator\\Services\\EnvExampleSyncService;\n\n";
echo "// 1. Check if environment files are synchronized\n";
echo "\$syncService = new EnvExampleSyncService(\$envPath, \$examplePath);\n";
echo "\$report = \$syncService->getSyncReport();\n\n";
echo "if (\$report['status'] !== 'synced') {\n";
echo "    echo 'Warning: Environment files are out of sync!';\n";
echo "    // Optionally auto-sync\n";
echo "    \$syncService->syncToExample(['generate_values' => true]);\n";
echo "}\n\n";
echo "// 2. Validate environment variables\n";
echo "\$rules = [\n";
echo "    'APP_ENV' => 'required|string',\n";
echo "    'DB_HOST' => 'required|string',\n";
echo "    'API_KEY' => 'required|string|min:10',\n";
echo "];\n\n";
echo "\$result = EnvValidator::validateStandalone(\$_ENV, \$rules);\n";
echo "if (\$result !== true) {\n";
echo "    // Handle validation errors\n";
echo "    foreach (\$result as \$field => \$errors) {\n";
echo "        echo \"Error in \$field: \" . implode(', ', \$errors) . \"\\n\";\n";
echo "    }\n";
echo "}\n";
echo "```\n\n";

// Scenario 7: Suggest validation rules for new keys
echo "[*] Scenario 7: Getting validation rule suggestions\n";
echo "=================================================\n\n";

$comparison = $syncService->compareFiles();
if (! empty($comparison['missing_in_example'])) {
    echo "[*] Suggested validation rules for new environment variables:\n\n";

    $suggestedRules = $syncService->suggestValidationRules($comparison['missing_in_example']);

    echo "```php\n";
    echo "// Add these rules to your validation configuration\n";
    echo "\$rules = [\n";
    foreach ($suggestedRules as $key => $rules) {
        echo "    '{$key}' => [";
        $ruleStrings = [];
        foreach ($rules as $rule) {
            if (is_object($rule)) {
                $ruleStrings[] = 'new '.get_class($rule).'()';
            } else {
                $ruleStrings[] = "'{$rule}'";
            }
        }
        echo implode(', ', $ruleStrings);
        echo "],\n";
    }
    echo "];\n";
    echo "```\n\n";
}

// Scenario 8: Error handling in standalone mode
echo "[!]  Scenario 8: Error handling and edge cases\n";
echo "===========================================\n\n";

// Test with non-existent files
$testEnvPath = $projectDir.'/nonexistent.env';
$testExamplePath = $projectDir.'/nonexistent.env.example';

$testSyncService = new EnvExampleSyncService($testEnvPath, $testExamplePath);
$testReport = $testSyncService->getSyncReport();

echo "[*] Testing with non-existent files:\n";
echo 'Status: '.strtoupper($testReport['status'])."\n";
echo "Message: {$testReport['message']}\n";
echo "File exists checks:\n";
echo '  • .env exists: '.($testSyncService->envFileExists() ? 'Yes' : 'No')."\n";
echo '  • .env.example exists: '.($testSyncService->exampleFileExists() ? 'Yes' : 'No')."\n\n";

// Best practices for standalone PHP
echo "[*] Best Practices for Standalone PHP\n";
echo "==================================\n\n";

echo "1. [*] **Always specify absolute paths**:\n";
echo "   \$syncService = new EnvExampleSyncService(\n";
echo "       __DIR__ . '/.env',\n";
echo "       __DIR__ . '/.env.example'\n";
echo "   );\n\n";

echo "2. [*] **Check file existence before operations**:\n";
echo "   if (!\$syncService->envFileExists()) {\n";
echo "       die('Error: .env file not found!');\n";
echo "   }\n\n";

echo "3. [!]  **Handle errors gracefully**:\n";
echo "   \$result = \$syncService->syncToExample();\n";
echo "   if (!\$result['success']) {\n";
echo "       echo 'Sync failed: ' . \$result['message'];\n";
echo "       exit(1);\n";
echo "   }\n\n";

echo "4. [*] **Use --no-values for sensitive projects**:\n";
echo "   \$syncService->syncToExample(['generate_values' => false]);\n\n";

echo "5. [*] **Integrate with your deployment script**:\n";
echo "   // deployment.php\n";
echo "   \$report = \$syncService->getSyncReport();\n";
echo "   if (\$report['status'] !== 'synced') {\n";
echo "       echo 'ERROR: Environment files out of sync!';\n";
echo "       exit(1);\n";
echo "   }\n\n";

// Cleanup
echo "[*] Cleaning up demo files...\n";
unlink($envPath);
unlink($examplePath);

echo "[✓] Standalone PHP environment synchronization example completed!\n\n";

echo ">>  Ready to use in your projects:\n";
echo "  • Copy the patterns shown above\n";
echo "  • Adapt paths to your project structure\n";
echo "  • Integrate with your deployment workflow\n";
echo "  • Combine with environment validation for complete solution\n";
