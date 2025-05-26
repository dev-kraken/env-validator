<?php

declare(strict_types=1);

namespace EnvValidator\Core\Validators;

use EnvValidator\Contracts\RuleValidatorInterface;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validator for Laravel ValidationRule objects.
 *
 * This validator handles validation using Laravel's ValidationRule interface,
 * allowing custom rules to be applied through the validate() method.
 */
final class ValidationRuleValidator implements RuleValidatorInterface
{
    /**
     * Validate a field value against a ValidationRule object.
     *
     * @param  array<string, string>  $messages
     * @param  array<string, array<int, string>>  $errors
     */
    public function validate(
        string $key,
        mixed $rule,
        mixed $value,
        array $messages,
        array &$errors
    ): void {
        if (! ($rule instanceof ValidationRule)) {
            return;
        }

        $rule->validate($key, $value, function (string $message, ?string $attribute = null) use ($key, $messages, &$errors): PotentiallyTranslatedString {
            $finalMessage = $messages["$key.custom"] ?? $message;
            $errors[$key][] = $finalMessage;

            // Get translator instance with proper type assertion
            /** @var Translator $translator */
            $translator = app('translator');

            return new PotentiallyTranslatedString($finalMessage, $translator);
        });
    }

    /**
     * Check if this validator can handle the given rule.
     */
    public function canHandle(mixed $rule): bool
    {
        return $rule instanceof ValidationRule;
    }
}
