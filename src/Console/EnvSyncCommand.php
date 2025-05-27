<?php

declare(strict_types=1);

namespace EnvValidator\Console;

use EnvValidator\Services\EnvExampleSyncService;
use Illuminate\Console\Command;

class EnvSyncCommand extends Command
{
    protected $signature = 'env:sync
                            {--check : Only check for differences without making changes}
                            {--force : Automatically sync without confirmation}
                            {--no-values : Do not generate example values}
                            {--remove-extra : Remove keys that exist in .env.example but not in .env}
                            {--env-path= : Custom path to .env file}
                            {--example-path= : Custom path to .env.example file}';

    protected $description = 'Synchronize .env.example with .env file';

    protected EnvExampleSyncService $syncService;

    public function handle(): int
    {
        $envPath = $this->option('env-path');
        $examplePath = $this->option('example-path');

        $this->syncService = new EnvExampleSyncService(
            is_string($envPath) ? $envPath : null,
            is_string($examplePath) ? $examplePath : null
        );

        if ($this->option('check')) {
            return $this->checkSync();
        }

        return $this->performSync();
    }

    protected function checkSync(): int
    {
        $this->info('🔍 Checking environment file synchronization...');
        $this->newLine();

        $report = $this->syncService->getSyncReport();

        // Display status
        $status = $report['status'] ?? 'unknown';
        $message = is_string($report['message'] ?? null) ? $report['message'] : 'Unknown error';

        match ($status) {
            'synced' => $this->components->info('✅ Files are synchronized'),
            'warning' => $this->components->warn("⚠️  {$message}"),
            'error' => $this->components->error("❌ {$message}"),
            'out_of_sync' => $this->components->warn("⚠️  {$message}"),
            default => $this->components->error("❌ {$message}"),
        };

        if ($status === 'error') {
            return Command::FAILURE;
        }

        // Show detailed information
        $totalEnvKeys = is_numeric($report['total_env_keys'] ?? null) ? (int) $report['total_env_keys'] : 0;
        $totalExampleKeys = is_numeric($report['total_example_keys'] ?? null) ? (int) $report['total_example_keys'] : 0;
        if ($totalEnvKeys > 0 || $totalExampleKeys > 0) {
            $this->newLine();
            $this->line('📊 <info>Statistics:</info>');
            $this->line("   • .env keys: {$totalEnvKeys}");
            $this->line("   • .env.example keys: {$totalExampleKeys}");
        }

        // Show missing keys by category
        $missingInExample = $report['missing_in_example'] ?? [];
        if (! empty($missingInExample) && is_array($missingInExample)) {
            $this->newLine();
            $this->line('🔍 <comment>Missing in .env.example:</comment>');

            foreach ($missingInExample as $category => $keys) {
                if (is_array($keys)) {
                    $categoryName = ucfirst(str_replace('_', ' ', is_string($category) ? $category : 'unknown'));
                    $this->line("   <fg=yellow>📂 {$categoryName}:</fg=yellow>");

                    foreach ($keys as $key => $value) {
                        $keyStr = is_string($key) ? $key : 'unknown_key';
                        $valueStr = is_string($value) ? $value : '';
                        $displayValue = $this->maskSensitiveValue($keyStr, $valueStr);
                        $this->line("      • <fg=cyan>{$keyStr}</fg=cyan> = <fg=gray>{$displayValue}</fg=gray>");
                    }
                }
            }
        }

        // Show extra keys
        $extraInExample = $report['extra_in_example'] ?? [];
        if (! empty($extraInExample) && is_array($extraInExample)) {
            $this->newLine();
            $this->line('🗑️  <comment>Extra in .env.example:</comment>');
            foreach ($extraInExample as $key) {
                $keyStr = is_string($key) ? $key : 'unknown_key';
                $this->line("   • <fg=cyan>{$keyStr}</fg=cyan>");
            }
        }

        // Show suggestions
        $suggestions = $report['suggestions'] ?? [];
        if (! empty($suggestions) && is_array($suggestions)) {
            $this->newLine();
            $this->line('💡 <comment>Suggestions:</comment>');
            foreach ($suggestions as $suggestion) {
                $suggestionStr = is_string($suggestion) ? $suggestion : 'Unknown suggestion';
                $this->line('   • '.$suggestionStr);
            }
        }

        // Show sync command if needed
        if ($report['status'] === 'out_of_sync') {
            $this->newLine();
            $this->components->info('Run `php artisan env:sync` to automatically synchronize the files.');
        }

        return $report['status'] === 'synced' ? Command::SUCCESS : ($report['status'] === 'error' ? Command::FAILURE : 1);
    }

    protected function performSync(): int
    {
        $this->info('🔄 Synchronizing environment files...');

        // First, check current status
        $report = $this->syncService->getSyncReport();

        if ($report['status'] === 'error') {
            $errorMessage = is_string($report['message'] ?? null) ? $report['message'] : 'Unknown error';
            $this->components->error($errorMessage);

            return Command::FAILURE;
        }

        if ($report['status'] === 'synced') {
            $this->info('✅ Files are already synchronized!');

            return Command::SUCCESS;
        }

        // Show what will be changed
        if (! $this->option('force')) {
            $this->showPreview($report);

            if (! $this->confirm('Do you want to proceed with the synchronization?')) {
                $this->components->warn('Synchronization cancelled.');

                return Command::FAILURE;
            }
        }

        // Perform synchronization
        $options = [
            'add_missing' => true,
            'remove_extra' => $this->option('remove-extra'),
            'generate_values' => ! $this->option('no-values'),
        ];

        $result = $this->syncService->syncToExample($options);

        if (! $result['success']) {
            $errorMessage = is_string($result['message'] ?? null) ? $result['message'] : 'Unknown error';
            $this->components->error($errorMessage);

            return Command::FAILURE;
        }

        // Show results
        $successMessage = is_string($result['message'] ?? null) ? $result['message'] : 'Synchronization completed';
        $this->info($successMessage);

        $changes = $result['changes'] ?? [];
        if (! empty($changes) && is_array($changes)) {
            $this->newLine();
            /** @var array<string, mixed> $changes */
            $this->showChanges($changes);
        }

        $this->newLine();
        $this->info('🎉 Synchronization completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function showPreview(array $report): void
    {
        $this->newLine();
        $this->line('📋 <comment>Preview of changes:</comment>');

        $missingInExample = $report['missing_in_example'] ?? [];
        if (! empty($missingInExample) && is_array($missingInExample)) {
            $totalMissing = 0;
            foreach ($missingInExample as $keys) {
                if (is_array($keys)) {
                    $totalMissing += count($keys);
                }
            }
            $this->line("   ✅ Add {$totalMissing} missing key(s) to .env.example");
        }

        $extraInExample = $report['extra_in_example'] ?? [];
        if ($this->option('remove-extra') && ! empty($extraInExample) && is_array($extraInExample)) {
            $totalExtra = count($extraInExample);
            $this->line("   🗑️  Remove {$totalExtra} extra key(s) from .env.example");
        }

        if ($this->option('no-values')) {
            $this->line('   🔒 Keys will be added with empty values');
        } else {
            $this->line('   🔧 Appropriate example values will be generated');
        }
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    protected function showChanges(array $changes): void
    {
        if (isset($changes['created_file'])) {
            $this->line('📄 <info>Created new .env.example file</info>');

            return;
        }

        $added = $changes['added'] ?? [];
        if (! empty($added) && is_array($added)) {
            $this->line('✅ <info>Added keys:</info>');
            foreach ($added as $key) {
                $keyStr = is_string($key) ? $key : 'unknown_key';
                $this->line('   • <fg=green>'.$keyStr.'</fg=green>');
            }
        }

        $removed = $changes['removed'] ?? [];
        if (! empty($removed) && is_array($removed)) {
            $this->line('🗑️  <info>Removed keys:</info>');
            foreach ($removed as $key) {
                $keyStr = is_string($key) ? $key : 'unknown_key';
                $this->line('   • <fg=red>'.$keyStr.'</fg=red>');
            }
        }
    }

    protected function maskSensitiveValue(string $key, string $value): string
    {
        $sensitivePatterns = ['PASSWORD', 'SECRET', 'KEY', 'TOKEN'];

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains(strtoupper($key), $pattern)) {
                return str_repeat('*', min(strlen($value), 8));
            }
        }

        // Limit display length for non-sensitive values
        return strlen($value) > 50 ? substr($value, 0, 47).'...' : $value;
    }
}
