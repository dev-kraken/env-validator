<?php

declare(strict_types=1);

namespace EnvValidator\Core;

use Illuminate\Contracts\Validation\Rule;

/**
 * Registry for validation rules.
 *
 * This class provides a central location for registering and retrieving
 * validation rules, organized by categories.
 */
final class RuleRegistry
{
    /**
     * The registered validation rules.
     *
     * @var array<string, array<string, class-string<Rule>>>
     */
    private array $rules = [];

    /**
     * Register a validation rule.
     *
     * @param  string  $category  The category the rule belongs to
     * @param  string  $name  The name of the rule
     * @param  class-string<Rule>  $class  The rule class
     */
    public function register(string $category, string $name, string $class): self
    {
        if (! isset($this->rules[$category])) {
            $this->rules[$category] = [];
        }

        $this->rules[$category][$name] = $class;

        return $this;
    }

    /**
     * Get a validation rule by name.
     *
     * @param  string  $name  The name of the rule
     * @return class-string<Rule>|null
     */
    public function get(string $name): ?string
    {
        foreach ($this->rules as $category) {
            if (isset($category[$name])) {
                return $category[$name];
            }
        }

        return null;
    }

    /**
     * Get all validation rules.
     *
     * @return array<string, array<string, class-string<Rule>>>
     */
    public function all(): array
    {
        return $this->rules;
    }

    /**
     * Get all validation rules in a category.
     *
     * @param  string  $category  The category to get rules for
     * @return array<string, class-string<Rule>>
     */
    public function category(string $category): array
    {
        return $this->rules[$category] ?? [];
    }
}
