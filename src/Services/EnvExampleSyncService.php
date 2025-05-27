<?php

declare(strict_types=1);

namespace EnvValidator\Services;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\EmailRule;
use EnvValidator\Collections\StringRules\InRule;

class EnvExampleSyncService
{
    protected string $envPath;
    protected string $envExamplePath;
    /** @var array<string> */
    protected array $sensitiveKeys = [
        'APP_KEY',
        'DB_PASSWORD',
        'REDIS_PASSWORD',
        'MAIL_PASSWORD',
        'AWS_SECRET_ACCESS_KEY',
        'STRIPE_SECRET',
        'JWT_SECRET',
        'ENCRYPTION_KEY',
        'API_SECRET',
        'TOKEN',
        'SECRET',
        'PASSWORD',
    ];

    public function __construct(
        ?string $envPath = null,
        ?string $envExamplePath = null
    ) {
        $this->envPath = $envPath ?? $this->getDefaultEnvPath();
        $this->envExamplePath = $envExamplePath ?? $this->getDefaultExamplePath();
    }

    /**
     * Get default .env path (Laravel or standalone)
     */
    protected function getDefaultEnvPath(): string
    {
        // Check if we're in a proper Laravel environment
        if ($this->isLaravelEnvironment()) {
            return base_path('.env');
        }

        // Standalone PHP - use current working directory
        return getcwd().'/.env';
    }

    /**
     * Get default .env.example path (Laravel or standalone)
     */
    protected function getDefaultExamplePath(): string
    {
        // Check if we're in a proper Laravel environment
        if ($this->isLaravelEnvironment()) {
            return base_path('.env.example');
        }

        // Standalone PHP - use current working directory
        return getcwd().'/.env.example';
    }

    /**
     * Check if we're running in a proper Laravel environment
     */
    protected function isLaravelEnvironment(): bool
    {
        // Check if base_path function exists and can be called safely
        if (! function_exists('base_path')) {
            return false;
        }

        // Try to call base_path safely
        try {
            base_path();

            return true;
        } catch (\Throwable $e) {
            // base_path function exists but can't be called (no Laravel app instance)
            return false;
        }
    }

    /**
     * Compare .env and .env.example files and return differences
     *
     * @return array{missing_in_example: array<string, string>, extra_in_example: array<string, string>, common_keys: array<string, string>, env_count: int, example_count: int}
     */
    public function compareFiles(): array
    {
        $envVars = $this->parseEnvFile($this->envPath);
        $exampleVars = $this->parseEnvFile($this->envExamplePath);

        return [
            'missing_in_example' => array_diff_key($envVars, $exampleVars),
            'extra_in_example' => array_diff_key($exampleVars, $envVars),
            'common_keys' => array_intersect_key($envVars, $exampleVars),
            'env_count' => count($envVars),
            'example_count' => count($exampleVars),
        ];
    }

    /**
     * Get detailed sync report
     *
     * @return array<string, mixed>
     */
    public function getSyncReport(): array
    {
        if (! $this->envFileExists()) {
            return [
                'status' => 'error',
                'message' => '.env file not found',
                'suggestions' => ['Create a .env file first'],
            ];
        }

        if (! $this->exampleFileExists()) {
            return [
                'status' => 'warning',
                'message' => '.env.example file not found',
                'suggestions' => ['Create .env.example file from .env template'],
                'can_auto_create' => true,
            ];
        }

        $comparison = $this->compareFiles();
        $missingKeys = $comparison['missing_in_example'];
        $extraKeys = $comparison['extra_in_example'];

        $status = 'synced';
        $issues = [];
        $suggestions = [];

        if (! empty($missingKeys)) {
            $status = 'out_of_sync';
            $issues[] = count($missingKeys).' key(s) missing in .env.example';
            $suggestions[] = 'Add missing keys to .env.example';
        }

        if (! empty($extraKeys)) {
            $status = 'out_of_sync';
            $issues[] = count($extraKeys).' extra key(s) in .env.example';
            $suggestions[] = 'Remove unused keys from .env.example or add them to .env';
        }

        return [
            'status' => $status,
            'message' => $status === 'synced' ? 'Files are synchronized' : implode(', ', $issues),
            'missing_in_example' => $this->categorizeKeys($missingKeys),
            'extra_in_example' => array_keys($extraKeys),
            'suggestions' => $suggestions,
            'can_auto_sync' => ! empty($missingKeys),
            'total_env_keys' => $comparison['env_count'],
            'total_example_keys' => $comparison['example_count'],
        ];
    }

    /**
     * Automatically sync .env.example with .env
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function syncToExample(array $options = []): array
    {
        $addMissing = $options['add_missing'] ?? true;
        $removeExtra = $options['remove_extra'] ?? false;
        $preserveComments = $options['preserve_comments'] ?? true;
        $generateValues = (bool) ($options['generate_values'] ?? true);

        if (! $this->envFileExists()) {
            return [
                'success' => false,
                'message' => '.env file not found',
            ];
        }

        $comparison = $this->compareFiles();
        $changes = [];

        // Create .env.example if it doesn't exist
        if (! $this->exampleFileExists()) {
            $this->createExampleFromEnv($generateValues);

            return [
                'success' => true,
                'message' => 'Created .env.example from .env template',
                'changes' => ['created_file' => true],
            ];
        }

        // Read current .env.example content
        $exampleContent = file_get_contents($this->envExamplePath);
        if ($exampleContent === false) {
            return [
                'success' => false,
                'message' => 'Failed to read .env.example file',
            ];
        }
        $newContent = $exampleContent;

        // Add missing keys
        if ($addMissing && ! empty($comparison['missing_in_example'])) {
            $addedKeys = [];
            foreach ($comparison['missing_in_example'] as $key => $value) {
                $exampleValue = $generateValues ? $this->generateExampleValue($key, $value) : '';
                $newContent .= "\n{$key}={$exampleValue}";
                $addedKeys[] = $key;
            }
            $changes['added'] = $addedKeys;
        }

        // Remove extra keys
        if ($removeExtra && ! empty($comparison['extra_in_example'])) {
            $removedKeys = [];
            foreach ($comparison['extra_in_example'] as $key => $value) {
                $pattern = "/^{$key}=.*$/m";
                if (preg_match($pattern, $newContent)) {
                    $result = preg_replace($pattern, '', $newContent);
                    if ($result !== null) {
                        $newContent = $result;
                        $removedKeys[] = $key;
                    }
                }
            }
            $changes['removed'] = $removedKeys;
        }

        // Clean up extra newlines
        $result = preg_replace("/\n{3,}/", "\n\n", $newContent);
        if ($result !== null) {
            $newContent = $result;
        }
        $newContent = rtrim($newContent)."\n";

        // Write updated content
        file_put_contents($this->envExamplePath, $newContent);

        return [
            'success' => true,
            'message' => 'Successfully synchronized .env.example',
            'changes' => $changes,
        ];
    }

    /**
     * Generate appropriate example values for different key types
     */
    protected function generateExampleValue(string $key, string $value): string
    {
        // Sensitive keys get empty values
        if ($this->isSensitiveKey($key)) {
            return '';
        }

        // Boolean values
        if (in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'])) {
            return 'true';
        }

        // URLs
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($value);

            return ($parsed['scheme'] ?? 'https').'://example.com';
        }

        // Email addresses
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'user@example.com';
        }

        // Numeric values
        if (is_numeric($value)) {
            return $value;
        }

        // Environment-specific values
        if (in_array($key, ['APP_ENV', 'NODE_ENV'])) {
            return 'production';
        }

        if (str_contains($key, 'PORT')) {
            return '3000';
        }

        if (str_contains($key, 'HOST')) {
            return 'localhost';
        }

        if (str_contains($key, 'DATABASE') || str_contains($key, 'DB_')) {
            if (str_contains($key, 'NAME')) {
                return 'app_database';
            }
            if (str_contains($key, 'USER')) {
                return 'app_user';
            }

            // HOST and PORT for database are handled above, so we just return defaults here
            return 'your_database_value_here';
        }

        // Default: return a placeholder
        return 'your_value_here';
    }

    /**
     * Create .env.example from .env with safe example values
     */
    protected function createExampleFromEnv(bool $generateValues = true): void
    {
        $envVars = $this->parseEnvFile($this->envPath);
        $content = "# Environment Configuration Example\n";
        $content .= "# Copy this file to .env and update with your actual values\n\n";

        foreach ($envVars as $key => $value) {
            $exampleValue = $generateValues ? $this->generateExampleValue($key, $value) : '';
            $content .= "{$key}={$exampleValue}\n";
        }

        file_put_contents($this->envExamplePath, $content);
    }

    /**
     * Categorize missing keys by type for better reporting
     *
     * @param  array<string, string>  $keys
     * @return array<string, array<string, string>>
     */
    protected function categorizeKeys(array $keys): array
    {
        $categories = [
            'sensitive' => [],
            'database' => [],
            'mail' => [],
            'cache' => [],
            'application' => [],
            'third_party' => [],
            'other' => [],
        ];

        foreach ($keys as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $categories['sensitive'][$key] = $value;
            } elseif (str_starts_with($key, 'DB_') || str_contains($key, 'DATABASE')) {
                $categories['database'][$key] = $value;
            } elseif (str_starts_with($key, 'MAIL_') || str_contains($key, 'SMTP')) {
                $categories['mail'][$key] = $value;
            } elseif (str_contains($key, 'CACHE') || str_contains($key, 'REDIS') || str_contains($key, 'MEMCACHE')) {
                $categories['cache'][$key] = $value;
            } elseif (str_starts_with($key, 'APP_') || in_array($key, ['APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL'])) {
                $categories['application'][$key] = $value;
            } elseif (str_contains($key, 'AWS_') || str_contains($key, 'STRIPE_') || str_contains($key, 'PAYPAL_')) {
                $categories['third_party'][$key] = $value;
            } else {
                $categories['other'][$key] = $value;
            }
        }

        // Remove empty categories
        return array_filter($categories, fn ($category) => ! empty($category));
    }

    /**
     * Check if a key contains sensitive information
     */
    protected function isSensitiveKey(string $key): bool
    {
        $key = strtoupper($key);

        foreach ($this->sensitiveKeys as $sensitivePattern) {
            if (str_contains($key, $sensitivePattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse environment file into key-value pairs
     *
     * @return array<string, string>
     */
    protected function parseEnvFile(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }
        $lines = explode("\n", $content);
        $vars = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse key=value pairs
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\''); // Remove quotes

                if (! empty($key)) {
                    $vars[$key] = $value;
                }
            }
        }

        return $vars;
    }

    /**
     * Check if .env file exists
     */
    public function envFileExists(): bool
    {
        return file_exists($this->envPath);
    }

    /**
     * Check if .env.example file exists
     */
    public function exampleFileExists(): bool
    {
        return file_exists($this->envExamplePath);
    }

    /**
     * Get validation rules for environment keys based on patterns
     *
     * @param  array<string, string>  $keys
     * @return array<string, array<mixed>>
     */
    public function suggestValidationRules(array $keys): array
    {
        $rules = [];

        foreach ($keys as $key => $value) {
            $rule = ['required'];

            // Add type-specific rules based on key patterns and values
            if (str_contains($key, 'EMAIL') || filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $rule[] = new EmailRule;
            } elseif (str_contains($key, 'URL') || filter_var($value, FILTER_VALIDATE_URL)) {
                $rule[] = new UrlRule;
            } elseif (str_contains($key, 'DEBUG') || in_array(strtolower($value), ['true', 'false'])) {
                $rule[] = new BooleanRule;
            } elseif ($key === 'APP_ENV') {
                $rule[] = new InRule(['local', 'development', 'staging', 'production']);
            } elseif (str_contains($key, 'PORT') && is_numeric($value)) {
                $rule[] = 'integer';
                $rule[] = 'min:1';
                $rule[] = 'max:65535';
            } else {
                $rule[] = 'string';
            }

            $rules[$key] = $rule;
        }

        return $rules;
    }
}
