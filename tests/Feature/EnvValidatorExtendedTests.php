<?php

namespace EnvValidator\Tests\Feature;

use EnvValidator\EnvValidator;
use EnvValidator\Exceptions\InvalidEnvironmentException;
use EnvValidator\Tests\TestCase;

class EnvValidatorExtendedTests extends TestCase
{
    public function test_validator_throws_appropriate_exception_with_invalid_input(): void
    {
        $this->expectException(InvalidEnvironmentException::class);

        $validator = app(EnvValidator::class);
        $validator->validate(['APP_ENV' => 'invalid_environment']);
    }

    public function test_validator_properly_merges_all_environment_sources(): void
    {
        // Backup current environment
        $oldAppEnv = $_ENV['APP_ENV'] ?? null;
        $oldServerAppEnv = $_SERVER['APP_ENV'] ?? null;

        try {
            // Setup test environment with variables in different places
            unset($_ENV['APP_ENV']);
            $_SERVER['APP_ENV'] = 'production';

            $validator = app(EnvValidator::class);

            // This should pass because we're now merging $_SERVER into our environment
            $result = $validator->validate();

            $this->assertTrue($result);
        } catch (InvalidEnvironmentException $e) {
            $this->fail($e->getMessage());
        } finally {
            // Restore environment
            if ($oldAppEnv !== null) {
                $_ENV['APP_ENV'] = $oldAppEnv;
            }

            if ($oldServerAppEnv !== null) {
                $_SERVER['APP_ENV'] = $oldServerAppEnv;
            } else {
                unset($_SERVER['APP_ENV']);
            }
        }
    }
}
