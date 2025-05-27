<?php

declare(strict_types=1);

namespace EnvValidator;

use EnvValidator\Console\EnvSyncCommand;
use EnvValidator\Console\ValidateEnvCommand;
use EnvValidator\Core\RuleRegistry;
use EnvValidator\Exceptions\InvalidEnvironmentException;
use EnvValidator\Services\EnvExampleSyncService;
use EnvValidator\Support\RuleFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class EnvValidatorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/env-validator.php',
            'env-validator'
        );

        // Register the rule registry as a singleton
        $this->app->singleton(RuleRegistry::class, function () {
            return new RuleRegistry;
        });

        // Register the rule factory as a singleton
        $this->app->singleton(RuleFactory::class, function ($app) {
            /** @var Application $app */
            /** @var RuleRegistry $registry */
            $registry = $app->make(RuleRegistry::class);

            return new RuleFactory($registry);
        });

        // Register the main validator
        $this->app->singleton('env-validator', function ($app) {
            return new EnvValidator;
        });

        // Register the sync service
        $this->app->singleton(EnvExampleSyncService::class, function ($app) {
            return new EnvExampleSyncService;
        });
    }

    /**
     * Bootstrap services.
     *
     * @throws InvalidEnvironmentException
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/env-validator.php' => config_path('env-validator.php'),
            ], 'config');

            $this->commands([
                ValidateEnvCommand::class,
                EnvSyncCommand::class,
            ]);
        }

        // Optionally validate environment on boot if configured
        if (config('env-validator.auto_validate', false)) {
            $this->validateEnvironment();
        }
    }

    /**
     * Validate environment variables on boot if enabled.
     *
     * @throws InvalidEnvironmentException
     */
    protected function validateEnvironment(): void
    {
        try {
            /** @var EnvValidator $validator */
            $validator = $this->app->make('env-validator');
            $keysToValidate = config('env-validator.validate_on_boot', []);

            // Check if we're running the env:validate command
            // If so, we'll skip validation here since the command will handle it
            $runningCommand = '';
            if ($this->app->runningInConsole()) {
                // Grab raw value (could be mixed)
                $rawArgv = $_SERVER['argv'] ?? null;

                // 1) Narrow to array
                if (is_array($rawArgv)
                    // 2) Check offset exists
                    && isset($rawArgv[1])
                    // 3) Check it’s a string
                    && is_string($rawArgv[1])
                ) {
                    $runningCommand = $rawArgv[1];
                }
            }

            if ($runningCommand === 'env:validate') {
                return;
            }

            if (! empty($keysToValidate)) {
                /** @var array<int, string> $keysToValidate */
                $validator->validateOnly($keysToValidate);
            } else {
                $validator->validate();
            }
        } catch (InvalidEnvironmentException $e) {
            // When running in console, show a cleaner message
            if ($this->app->runningInConsole()) {
                $this->app->terminating(function () use ($e) {
                    // This callback ensures the message is displayed after command execution
                    if (PHP_SAPI === 'cli') {
                        echo "\n\033[31mError: {$e->getMessage()}\033[0m\n\n";
                        exit(1);
                    }
                });
            } else {
                // Re-throw for web context
                throw $e;
            }
        } catch (BindingResolutionException $e) {
            // When running in console, show a cleaner message
            if ($this->app->runningInConsole()) {
                $this->app->terminating(function () use ($e) {
                    // This callback ensures the message is displayed after command execution
                    if (PHP_SAPI === 'cli') {
                        echo "\n\033[31mError: {$e->getMessage()}\033[0m\n\n";
                        exit(1);
                    }
                });
            }
        }
    }
}
