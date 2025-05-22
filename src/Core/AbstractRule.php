<?php

declare(strict_types=1);

namespace EnvValidator\Core;

use Illuminate\Contracts\Validation\Rule;

/**
 * Abstract base class for environment validation rules.
 *
 * This class provides a standard implementation for validation rules
 * used within the EnvValidator package. All concrete validation rules
 * should extend this class and implement the passes() and message() methods.
 *
 * @see \Illuminate\Contracts\Validation\Rule
 */
abstract class AbstractRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute being validated
     * @param  mixed  $value  The value being validated
     * @return bool True if validation passes, false otherwise
     */
    abstract public function passes($attribute, $value);

    /**
     * Get the validation error message.
     *
     * @return string|array<int, string> The validation error message
     */
    abstract public function message();
}
