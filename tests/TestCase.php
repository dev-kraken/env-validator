<?php

namespace EnvValidator\Tests;

use EnvValidator\EnvValidatorServiceProvider;
use EnvValidator\Facades\EnvValidator;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            EnvValidatorServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  Application  $app
     * @return array<string, class-string<Facade>>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'EnvValidator' => EnvValidator::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Setup default environment variables
        $app['config']->set('env-validator.auto_validate', false);

        // Default test environment variables
        $app['env'] = 'testing';
    }

    /**
     * Create a temporary .env file with given key-value pairs.
     * Automatically quotes values containing spaces.
     *
     * @return string Full path to the created .env file.
     */
    protected function createTestEnv(array $data): string
    {
        // Determine file path in the base directory
        $path = $this->app->basePath('.env.testing');

        // Build lines without leading whitespace
        $lines = [];
        foreach ($data as $key => $value) {
            // Quote values with spaces
            if (preg_match('/\s/', $value)) {
                // Escape existing quotes
                $escaped = str_replace('"', '\\"', $value);
                $value = "\"{$escaped}\"";
            }
            $lines[] = "{$key}={$value}";
        }

        // Write to file (overwrites if exists)
        file_put_contents($path, implode("\n", $lines));

        // Also set these variables in the current environment
        foreach ($data as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }

        return $path;
    }

    /**
     * Clean up test .env file.
     */
    protected function cleanTestEnv(): void
    {
        $envPath = $this->app->basePath('.env.testing');
        if (file_exists($envPath)) {
            unlink($envPath);
        }
    }

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanTestEnv();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        $this->cleanTestEnv();
        parent::tearDown();
    }
}
