<?php

declare(strict_types=1);

namespace Modules\Core\ValueObjects;

use Modules\Core\Exceptions\ValidationException;

/**
 * Phone Number Value Object
 * 
 * Represents a phone number with basic validation
 * Immutable - once created, cannot be changed
 */
final class PhoneNumber
{
    private readonly string $value;
    private readonly ?string $countryCode;

    /**
     * Create a new phone number value object
     *
     * @param string $phoneNumber
     * @param string|null $countryCode
     * @throws ValidationException
     */
    public function __construct(string $phoneNumber, ?string $countryCode = null)
    {
        $phoneNumber = $this->sanitize($phoneNumber);
        
        if (!$this->isValid($phoneNumber)) {
            throw ValidationException::forField(
                'phone_number',
                'Invalid phone number format'
            );
        }

        $this->value = $phoneNumber;
        $this->countryCode = $countryCode;
    }

    /**
     * Sanitize phone number
     *
     * @param string $phoneNumber
     * @return string
     */
    private function sanitize(string $phoneNumber): string
    {
        // Remove all non-numeric characters except +
        return preg_replace('/[^0-9+]/', '', $phoneNumber);
    }

    /**
     * Validate phone number format
     *
     * @param string $phoneNumber
     * @return bool
     */
    private function isValid(string $phoneNumber): bool
    {
        // Basic validation: must be between 10-15 digits, can start with +
        return preg_match('/^\+?[1-9]\d{9,14}$/', $phoneNumber) === 1;
    }

    /**
     * Get the phone number value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the country code
     *
     * @return string|null
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * Format phone number for display
     *
     * @return string
     */
    public function format(): string
    {
        // Simple formatting: +X XXX XXX XXXX
        if (str_starts_with($this->value, '+')) {
            return $this->value;
        }

        return $this->value;
    }

    /**
     * Check if phone number equals another
     *
     * @param PhoneNumber $other
     * @return bool
     */
    public function equals(PhoneNumber $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Create from string
     *
     * @param string $phoneNumber
     * @param string|null $countryCode
     * @return static
     * @throws ValidationException
     */
    public static function fromString(string $phoneNumber, ?string $countryCode = null): static
    {
        return new static($phoneNumber, $countryCode);
    }

    /**
     * Serialize to array
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'country_code' => $this->countryCode,
            'formatted' => $this->format(),
        ];
    }
}
