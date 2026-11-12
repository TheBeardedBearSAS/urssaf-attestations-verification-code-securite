<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\IssuingBody;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvalidValue;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;

/**
 * Maps the 200 response (one item per requested code) to {@see VerificationResult}s (ADR 0002): each item carries
 * exactly one of `infosSecurisation` / `functionalFaultCodeSecurite`.
 */
final readonly class VerificationResultMapper
{
    public function __construct(private CertificateDetailsMapper $details = new CertificateDetailsMapper()) {}

    /**
     * @return list<VerificationResult>
     *
     * @throws UnexpectedUrssafResponse
     */
    public function map(Payload $response): array
    {
        return array_map($this->mapItem(...), $response->items('verifierCodeSecuriteAttestationGen'));
    }

    private function mapItem(Payload $item): VerificationResult
    {
        $code = $this->securityCode($item->requireString('codeSecurite'));
        $info = $item->object('infosSecurisation');
        $fault = $item->object('functionalFaultCodeSecurite');

        if ($info instanceof Payload && !$fault instanceof Payload) {
            return $this->certificate($code, $info);
        }

        if (!$info instanceof Payload && $fault instanceof Payload) {
            return RejectedCode::of($code, $fault->string('faultCode'), $fault->string('faultName'));
        }

        throw UnexpectedUrssafResponse::invalidPayload(
            'item must carry exactly one of "infosSecurisation" and "functionalFaultCodeSecurite"',
        );
    }

    private function certificate(SecurityCode $code, Payload $info): VerifiedCertificate
    {
        $date = $info->string('date');

        return VerifiedCertificate::of(
            $code,
            $info->string('hashKey'),
            $info->string('nature'),
            $info->string('denomination'),
            IssuingBody::of($info->string('codeOrganismeEmetteur'), $info->string('libelleOrganismeEmetteur')),
            $date === null ? null : DateEncoding::decodeIssueDate($date),
            $this->details->map($info->items('infosSecurisationSpecifiques')),
        );
    }

    private function securityCode(string $value): SecurityCode
    {
        try {
            return SecurityCode::fromString($value);
        } catch (InvalidValue $exception) {
            throw UnexpectedUrssafResponse::causedBy('field "codeSecurite" is blank', $exception);
        }
    }
}
