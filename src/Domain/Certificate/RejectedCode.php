<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate;

/**
 * Security code rejected with a functional fault (wire "functionalFaultCodeSecurite": "faultCode", "faultName").
 * The raw fault code is always kept, even when unknown to {@see FaultCode}.
 */
final readonly class RejectedCode implements VerificationResult
{
    private function __construct(
        private SecurityCode $securityCode,
        private ?string $faultCode,
        private ?string $faultName,
    ) {}

    public static function of(SecurityCode $securityCode, ?string $faultCode, ?string $faultName): self
    {
        return new self($securityCode, $faultCode, $faultName);
    }

    public function securityCode(): SecurityCode
    {
        return $this->securityCode;
    }

    public function faultCode(): ?string
    {
        return $this->faultCode;
    }

    public function fault(): ?FaultCode
    {
        return $this->faultCode === null ? null : FaultCode::tryFrom($this->faultCode);
    }

    public function faultName(): ?string
    {
        return $this->faultName;
    }

    public function isValid(): bool
    {
        return false;
    }
}
