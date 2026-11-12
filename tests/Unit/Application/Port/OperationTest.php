<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Port;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;

#[CoversClass(Operation::class)]
final class OperationTest extends TestCase
{
    public function testValuesAreTheOfficialOperationIds(): void
    {
        static::assertSame('verifierCodeSecuriteAttestationGen', Operation::VerifyCertificate->value);
        static::assertSame('verifierListeCodesSecuriteAttestationGen', Operation::VerifyCertificates->value);
        static::assertCount(2, Operation::cases());
    }
}
