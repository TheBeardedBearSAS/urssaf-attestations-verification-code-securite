<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvariantViolation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use Throwable;

/**
 * An in-memory URSSAF certificate verification API for the tests of a host application: it answers from the results
 * scripted by the test, without any network.
 *
 * Default behaviour: a security code that was not scripted is rejected as unknown (FUNC202, "Attestation inconnue").
 * Codes are matched by value. Scripting: {@see self::certify()}, {@see self::reject()}, {@see self::failNext()};
 * inspection: {@see self::calls()}.
 *
 * Never use it outside tests: nothing is sent to URSSAF.
 */
final class SimulatedCertificateVerificationGateway implements CertificateVerificationGateway
{
    private const UNKNOWN_FAULT_CODE = 'FUNC202';
    private const UNKNOWN_FAULT_NAME = 'Attestation inconnue';

    /** @var array<string, VerificationResult> scripted results, by security code value */
    private array $results = [];

    /** @var array<string, list<Throwable>> failures to throw, by operation */
    private array $failures = [];

    /** @var list<array{operation: Operation, tenantId: TenantId, securityCodes: list<SecurityCode>}> */
    private array $calls = [];

    /**
     * The code of the certificate is answered with this valid certificate from now on.
     */
    public function certify(VerifiedCertificate $certificate): void
    {
        $this->results[$certificate->securityCode()->value()] = $certificate;
    }

    /**
     * The code is answered with this functional fault from now on, for example `FUNC201` / "Attestation perimée depuis le 01/03/2019".
     */
    public function reject(SecurityCode $code, string $faultCode, string $faultName): void
    {
        $this->results[$code->value()] = RejectedCode::of($code, $faultCode, $faultName);
    }

    /**
     * The next call of the operation ({@see self::verify()} for `VerifyCertificate`, {@see self::verifyMany()} for
     * `VerifyCertificates`) throws the failure, for example `UrssafUnavailable::withStatus(...)`; failures queued for
     * the same operation are thrown in order.
     */
    public function failNext(Operation $operation, Throwable $failure): void
    {
        $this->failures[$operation->value][] = $failure;
    }

    /**
     * @return list<array{operation: Operation, tenantId: TenantId, securityCodes: list<SecurityCode>}> every call
     *                                                                                                  received, in order
     */
    public function calls(): array
    {
        return $this->calls;
    }

    public function verify(TenantId $tenantId, SecurityCode $securityCode): VerificationResult
    {
        $this->record(Operation::VerifyCertificate, $tenantId, [$securityCode]);
        $this->throwScriptedFailure(Operation::VerifyCertificate);

        return $this->answer($securityCode);
    }

    public function verifyMany(TenantId $tenantId, SecurityCode ...$securityCodes): array
    {
        $count = \count($securityCodes);
        if ($count < 1 || $count > self::MAX_CODES_PER_CALL) {
            throw InvariantViolation::because(
                'security_codes.count_out_of_range',
                \sprintf('The number of security codes must be between 1 and %d.', self::MAX_CODES_PER_CALL),
                ['count' => $count, 'min' => 1, 'max' => self::MAX_CODES_PER_CALL],
            );
        }

        $this->record(Operation::VerifyCertificates, $tenantId, array_values($securityCodes));
        $this->throwScriptedFailure(Operation::VerifyCertificates);

        return array_map($this->answer(...), array_values($securityCodes));
    }

    /**
     * @param list<SecurityCode> $securityCodes
     */
    private function record(Operation $operation, TenantId $tenantId, array $securityCodes): void
    {
        $this->calls[] = ['operation' => $operation, 'tenantId' => $tenantId, 'securityCodes' => $securityCodes];
    }

    private function throwScriptedFailure(Operation $operation): void
    {
        $queue = $this->failures[$operation->value] ?? [];
        $failure = array_shift($queue);
        $this->failures[$operation->value] = $queue;
        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }

    private function answer(SecurityCode $securityCode): VerificationResult
    {
        return $this->results[$securityCode->value()]
            ?? RejectedCode::of($securityCode, self::UNKNOWN_FAULT_CODE, self::UNKNOWN_FAULT_NAME);
    }
}
