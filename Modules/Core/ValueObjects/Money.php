<?php

declare(strict_types=1);

namespace Modules\Core\ValueObjects;

use Modules\Core\Exceptions\ValidationException;

/**
 * Money Value Object
 * 
 * Represents a monetary amount with currency
 * Immutable - once created, cannot be changed
 * Uses BCMath for precise calculations
 */
final class Money
{
    private readonly string $amount;
    private readonly Currency $currency;
    private const SCALE = 2; // Decimal precision

    /**
     * Create a new money value object
     *
     * @param string|int|float $amount
     * @param Currency $currency
     * @throws ValidationException
     */
    public function __construct(string|int|float $amount, Currency $currency)
    {
        // Convert to string for BCMath
        $amount = (string) $amount;
        
        // Validate amount is numeric
        if (!is_numeric($amount)) {
            throw ValidationException::forField('amount', 'Amount must be numeric');
        }

        // Round to currency precision
        $this->amount = bcadd($amount, '0', self::SCALE);
        $this->currency = $currency;
    }

    /**
     * Get the amount value
     *
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Get the currency
     *
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Add money
     *
     * @param Money $other
     * @return static
     * @throws ValidationException
     */
    public function add(Money $other): static
    {
        $this->assertSameCurrency($other);
        
        $newAmount = bcadd($this->amount, $other->amount, self::SCALE);
        return new static($newAmount, $this->currency);
    }

    /**
     * Subtract money
     *
     * @param Money $other
     * @return static
     * @throws ValidationException
     */
    public function subtract(Money $other): static
    {
        $this->assertSameCurrency($other);
        
        $newAmount = bcsub($this->amount, $other->amount, self::SCALE);
        return new static($newAmount, $this->currency);
    }

    /**
     * Multiply by a factor
     *
     * @param string|int|float $multiplier
     * @return static
     */
    public function multiply(string|int|float $multiplier): static
    {
        $multiplier = (string) $multiplier;
        $newAmount = bcmul($this->amount, $multiplier, self::SCALE);
        return new static($newAmount, $this->currency);
    }

    /**
     * Divide by a divisor
     *
     * @param string|int|float $divisor
     * @return static
     * @throws ValidationException
     */
    public function divide(string|int|float $divisor): static
    {
        $divisor = (string) $divisor;
        
        if (bccomp($divisor, '0', self::SCALE) === 0) {
            throw ValidationException::forField('divisor', 'Cannot divide by zero');
        }
        
        $newAmount = bcdiv($this->amount, $divisor, self::SCALE);
        return new static($newAmount, $this->currency);
    }

    /**
     * Check if positive
     *
     * @return bool
     */
    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) > 0;
    }

    /**
     * Check if negative
     *
     * @return bool
     */
    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) < 0;
    }

    /**
     * Check if zero
     *
     * @return bool
     */
    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) === 0;
    }

    /**
     * Check if equals another money
     *
     * @param Money $other
     * @return bool
     */
    public function equals(Money $other): bool
    {
        return $this->currency->equals($other->currency) 
            && bccomp($this->amount, $other->amount, self::SCALE) === 0;
    }

    /**
     * Check if greater than another money
     *
     * @param Money $other
     * @return bool
     * @throws ValidationException
     */
    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return bccomp($this->amount, $other->amount, self::SCALE) > 0;
    }

    /**
     * Check if less than another money
     *
     * @param Money $other
     * @return bool
     * @throws ValidationException
     */
    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return bccomp($this->amount, $other->amount, self::SCALE) < 0;
    }

    /**
     * Assert same currency
     *
     * @param Money $other
     * @return void
     * @throws ValidationException
     */
    private function assertSameCurrency(Money $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw ValidationException::forField(
                'currency',
                "Cannot perform operation with different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }

    /**
     * Format for display
     *
     * @return string
     */
    public function format(): string
    {
        return $this->currency->getSymbol() . ' ' . number_format((float) $this->amount, self::SCALE);
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Create from amount and currency code
     *
     * @param string|int|float $amount
     * @param string $currencyCode
     * @return static
     * @throws ValidationException
     */
    public static function fromAmount(string|int|float $amount, string $currencyCode): static
    {
        return new static($amount, Currency::fromCode($currencyCode));
    }

    /**
     * Create zero money
     *
     * @param Currency $currency
     * @return static
     */
    public static function zero(Currency $currency): static
    {
        return new static('0', $currency);
    }

    /**
     * Serialize to array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => (string) $this->currency,
            'formatted' => $this->format(),
        ];
    }
}
