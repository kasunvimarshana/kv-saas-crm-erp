<?php

declare(strict_types=1);

namespace Modules\Core\ValueObjects;

use Modules\Core\Exceptions\ValidationException;

/**
 * Email Value Object
 * 
 * Represents an email address with validation
 * Immutable - once created, cannot be changed
 */
final class Email
{
    private readonly string $value;

    /**
     * Create a new email value object
     *
     * @param string $email
     * @throws ValidationException
     */
    public function __construct(string $email)
    {
        $email = trim(strtolower($email));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::forField('email', 'Invalid email address format');
        }

        $this->value = $email;
    }

    /**
     * Get the email value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the domain part of the email
     *
     * @return string
     */
    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    /**
     * Get the local part of the email
     *
     * @return string
     */
    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    /**
     * Check if email equals another email
     *
     * @param Email $other
     * @return bool
     */
    public function equals(Email $other): bool
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
     * @param string $email
     * @return static
     * @throws ValidationException
     */
    public static function fromString(string $email): static
    {
        return new static($email);
    }

    /**
     * Serialize to array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'domain' => $this->getDomain(),
            'local_part' => $this->getLocalPart(),
        ];
    }
}
