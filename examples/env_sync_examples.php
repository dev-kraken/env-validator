<?php

require_once __DIR__.'/../vendor/autoload.php';

use EnvValidator\Services\EnvExampleSyncService;

echo "[*] Environment File Synchronization Examples\n";
echo "============================================\n\n";

// Create temporary test files for demonstration
$tempDir = sys_get_temp_dir().'/env-validator-demo';
if (! is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

$envPath = $tempDir.'/.env';
$examplePath = $tempDir.'/.env.example';

// Scenario 1: Developer adds new keys to .env but forgets .env.example
echo "[*] Scenario 1: Developer adds new environment variables\n";
echo "======================================================\n\n";

// Create a sample .env file with some new keys
$envContent = <<<'ENV'
# Application Configuration
APP_NAME=MyApp
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:abcdefghijklmnopqrstuvwxyz123456
APP_URL=https://myapp.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=myapp_db
DB_USERNAME=myapp_user
DB_PASSWORD=super_secret_password

# New keys added by developer (missing from .env.example)
STRIPE_PUBLIC_KEY=pk_test_123456789
STRIPE_SECRET_KEY=sk_test_987654321
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
ADMIN_EMAIL=admin@myapp.com
API_RATE_LIMIT=60
FEATURE_FLAG_NEW_DASHBOARD=true
ENV;

// Create existing .env.example (missing the new keys)
$exampleContent = <<<'EXAMPLE'
# Environment Configuration Example
# Copy this file to .env and update with your actual values

APP_NAME=your_app_name
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=

# This key exists in example but not in .env
LEGACY_API_KEY=your_legacy_key
EXAMPLE;

file_put_contents($envPath, $envContent);
file_put_contents($examplePath, $exampleContent);

$syncService = new EnvExampleSyncService($envPath, $examplePath);

echo "[*] Checking synchronization status...\n";
$report = $syncService->getSyncReport();

echo 'Status: '.strtoupper($report['status'])."\n";
echo "Message: {$report['message']}\n\n";

if (! empty($report['missing_in_example'])) {
    echo "[*] Missing keys in .env.example (categorized):\n";
    foreach ($report['missing_in_example'] as $category => $keys) {
        $categoryName = ucfirst(str_replace('_', ' ', $category));
        echo "  [*] {$categoryName}:\n";
        foreach ($keys as $key => $value) {
            // Mask sensitive values for display
            $displayValue = str_contains(strtoupper($key), 'SECRET') || str_contains(strtoupper($key), 'PASSWORD')
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

echo "[*] Suggestions:\n";
foreach ($report['suggestions'] as $suggestion) {
    echo "  • {$suggestion}\n";
}
echo "\n";

// Scenario 2: Auto-sync the files
echo "[*] Scenario 2: Automatically synchronizing files\n";
echo "===============================================\n\n";

echo "[*] Preview of changes that will be made:\n";
echo "  [✓] Add missing keys to .env.example with safe example values\n";
echo "  [*]  Remove extra keys from .env.example\n";
echo "  [*] Sensitive keys will get empty values\n";
echo "  [*] Other keys will get appropriate example values\n\n";

// Perform the sync
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

// Show the updated .env.example content
echo "[*] Updated .env.example content:\n";
echo "==============================\n";
echo file_get_contents($examplePath);
echo "\n";

// Scenario 3: Suggest validation rules for new keys
echo "[*] Scenario 3: Suggesting validation rules for new keys\n";
echo "======================================================\n\n";

$comparison = $syncService->compareFiles();
if (! empty($comparison['missing_in_example'])) {
    echo "[*] Suggested validation rules for new environment variables:\n\n";

    $suggestedRules = $syncService->suggestValidationRules($comparison['missing_in_example']);

    echo "```php\n";
    echo "// Add these rules to your EnvValidator configuration\n";
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

// Scenario 4: Create .env.example from scratch
echo "🆕 Scenario 4: Creating .env.example from scratch\n";
echo "=================================================\n\n";

// Remove the example file to demonstrate creation from scratch
unlink($examplePath);

echo "[*]  Removed existing .env.example\n";
echo "[*] Checking status without .env.example...\n";

$report = $syncService->getSyncReport();
echo 'Status: '.strtoupper($report['status'])."\n";
echo "Message: {$report['message']}\n\n";

if ($report['can_auto_create'] ?? false) {
    echo "🆕 Creating new .env.example from .env template...\n";

    $result = $syncService->syncToExample(['generate_values' => true]);

    if ($result['success']) {
        echo "[✓] {$result['message']}\n\n";

        echo "[*] Generated .env.example:\n";
        echo "=========================\n";
        echo file_get_contents($examplePath);
        echo "\n";
    }
}

// Scenario 5: Best practices and security
echo "🛡️  Scenario 5: Security and best practices\n";
echo "==========================================\n\n";

echo "[*] Security features demonstrated:\n";
echo "  • Sensitive keys (PASSWORD, SECRET, KEY, TOKEN) get empty values\n";
echo "  • URLs are replaced with 'https://example.com'\n";
echo "  • Email addresses become 'user@example.com'\n";
echo "  • Numeric values are preserved (ports, limits, etc.)\n";
echo "  • Boolean values are standardized to 'true'\n";
echo "  • Environment-specific values get appropriate defaults\n\n";

echo "[*] Best practices:\n";
echo "  1. Run 'php artisan env:sync --check' before deployments\n";
echo "  2. Add env:sync to your CI/CD pipeline\n";
echo "  3. Review changes before auto-syncing\n";
echo "  4. Use --no-values flag for security-sensitive projects\n";
echo "  5. Regularly audit both .env and .env.example files\n\n";

// Integration with existing validation
echo "[*] Integration with Environment Validation\n";
echo "=========================================\n\n";

echo "[*] Complete workflow:\n";
echo "  1. Developer adds new variables to .env\n";
echo "  2. Run: php artisan env:sync --check (detect missing keys)\n";
echo "  3. Run: php artisan env:sync (synchronize files)\n";
echo "  4. Add validation rules for new keys\n";
echo "  5. Run: php artisan env:validate (validate all variables)\n";
echo "  6. Commit both .env.example and updated validation rules\n\n";

echo "[*] Example validation integration:\n";
echo "```php\n";
echo "use EnvValidator\\EnvValidator;\n";
echo "use EnvValidator\\Services\\EnvExampleSyncService;\n\n";
echo "// Check sync status\n";
echo "\$syncService = new EnvExampleSyncService();\n";
echo "\$report = \$syncService->getSyncReport();\n\n";
echo "if (\$report['status'] !== 'synced') {\n";
echo "    echo 'Warning: .env and .env.example are out of sync!';\n";
echo "    // Optionally auto-sync or prompt user\n";
echo "}\n\n";
echo "// Validate environment\n";
echo "\$validator = new EnvValidator();\n";
echo "\$result = \$validator->validate();\n";
echo "```\n\n";

// Cleanup
echo "[*] Cleaning up temporary files...\n";
unlink($envPath);
if (file_exists($examplePath)) {
    unlink($examplePath);
}
rmdir($tempDir);

echo "[✓] Example completed successfully!\n\n";

echo ">>  Next steps:\n";
echo "  • Try the commands: php artisan env:sync --check\n";
echo "  • Explore options: php artisan env:sync --help\n";
echo "  • Integrate into your workflow and CI/CD pipeline\n";
