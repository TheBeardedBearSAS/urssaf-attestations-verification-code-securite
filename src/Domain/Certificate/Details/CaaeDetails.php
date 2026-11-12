<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details;

/**
 * Wire key "infosSecurisationSpecifiquesCAAE": etablissement[].
 */
final readonly class CaaeDetails implements CertificateDetails
{
    /**
     * @param list<Establishment> $establishments
     */
    private function __construct(
        private array $establishments,
    ) {}

    /**
     * @param list<Establishment> $establishments
     */
    public static function of(
        array $establishments,
    ): self {
        return new self($establishments);
    }

    /**
     * @return list<Establishment>
     */
    public function establishments(): array
    {
        return $this->establishments;
    }
}
