<?php

declare(strict_types=1);

namespace EnvValidator\Console;

use EnvValidator\EnvValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ValidateEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:validate {--keys=* : Specific environment keys to validate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate environment variables against defined rules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $keys = $this->option('keys');

        // Ensure $keys is always an array of strings
        $keysArray = is_array($keys) ? $keys : [];

        // Show debug output in verbose mode
        if ($this->getOutput()->isVerbose()) {
            // Get environment variables for debug display only
            $debugEnv = array_merge(getenv() ?: [], $_ENV, $_SERVER);

            $this->comment('Current environment variables:');
            $this->table(
                ['Variable', 'Value'],
                collect($debugEnv)
                    ->filter(function ($value, $key) {
                        return ! is_array($value) && ! str_starts_with($key, 'SYMFONY_') && ! str_starts_with($key, 'APP_');
                    })
                    ->map(function ($value, $key) {
                        return [$key, is_string($value) ? $value : var_export($value, true)];
                    })
                    ->toArray()
            );
        }

        /** @var EnvValidator $validator */
        $validator = app(EnvValidator::class);

        try {
            if (! empty($keysArray)) {
                $this->info('Validating specific environment variables: '.implode(', ', $keysArray));
                /** @var array<int, string> $keysArray */
                $validator->validateOnly($keysArray); // Let EnvValidator handle environment merging
                $this->info('Specified environment variables are valid.');
            } else {
                $this->info('Validating all environment variables...');
                $validator->validate(); // Let EnvValidator handle environment merging
                $this->info('All environment variables are valid.');
            }

            return CommandAlias::SUCCESS;
        } catch (InvalidEnvironmentException $e) {
            $this->error('Environment validation failed!');
            $this->error($e->getMessage());

            return CommandAlias::FAILURE;
        }
    }
}
