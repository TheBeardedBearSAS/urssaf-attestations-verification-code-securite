<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details;

/**
 * Wire key "infosSecurisationSpecifiquesCFP": numExt, etablissement[].
 */
final readonly class CfpDetails implements CertificateDetails
{
    /**
     * @param list<Establishment> $establishments
     */
    private function __construct(
        private ?string $externalNumber,
        private array $establishments,
    ) {}

    /**
     * @param list<Establishment> $establishments
     */
    public static function of(
        ?string $externalNumber,
        array $establishments,
    ): self {
        return new self($externalNumber, $establishments);
    }

    public function externalNumber(): ?string
    {
        return $this->externalNumber;
    }

    /**
     * @return list<Establishment>
     */
    public function establishments(): array
    {
        return $this->establishments;
    }
}
