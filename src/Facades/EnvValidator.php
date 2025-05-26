<?php

declare(strict_types=1);

namespace EnvValidator\Facades;

use EnvValidator\EnvValidator as EnvValidatorClass;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin EnvValidatorClass
 *
 * @method static bool validate(?array<string, mixed> $env = null)
 * @method static bool validateOnly(array<int, string> $keys, ?array<string, mixed> $env = null)
 * @method static EnvValidatorClass setRules(array<string, mixed> $rules)
 * @method static EnvValidatorClass addRule(string $key, array<mixed>|string $rules)
 * @method static EnvValidatorClass setMessages(array<string, string> $messages)
 * @method static array<string, mixed> getRules()
 * @method static bool|array<string, array<int, string>> validateStandalone(array<string, mixed> $env, array<string, mixed> $rules, array<string, string> $messages = [])
 *
 * @see     EnvValidatorClass
 */
class EnvValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'env-validator';
    }
}
