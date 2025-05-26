<?php

declare(strict_types=1);

namespace EnvValidator\Collections\NetworkRules;

use EnvValidator\Core\AbstractRule;

/**
 * Validates that a value is a valid IP address.
 *
 * This rule checks if the value is a valid IP address (IPv4 or IPv6)
 * using PHP's built-in filter_var() function.
 *
 * @example
 * ```php
 * // Validate any IP address (IPv4 or IPv6)
 * $rule = new IpRule();
 *
 * // Validate IPv4 addresses only
 * $rule = new IpRule(IpRule::IPV4);
 *
 * // Validate IPv6 addresses only
 * $rule = new IpRule(IpRule::IPV6);
 * ```
 */
final class IpRule extends AbstractRule
{
    /**
     * IP version constants.
     */
    public const IPV4 = 'ipv4';
    public const IPV6 = 'ipv6';
    public const ANY = 'any';

    /**
     * Create a new IP rule instance.
     *
     * @param  string  $version  IP version to validate (ipv4, ipv6, or any)
     */
    public function __construct(
        private readonly string $version = self::ANY
    ) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute being validated
     * @param  mixed  $value  The value being validated
     * @return bool True if validation passes, false otherwise
     */
    public function passes(string $attribute, mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return match ($this->version) {
            self::IPV4 => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            self::IPV6 => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            default => filter_var($value, FILTER_VALIDATE_IP) !== false,
        };
    }

    /**
     * Get the validation error message.
     *
     * @return string The validation error message
     */
    public function message(): string
    {
        return match ($this->version) {
            self::IPV4 => 'The :attribute must be a valid IPv4 address.',
            self::IPV6 => 'The :attribute must be a valid IPv6 address.',
            default => 'The :attribute must be a valid IP address.',
        };
    }
}
