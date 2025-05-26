<?php

declare(strict_types=1);

namespace EnvValidator\Core;

use EnvValidator\Collections\NetworkRules\UrlRule;
use EnvValidator\Collections\StringRules\BooleanRule;
use EnvValidator\Collections\StringRules\InRule;
use EnvValidator\Collections\StringRules\KeyRule;

/**
 * Manages default validation rule presets for common environments and use cases.
 *
 * This class provides predefined sets of validation rules using Rule objects
 * for better type safety, reusability, and maintainability.
 *
 * Benefits of this approach:
 * - Type safety and IDE autocompletion
 * - Reusable rule objects
 * - Better testing capabilities
 * - Cleaner configuration
 * - More expressive and readable code
 */
final class DefaultRulePresets
{
    /**
     * Get the complete set of Laravel default rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function laravel(): array
    {
        return array_merge(
            self::application(),
            self::localization()
        );
    }

    /**
     * Get core application configuration rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function application(): array
    {
        return [
            'APP_NAME' => [
                'required',
                'string',
            ],
            'APP_ENV' => [
                'required',
                'string',
                new InRule(['local', 'development', 'staging', 'production']),
            ],
            'APP_KEY' => [
                'required',
                'string',
                new KeyRule,
            ],
            'APP_DEBUG' => [
                'required',
                new BooleanRule,
            ],
            'APP_URL' => [
                'required',
                new UrlRule,
            ],
        ];
    }

    /**
     * Get localization configuration rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function localization(): array
    {
        return [
            'APP_LOCALE' => [
                'required',
                'string',
            ],
            'APP_FALLBACK_LOCALE' => [
                'required',
                'string',
            ],
            'APP_FAKER_LOCALE' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Get minimal application rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function minimal(): array
    {
        return [
            'APP_NAME' => [
                'required',
                'string',
            ],
            'APP_ENV' => [
                'required',
                'string',
                new InRule(['local', 'development', 'staging', 'production']),
            ],
            'APP_KEY' => [
                'required',
                'string',
                new KeyRule,
            ],
            'APP_DEBUG' => [
                'required',
                new BooleanRule,
            ],
        ];
    }

    /**
     * Get production-ready application rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function production(): array
    {
        return array_merge(
            [
                'APP_NAME' => [
                    'required',
                    'string',
                ],
                'APP_ENV' => [
                    'required',
                    'string',
                    new InRule(
                        ['staging', 'production'],
                        'The :attribute must be either staging or production for production environments.'
                    ),
                ],
                'APP_KEY' => [
                    'required',
                    'string',
                    new KeyRule,
                ],
                'APP_DEBUG' => [
                    'required',
                    new BooleanRule,
                ],
                'APP_URL' => [
                    'required',
                    new UrlRule,
                ],
            ],
            self::localization()
        );
    }

    /**
     * Get API-focused application rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function api(): array
    {
        return [
            'APP_NAME' => [
                'required',
                'string',
            ],
            'APP_ENV' => [
                'required',
                'string',
                new InRule(['local', 'development', 'staging', 'production']),
            ],
            'APP_KEY' => [
                'required',
                'string',
                new KeyRule,
            ],
            'APP_DEBUG' => [
                'required',
                new BooleanRule,
            ],
            'APP_URL' => [
                'required',
                new UrlRule,
            ],
            'APP_LOCALE' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get database configuration rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function database(): array
    {
        return [
            'DB_CONNECTION' => [
                'required',
                'string',
                new InRule(['mysql', 'pgsql', 'sqlite', 'sqlsrv']),
            ],
            'DB_HOST' => [
                'required_unless:DB_CONNECTION,sqlite',
                'string',
            ],
            'DB_PORT' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
            'DB_DATABASE' => [
                'required',
                'string',
            ],
            'DB_USERNAME' => [
                'required_unless:DB_CONNECTION,sqlite',
                'string',
            ],
            'DB_PASSWORD' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Get cache and session configuration rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function cache(): array
    {
        return [
            'CACHE_DRIVER' => [
                'required',
                new InRule(
                    ['file', 'database', 'redis', 'memcached', 'dynamodb', 'array'],
                    'The :attribute must be a supported cache driver.'
                ),
            ],
            'SESSION_DRIVER' => [
                'required',
                new InRule(
                    ['file', 'cookie', 'database', 'redis', 'memcached', 'array'],
                    'The :attribute must be a supported session driver.'
                ),
            ],
            'SESSION_LIFETIME' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Get queue configuration rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function queue(): array
    {
        return [
            'QUEUE_CONNECTION' => [
                'required',
                new InRule(
                    ['sync', 'database', 'beanstalkd', 'sqs', 'redis'],
                    'The :attribute must be a supported queue connection.'
                ),
            ],
        ];
    }

    /**
     * Get mail configuration rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function mail(): array
    {
        return [
            'MAIL_MAILER' => [
                'required',
                new InRule(['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array']),
            ],
            'MAIL_HOST' => [
                'required_unless:MAIL_MAILER,log,array',
                'string',
            ],
            'MAIL_PORT' => [
                'required_unless:MAIL_MAILER,log,array',
                'integer',
                'min:1',
                'max:65535',
            ],
            'MAIL_USERNAME' => [
                'nullable',
                'string',
            ],
            'MAIL_PASSWORD' => [
                'nullable',
                'string',
            ],
            'MAIL_ENCRYPTION' => [
                'nullable',
                new InRule(['tls', 'ssl']),
            ],
        ];
    }

    /**
     * Get logging configuration rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function logging(): array
    {
        return [
            'LOG_CHANNEL' => [
                'required',
                new InRule(['stack', 'single', 'daily', 'slack', 'syslog', 'errorlog']),
            ],
            'LOG_LEVEL' => [
                'required',
                new InRule(
                    ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
                    'The :attribute must be a valid PSR-3 log level.'
                ),
            ],
        ];
    }

    /**
     * Get microservice-specific rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function microservice(): array
    {
        return array_merge(
            self::minimal(),
            [
                'SERVICE_NAME' => [
                    'required',
                    'string',
                    'min:2',
                    'max:50',
                ],
                'SERVICE_VERSION' => [
                    'required',
                    'string',
                ],
                'HEALTH_CHECK_PATH' => [
                    'nullable',
                    'string',
                ],
            ]
        );
    }

    /**
     * Get Docker/containerized application rules using Rule objects.
     *
     * @return array<string, array<int, string|object>>
     */
    public static function docker(): array
    {
        return array_merge(
            self::production(),
            [
                'CONTAINER_NAME' => [
                    'nullable',
                    'string',
                ],
                'DOCKER_NETWORK' => [
                    'nullable',
                    'string',
                ],
            ]
        );
    }
}
