<?php

declare(strict_types=1);

namespace EnvValidator\Support;

use EnvValidator\Core\RuleRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use ReflectionClass;
use ReflectionException;

/**
 * Factory for creating validation rule instances.
 */
final readonly class RuleFactory
{
    /**
     * Create a new rule factory instance.
     *
     * @param  RuleRegistry  $registry  The rule registry
     */
    public function __construct(
        private RuleRegistry $registry
    ) {}

    /**
     * Create a new rule instance by name.
     *
     * @param  string  $name  The name of the rule
     * @param  array<int,mixed>  $parameters  Parameters to pass to the rule constructor
     *
     * @throws ReflectionException If the rule class cannot be instantiated
     */
    public function make(string $name, array $parameters = []): ?ValidationRule
    {
        $class = $this->registry->get($name);

        if ($class === null) {
            return null;
        }

        // If no parameters are provided, try to instantiate the class directly
        if (empty($parameters)) {
            return new $class;
        }

        // Use reflection to instantiate the class with parameters
        return (new ReflectionClass($class))->newInstanceArgs($parameters);
    }
}
