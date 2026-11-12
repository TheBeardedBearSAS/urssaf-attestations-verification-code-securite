<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Wire;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\JsonEncoder;

#[CoversClass(JsonEncoder::class)]
final class JsonEncoderTest extends TestCase
{
    /**
     * @return iterable<string, array{array<array-key, array<array-key, mixed>|string>, string}>
     */
    public static function documents(): iterable
    {
        yield 'empty array' => [[], '[]'];
        yield 'list of strings' => [['a', 'b'], '["a","b"]'];
        yield 'string with quote' => [['a"b'], '["a\"b"]'];
        yield 'slashes are not escaped' => [['a/b'], '["a/b"]'];
        yield 'unicode is not escaped' => [['Zoé'], '["Zoé"]'];
        yield 'object' => [['codes' => ['a', 'b']], '{"codes":["a","b"]}'];
        yield 'nested lists' => [[['a'], ['b', 'c']], '[["a"],["b","c"]]'];
    }

    /**
     * @param array<array-key, array<array-key, mixed>|string> $value
     */
    #[DataProvider('documents')]
    public function testItEncodesDocumentsExactly(array $value, string $expected): void
    {
        static::assertSame($expected, JsonEncoder::encode($value));
    }

    public function testInvalidUtf8IsRefused(): void
    {
        $this->expectException(JsonException::class);

        JsonEncoder::encode(["\xB1\x31"]);
    }
}
