<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate;

use Stringable;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvalidValue;

/**
 * Security code printed on a certificate (wire "codeSecurite"). Surrounding whitespace is trimmed; no other format
 * rule is applied because the specification does not define one.
 */
final readonly class SecurityCode implements Stringable
{
    private const SUBJECT = 'security_code';

    private function __construct(private string $value) {}

    /**
     * @throws InvalidValue "security_code.empty"
     */
    public static function fromString(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw InvalidValue::empty(self::SUBJECT);
        }

        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
