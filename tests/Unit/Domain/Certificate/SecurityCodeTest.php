<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Domain\Certificate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvalidValue;

#[CoversClass(SecurityCode::class)]
final class SecurityCodeTest extends TestCase
{
    public function testValueIsTrimmed(): void
    {
        $code = SecurityCode::fromString(" MWLJU8HKNPLF5X9 \n");

        static::assertSame('MWLJU8HKNPLF5X9', $code->value());
        static::assertSame('MWLJU8HKNPLF5X9', (string) $code);
    }

    #[DataProvider('blankValues')]
    public function testBlankValuesAreRejected(string $value): void
    {
        try {
            SecurityCode::fromString($value);
            static::fail('Expected exception');
        } catch (InvalidValue $exception) {
            static::assertSame('security_code.empty', $exception->violationCode());
            static::assertSame('Invalid security_code: the value must not be empty.', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function blankValues(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces' => ['   '];
        yield 'whitespace' => ["\t\n"];
    }

    public function testEquality(): void
    {
        static::assertTrue(SecurityCode::fromString('ABC')->equals(SecurityCode::fromString(' ABC')));
        static::assertFalse(SecurityCode::fromString('ABC')->equals(SecurityCode::fromString('abc')));
    }
}
