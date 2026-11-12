<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details;

/**
 * Wire key "infosSecurisationSpecifiquesAVG": siren, nir, etablissement[], dernierePeriodeAnalysee, masseSalariale, effectif.
 * Personal data is masked in __debugInfo().
 */
final readonly class AvgDetails implements CertificateDetails
{
    /**
     * @param list<Establishment> $establishments
     */
    private function __construct(
        private ?string $siren,
        private ?string $nir,
        private array $establishments,
        private ?string $lastAnalysedPeriod,
        private ?string $payroll,
        private ?string $headcount,
    ) {}

    /**
     * @param list<Establishment> $establishments
     */
    // @mago-expect lint:excessive-parameter-list Mirrors the six wire fields of the AVG payload.
    public static function of(
        ?string $siren,
        ?string $nir,
        array $establishments,
        ?string $lastAnalysedPeriod,
        ?string $payroll,
        ?string $headcount,
    ): self {
        return new self($siren, $nir, $establishments, $lastAnalysedPeriod, $payroll, $headcount);
    }

    public function siren(): ?string
    {
        return $this->siren;
    }

    public function nir(): ?string
    {
        return $this->nir;
    }

    /**
     * @return list<Establishment>
     */
    public function establishments(): array
    {
        return $this->establishments;
    }

    public function lastAnalysedPeriod(): ?string
    {
        return $this->lastAnalysedPeriod;
    }

    public function payroll(): ?string
    {
        return $this->payroll;
    }

    public function headcount(): ?string
    {
        return $this->headcount;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'siren' => $this->siren,
            'nir' => $this->nir === null ? null : '***',
            'establishments' => $this->establishments,
            'lastAnalysedPeriod' => $this->lastAnalysedPeriod,
            'payroll' => $this->payroll,
            'headcount' => $this->headcount,
        ];
    }
}
