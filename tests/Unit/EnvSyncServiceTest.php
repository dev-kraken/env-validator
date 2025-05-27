<?php

namespace Tests\Unit;

use EnvValidator\Services\EnvExampleSyncService;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/env-validator-tests-'.uniqid();
    mkdir($this->tempDir, 0755, true);

    $this->envPath = $this->tempDir.'/.env';
    $this->examplePath = $this->tempDir.'/.env.example';

    $this->syncService = new EnvExampleSyncService($this->envPath, $this->examplePath);
});

afterEach(function () {
    if (file_exists($this->envPath)) {
        unlink($this->envPath);
    }
    if (file_exists($this->examplePath)) {
        unlink($this->examplePath);
    }
    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

test('it detects when .env file does not exist', function () {
    $report = $this->syncService->getSyncReport();

    expect($report['status'])->toBe('error')
        ->and($report['message'])->toBe('.env file not found')
        ->and($report['suggestions'])->toContain('Create a .env file first');
});

test('it detects when .env.example file does not exist', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nAPP_ENV=testing");

    $report = $this->syncService->getSyncReport();

    expect($report['status'])->toBe('warning')
        ->and($report['message'])->toBe('.env.example file not found')
        ->and($report['can_auto_create'])->toBeTrue();
});

test('it detects files are synchronized', function () {
    $content = "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_DEBUG=false";
    file_put_contents($this->envPath, $content);
    file_put_contents($this->examplePath, $content);

    $report = $this->syncService->getSyncReport();

    expect($report['status'])->toBe('synced')
        ->and($report['message'])->toBe('Files are synchronized');
});

test('it detects missing keys in .env.example', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nAPP_ENV=testing\nNEW_KEY=value");
    file_put_contents($this->examplePath, "APP_NAME=TestApp\nAPP_ENV=testing");

    $report = $this->syncService->getSyncReport();

    expect($report['status'])->toBe('out_of_sync')
        ->and($report['message'])->toContain('1 key(s) missing in .env.example')
        ->and($report['missing_in_example'])->toHaveKey('other')
        ->and($report['missing_in_example']['other'])->toHaveKey('NEW_KEY');
});

test('it detects extra keys in .env.example', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nAPP_ENV=testing");
    file_put_contents($this->examplePath, "APP_NAME=TestApp\nAPP_ENV=testing\nEXTRA_KEY=value");

    $report = $this->syncService->getSyncReport();

    expect($report['status'])->toBe('out_of_sync')
        ->and($report['message'])->toContain('1 extra key(s) in .env.example')
        ->and($report['extra_in_example'])->toContain('EXTRA_KEY');
});

test('it categorizes missing keys correctly', function () {
    $envContent = <<<'ENV'
APP_NAME=TestApp
APP_DEBUG=true
DB_HOST=localhost
DB_PASSWORD=secret
MAIL_MAILER=smtp
REDIS_HOST=localhost
STRIPE_SECRET=sk_test_123
AWS_ACCESS_KEY_ID=AKIA123
CUSTOM_VAR=value
ENV;

    file_put_contents($this->envPath, $envContent);
    file_put_contents($this->examplePath, 'APP_NAME=TestApp');

    $report = $this->syncService->getSyncReport();

    expect($report['missing_in_example'])->toHaveKey('application')
        ->and($report['missing_in_example'])->toHaveKey('database')
        ->and($report['missing_in_example'])->toHaveKey('mail')
        ->and($report['missing_in_example'])->toHaveKey('cache')
        ->and($report['missing_in_example'])->toHaveKey('sensitive')
        ->and($report['missing_in_example'])->toHaveKey('third_party')
        ->and($report['missing_in_example'])->toHaveKey('other');
});

test('it generates appropriate example values', function () {
    $envContent = <<<'ENV'
APP_NAME=MyApp
APP_DEBUG=true
APP_URL=https://myapp.com
ADMIN_EMAIL=admin@myapp.com
DB_PORT=3306
SECRET_KEY=very_secret_value
FEATURE_FLAG=false
ENV;

    file_put_contents($this->envPath, $envContent);

    $result = $this->syncService->syncToExample(['generate_values' => true]);

    expect($result['success'])->toBeTrue();

    $exampleContent = file_get_contents($this->examplePath);

    // Check that appropriate values are generated
    expect($exampleContent)->toContain('APP_DEBUG=true') // Boolean preserved
        ->and($exampleContent)->toContain('APP_URL=https://example.com') // URL converted
        ->and($exampleContent)->toContain('ADMIN_EMAIL=user@example.com') // Email converted
        ->and($exampleContent)->toContain('DB_PORT=3306') // Numeric preserved
        ->and($exampleContent)->toContain('SECRET_KEY=') // Sensitive key emptied
        ->and($exampleContent)->toContain('FEATURE_FLAG=true'); // Boolean standardized
});

test('it handles sync with no value generation', function () {
    file_put_contents($this->envPath, 'NEW_KEY=some_value');

    $result = $this->syncService->syncToExample(['generate_values' => false]);

    expect($result['success'])->toBeTrue();

    $exampleContent = file_get_contents($this->examplePath);
    expect($exampleContent)->toContain('NEW_KEY=');
});

test('it adds missing keys to existing .env.example', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nNEW_KEY=value");
    file_put_contents($this->examplePath, 'APP_NAME=TestApp');

    $result = $this->syncService->syncToExample(['add_missing' => true]);

    expect($result['success'])->toBeTrue()
        ->and($result['changes'])->toHaveKey('added')
        ->and($result['changes']['added'])->toContain('NEW_KEY');

    $exampleContent = file_get_contents($this->examplePath);
    expect($exampleContent)->toContain('NEW_KEY=your_value_here');
});

test('it removes extra keys from .env.example', function () {
    file_put_contents($this->envPath, 'APP_NAME=TestApp');
    file_put_contents($this->examplePath, "APP_NAME=TestApp\nEXTRA_KEY=value");

    $result = $this->syncService->syncToExample(['remove_extra' => true]);

    expect($result['success'])->toBeTrue()
        ->and($result['changes'])->toHaveKey('removed')
        ->and($result['changes']['removed'])->toContain('EXTRA_KEY');

    $exampleContent = file_get_contents($this->examplePath);
    expect($exampleContent)->not()->toContain('EXTRA_KEY');
});

test('it identifies sensitive keys correctly', function () {
    $sensitiveKeys = [
        'APP_KEY' => 'base64:test',
        'DB_PASSWORD' => 'secret',
        'STRIPE_SECRET' => 'sk_test_123',
        'JWT_SECRET' => 'jwt_secret',
        'API_TOKEN' => 'token_123',
    ];

    $nonsensitiveKeys = [
        'APP_NAME' => 'TestApp',
        'APP_ENV' => 'testing',
        'DB_HOST' => 'localhost',
        'MAIL_MAILER' => 'smtp',
    ];

    $allKeys = array_merge($sensitiveKeys, $nonsensitiveKeys);

    $envContent = '';
    foreach ($allKeys as $key => $value) {
        $envContent .= "{$key}={$value}\n";
    }

    file_put_contents($this->envPath, $envContent);

    $result = $this->syncService->syncToExample(['generate_values' => true]);
    $exampleContent = file_get_contents($this->examplePath);

    // Sensitive keys should have empty values
    foreach ($sensitiveKeys as $key => $value) {
        expect($exampleContent)->toContain("{$key}=");
    }

    // Non-sensitive keys should have example values
    foreach ($nonsensitiveKeys as $key => $value) {
        expect($exampleContent)->toContain("{$key}=");
        // Some values like 'localhost' and 'smtp' are good examples and might be preserved
        if (! in_array($value, ['localhost', 'smtp'])) {
            expect($exampleContent)->not()->toContain("{$key}={$value}"); // Original value should be replaced
        }
    }
});

test('it suggests appropriate validation rules', function () {
    $keys = [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://example.com',
        'ADMIN_EMAIL' => 'admin@example.com',
        'DB_PORT' => '3306',
        'CUSTOM_VAR' => 'some_value',
    ];

    $rules = $this->syncService->suggestValidationRules($keys);

    expect($rules)->toHaveKey('APP_ENV')
        ->and($rules['APP_ENV'])->toContain('required')
        ->and($rules)->toHaveKey('APP_DEBUG')
        ->and($rules)->toHaveKey('APP_URL')
        ->and($rules)->toHaveKey('ADMIN_EMAIL')
        ->and($rules)->toHaveKey('DB_PORT')
        ->and($rules['DB_PORT'])->toContain('integer')
        ->and($rules['DB_PORT'])->toContain('min:1')
        ->and($rules['DB_PORT'])->toContain('max:65535')
        ->and($rules)->toHaveKey('CUSTOM_VAR')
        ->and($rules['CUSTOM_VAR'])->toContain('string');
});

test('it creates .env.example from scratch with header', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nAPP_ENV=testing");

    $result = $this->syncService->syncToExample(['generate_values' => true]);

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Created .env.example from .env template')
        ->and($result['changes'])->toHaveKey('created_file');

    $exampleContent = file_get_contents($this->examplePath);
    expect($exampleContent)->toContain('# Environment Configuration Example')
        ->and($exampleContent)->toContain('# Copy this file to .env and update with your actual values')
        ->and($exampleContent)->toContain('APP_NAME=your_value_here')
        ->and($exampleContent)->toContain('APP_ENV=production');
});

test('it preserves comments and formatting when syncing', function () {
    $envContent = "APP_NAME=TestApp\nNEW_KEY=value";
    $exampleContent = <<<'EXAMPLE'
# Application Configuration
APP_NAME=TestApp

# Database Configuration
DB_HOST=localhost
EXAMPLE;

    file_put_contents($this->envPath, $envContent);
    file_put_contents($this->examplePath, $exampleContent);

    $result = $this->syncService->syncToExample(['add_missing' => true]);

    expect($result['success'])->toBeTrue();

    $updatedContent = file_get_contents($this->examplePath);
    expect($updatedContent)->toContain('# Application Configuration')
        ->and($updatedContent)->toContain('# Database Configuration')
        ->and($updatedContent)->toContain('NEW_KEY=your_value_here');
});

test('it handles environment files with quotes and special characters', function () {
    $envContent = <<<'ENV'
APP_NAME="My App"
APP_URL='https://example.com'
SPECIAL_VAR="value with spaces"
QUOTED_EMPTY=""
SINGLE_QUOTED_EMPTY=''
ENV;

    file_put_contents($this->envPath, $envContent);

    $comparison = $this->syncService->compareFiles();

    expect($comparison['missing_in_example'])->toHaveKey('APP_NAME')
        ->and($comparison['missing_in_example']['APP_NAME'])->toBe('My App') // Quotes removed
        ->and($comparison['missing_in_example'])->toHaveKey('APP_URL')
        ->and($comparison['missing_in_example']['APP_URL'])->toBe('https://example.com')
        ->and($comparison['missing_in_example'])->toHaveKey('SPECIAL_VAR')
        ->and($comparison['missing_in_example']['SPECIAL_VAR'])->toBe('value with spaces');
});

test('it skips comments and empty lines when parsing', function () {
    $envContent = <<<'ENV'
# This is a comment
APP_NAME=TestApp

# Another comment
APP_ENV=testing
# More comments

# Final comment
ENV;

    file_put_contents($this->envPath, $envContent);

    $comparison = $this->syncService->compareFiles();

    expect($comparison['env_count'])->toBe(2); // Should only count actual variables
});

test('it handles file existence checks correctly', function () {
    expect($this->syncService->envFileExists())->toBeFalse()
        ->and($this->syncService->exampleFileExists())->toBeFalse();

    file_put_contents($this->envPath, 'APP_NAME=TestApp');
    expect($this->syncService->envFileExists())->toBeTrue()
        ->and($this->syncService->exampleFileExists())->toBeFalse();

    file_put_contents($this->examplePath, 'APP_NAME=TestApp');
    expect($this->syncService->envFileExists())->toBeTrue()
        ->and($this->syncService->exampleFileExists())->toBeTrue();
});

test('it handles sync when .env file is missing', function () {
    $result = $this->syncService->syncToExample();

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('.env file not found');
});
