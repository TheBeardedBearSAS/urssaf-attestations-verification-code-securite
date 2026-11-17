<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire;

use Closure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AmpDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AsaaDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AvgDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CaaeDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CertificateDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CfpDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CsgDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\EpmDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\Establishment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\FrsDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\Instant;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;

/**
 * Maps `infosSecurisationSpecifiques` (one object per item, each holding one nature-specific key) to details.
 * An unknown key is ignored; only its name is logged (personal data must never reach the logs, api-reference section 4.6).
 */
final readonly class CertificateDetailsMapper
{
    public function __construct(private LoggerInterface $logger = new NullLogger()) {}

    /**
     * @param list<Payload> $items
     *
     * @return list<CertificateDetails>
     *
     * @throws UnexpectedUrssafResponse
     */
    public function map(array $items): array
    {
        $mappers = $this->mappers();
        $details = [];
        foreach ($items as $item) {
            foreach ($item->keys() as $key) {
                $mapper = $mappers[$key] ?? null;
                if ($mapper === null) {
                    // Unknown key: the value is not inspected (its shape is not part of the contract).
                    $this->logger->warning('Unknown URSSAF certificate details key ignored', ['key' => $key]);

                    continue;
                }

                $payload = $item->object($key);
                if ($payload instanceof Payload) {
                    $details[] = $mapper($payload);
                }
            }
        }

        return $details;
    }

    /**
     * @return array<string, Closure(Payload): CertificateDetails>
     */
    private function mappers(): array
    {
        return [
            'infosSecurisationSpecifiquesAMP' => fn(Payload $payload): CertificateDetails => AmpDetails::of(
                $payload->string('siren'),
                $payload->string('nir'),
                $this->establishments($payload),
            ),
            'infosSecurisationSpecifiquesAVG' => fn(Payload $payload): CertificateDetails => AvgDetails::of(
                $payload->string('siren'),
                $payload->string('nir'),
                $this->establishments($payload),
                $payload->string('dernierePeriodeAnalysee'),
                $payload->string('masseSalariale'),
                $payload->string('effectif'),
            ),
            'infosSecurisationSpecifiquesCSG' => fn(Payload $payload): CertificateDetails => CsgDetails::of(
                $payload->string('numExt'),
                $this->establishments($payload),
            ),
            'infosSecurisationSpecifiquesCFP' => fn(Payload $payload): CertificateDetails => CfpDetails::of(
                $payload->string('numExt'),
                $this->establishments($payload),
            ),
            'infosSecurisationSpecifiquesEPM' => fn(Payload $payload): CertificateDetails => EpmDetails::of($this->establishments($payload)),
            'infosSecurisationSpecifiquesFRS' => fn(Payload $payload): CertificateDetails => FrsDetails::of($this->establishments($payload)),
            'infosSecurisationSpecifiquesCAAE' => fn(Payload $payload): CertificateDetails => CaaeDetails::of($this->establishments($payload)),
            'infosSecurisationSpecifiquesASAA' => fn(Payload $payload): CertificateDetails => AsaaDetails::of(
                $payload->string('nomArtisteAuteur'),
                $payload->string('prenomArtisteAuteur'),
                $this->instant($payload->string('dateDebutDeclaration')),
                $this->instant($payload->string('dateFinDeclaration')),
            ),
        ];
    }

    /**
     * @return list<Establishment>
     */
    private function establishments(Payload $payload): array
    {
        return array_map(
            static fn(Payload $entry): Establishment => Establishment::of($entry->string('siret'), $entry->string('adresse')),
            $payload->items('etablissement'),
        );
    }

    private function instant(?string $value): ?Instant
    {
        return $value === null ? null : DateEncoding::decodeInstant($value);
    }
}
