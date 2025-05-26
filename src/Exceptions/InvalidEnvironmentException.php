<?php

declare(strict_types=1);

namespace EnvValidator\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when environment validation fails.
 */
final class InvalidEnvironmentException extends Exception
{
    /**
     * The validation errors.
     *
     * @var array<string, array<int, string>>|null
     */
    private ?array $validationErrors;

    /**
     * Create a new invalid environment exception.
     *
     * @param  array<string, array<int, string>>|null  $errors
     */
    public function __construct(
        string $message = 'Environment validation failed',
        ?array $errors = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->validationErrors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<int, string>>|null
     */
    public function getValidationErrors(): ?array
    {
        return $this->validationErrors;
    }

    /**
     * Get a flattened array of error messages.
     *
     * @return array<int, string>
     */
    public function getErrorMessages(): array
    {
        if ($this->validationErrors === null) {
            return [];
        }

        $messages = [];
        foreach ($this->validationErrors as $errors) {
            foreach ($errors as $error) {
                if (is_string($error)) {
                    $trimmed = trim($error);
                    if ($trimmed !== '') {
                        $cleanMessage = preg_replace('/\s+/', ' ', $trimmed);
                        if ($cleanMessage !== null) {
                            $messages[] = $cleanMessage;
                        }
                    }
                }
            }
        }

        /** @var array<int, string> $messages */
        return $messages;
    }

    /**
     * Format the validation errors as a string.
     */
    public function formatErrors(): string
    {
        return implode(PHP_EOL, $this->getErrorMessages());
    }
}
