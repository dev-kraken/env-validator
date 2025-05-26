<?php

declare(strict_types=1);

namespace EnvValidator\Core;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;

/**
 * Abstract base class for environment validation rules.
 *
 * This class provides a standard implementation for validation rules
 * used within the EnvValidator package. All concrete validation rules
 * should extend this class and implement the validate() method.
 *
 * @see ValidationRule
 */
abstract class AbstractRule implements ValidationRule, ValidatorAwareRule
{
    /**
     * The validator instance.
     */
    protected ?Validator $validator = null;

    /**
     * Set the current validator.
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->passes($attribute, $value)) {
            $message = $this->message();
            $fail(is_array($message) ? implode(', ', $message) : $message);
        }
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute being validated
     * @param  mixed  $value  The value being validated
     * @return bool True if validation passes, false otherwise
     */
    abstract public function passes(string $attribute, mixed $value): bool;

    /**
     * Get the validation error message.
     *
     * @return string|array<int, string> The validation error message
     */
    abstract public function message(): array|string;
}
