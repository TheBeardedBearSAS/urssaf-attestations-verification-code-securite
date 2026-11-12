<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Wire;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\VerificationResultMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Json;

#[CoversClass(VerificationResultMapper::class)]
final class VerificationResultMapperRejectionTest extends TestCase
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, string}>
     */
    public static function invalidItems(): iterable
    {
        $both = ['codeSecurite' => 'A', 'infosSecurisation' => [], 'functionalFaultCodeSecurite' => []];
        $neither = ['codeSecurite' => 'A'];
        $message = 'Unexpected URSSAF response: item must carry exactly one of "infosSecurisation" and "functionalFaultCodeSecurite".';

        yield 'neither branch' => [$neither, $message];
        yield 'both branches' => [$both, $message];
        yield 'missing code' => [['infosSecurisation' => []], 'Unexpected URSSAF response: field "codeSecurite" is missing or empty.'];
        yield 'empty code' => [['codeSecurite' => '', 'infosSecurisation' => []], 'Unexpected URSSAF response: field "codeSecurite" is missing or empty.'];
        yield 'blank code' => [['codeSecurite' => '  ', 'infosSecurisation' => []], 'Unexpected URSSAF response: field "codeSecurite" is blank.'];
        yield 'numeric code' => [['codeSecurite' => 123, 'infosSecurisation' => []], 'Unexpected URSSAF response: field "codeSecurite" is missing or empty.'];
        yield 'invalid date' => [
            ['codeSecurite' => 'A', 'infosSecurisation' => ['date' => '31/02/2020']],
            'Unexpected URSSAF response: issue date is not a valid DD/MM/YYYY date.',
        ];
        yield 'iso date' => [
            ['codeSecurite' => 'A', 'infosSecurisation' => ['date' => '2020-10-02']],
            'Unexpected URSSAF response: issue date is not a valid DD/MM/YYYY date.',
        ];
        yield 'infos is a scalar' => [
            ['codeSecurite' => 'A', 'infosSecurisation' => 'x'],
            'Unexpected URSSAF response: field "infosSecurisation" is not an object.',
        ];
        yield 'fault is a scalar' => [
            ['codeSecurite' => 'A', 'functionalFaultCodeSecurite' => 'x'],
            'Unexpected URSSAF response: field "functionalFaultCodeSecurite" is not an object.',
        ];
    }

    /**
     * @param array<array-key, mixed> $item
     */
    #[DataProvider('invalidItems')]
    public function testItRejectsInvalidItems(array $item, string $message): void
    {
        // synthetic: no official example
        $this->expectException(UnexpectedUrssafResponse::class);
        $this->expectExceptionMessage($message);

        (new VerificationResultMapper())->map(Json::payload(['verifierCodeSecuriteAttestationGen' => [$item]]));
    }

    public function testItRejectsAnItemListThatIsNotAnArray(): void
    {
        $this->expectException(UnexpectedUrssafResponse::class);

        (new VerificationResultMapper())->map(Json::payload(['verifierCodeSecuriteAttestationGen' => ['a' => 1]]));
    }
}
