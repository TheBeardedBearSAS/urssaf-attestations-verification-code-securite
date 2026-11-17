<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CertificateDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\CalendarDate;

/**
 * Valid certificate (wire "infosSecurisation": "hashKey", "nature", "denomination", "codeOrganismeEmetteur",
 * "libelleOrganismeEmetteur", "date", "infosSecurisationSpecifiques"). The denomination is personal data and is
 * masked in __debugInfo().
 */
final readonly class VerifiedCertificate implements VerificationResult
{
    /**
     * @param list<CertificateDetails> $details
     */
    private function __construct(
        private SecurityCode $securityCode,
        private ?string $hashKey,
        private ?string $natureCode,
        private ?string $denomination,
        private IssuingBody $issuingBody,
        private ?CalendarDate $issuedOn,
        private array $details,
    ) {}

    /**
     * @param list<CertificateDetails> $details
     */
    // @mago-expect lint:excessive-parameter-list Mirrors the seven fields of the wire item.
    public static function of(
        SecurityCode $securityCode,
        ?string $hashKey,
        ?string $natureCode,
        ?string $denomination,
        IssuingBody $issuingBody,
        ?CalendarDate $issuedOn,
        array $details,
    ): self {
        return new self($securityCode, $hashKey, $natureCode, $denomination, $issuingBody, $issuedOn, $details);
    }

    public function securityCode(): SecurityCode
    {
        return $this->securityCode;
    }

    public function hashKey(): ?string
    {
        return $this->hashKey;
    }

    /**
     * Raw wire value of "nature".
     */
    public function natureCode(): ?string
    {
        return $this->natureCode;
    }

    /**
     * Typed view of {@see self::natureCode()}, null when absent or unknown.
     */
    public function nature(): ?CertificateNature
    {
        return $this->natureCode === null ? null : CertificateNature::tryFrom($this->natureCode);
    }

    public function denomination(): ?string
    {
        return $this->denomination;
    }

    public function issuingBody(): IssuingBody
    {
        return $this->issuingBody;
    }

    public function issuedOn(): ?CalendarDate
    {
        return $this->issuedOn;
    }

    /**
     * @return list<CertificateDetails>
     */
    public function details(): array
    {
        return $this->details;
    }

    public function isValid(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'securityCode' => $this->securityCode,
            'hashKey' => $this->hashKey,
            'natureCode' => $this->natureCode,
            'denomination' => $this->denomination === null ? null : '***',
            'issuingBody' => $this->issuingBody,
            'issuedOn' => $this->issuedOn,
            'details' => $this->details,
        ];
    }
}
