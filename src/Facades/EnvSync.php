<?php

declare(strict_types=1);

namespace EnvValidator\Facades;

use EnvValidator\Services\EnvExampleSyncService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, mixed> compareFiles()
 * @method static array<string, mixed> getSyncReport()
 * @method static array<string, mixed> syncToExample(array<string, mixed> $options = [])
 * @method static bool envFileExists()
 * @method static bool exampleFileExists()
 * @method static array<string, string> suggestValidationRules(array<string, string> $keys)
 *
 * @see \EnvValidator\Services\EnvExampleSyncService
 */
class EnvSync extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return EnvExampleSyncService::class;
    }
}
