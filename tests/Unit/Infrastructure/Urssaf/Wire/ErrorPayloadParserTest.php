<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Wire;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\ErrorPayloadParser;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Json;

#[CoversClass(ErrorPayloadParser::class)]
final class ErrorPayloadParserTest extends TestCase
{
    public function testItParsesTheServiceCallFailure(): void
    {
        $errors = (new ErrorPayloadParser())->parseBody(Fixtures::load('error_func013'));

        static::assertSame(['FUNC013'], $errors->codes());
        static::assertSame("Echec de l'appel au service demandé", $errors->all()[0]->message());
        static::assertSame(
            "Echec de l'appel au service : GestionAttestationsService (operation VerifierCodeSecuriteGenAttestation)",
            $errors->all()[0]->description(),
        );
    }

    public function testItParsesTheMissingSecurityCode(): void
    {
        $errors = (new ErrorPayloadParser())->parseBody(Fixtures::load('error_func015'));

        static::assertSame(['FUNC015'], $errors->codes());
        static::assertSame('Le code de sécurité est obligatoire', $errors->all()[0]->message());
        static::assertSame(' Le code de sécurité est obligatoire', $errors->all()[0]->description());
    }

    public function testItParsesTheTooManyCodesError(): void
    {
        $errors = (new ErrorPayloadParser())->parseBody(Fixtures::load('error_too_many_codes'));

        static::assertSame(['FUNC013'], $errors->codes());
        static::assertSame('le nombre de code securite pour un appel est limité à 100', $errors->all()[0]->message());
    }

    public function testItParsesTheTechnicalErrorSingleObject(): void
    {
        $errors = (new ErrorPayloadParser())->parseBody(Fixtures::load('error_technical'));

        static::assertSame(['ERR_TECHNIQUE'], $errors->codes());
        static::assertSame('Erreur technique', $errors->all()[0]->message());
        static::assertSame('Une erreur système est survenue', $errors->all()[0]->description());
    }

    public function testItParsesTheErrorsEnvelopeAndNormalisesCodes(): void
    {
        $errors = (new ErrorPayloadParser())->parseBody('{"errors":[{"code":" FUNC 015 ","message":"m","description":"d"}]}');

        static::assertSame(['FUNC015'], $errors->codes());
        static::assertSame('m', $errors->all()[0]->message());
        static::assertSame('d', $errors->all()[0]->description());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unreadableBodies(): iterable
    {
        yield 'empty' => [''];
        yield 'html' => ['<html>Bad gateway</html>'];
        yield 'scalar' => ['"oops"'];
        yield 'empty object' => ['{}'];
        yield 'empty list' => ['[]'];
        yield 'list of scalars' => ['[1, 2]'];
        yield 'errors is not a list' => ['{"errors": {"code": "X"}}'];
    }

    #[DataProvider('unreadableBodies')]
    public function testItIsLenientWithUnreadableBodies(string $body): void
    {
        static::assertTrue((new ErrorPayloadParser())->parseBody($body)->isEmpty());
    }

    public function testEntriesWithoutACodeAreIgnoredAndMissingTextsDefaultToEmpty(): void
    {
        $errors = (new ErrorPayloadParser())->parseBody('[{"message":"no code"},{"code":""},{"code":"ERR_X"}]');

        static::assertSame(['ERR_X'], $errors->codes());
        static::assertSame('', $errors->all()[0]->message());
        static::assertSame('', $errors->all()[0]->description());
    }

    public function testParsePayloadIsStrictOnUnreadableStructures(): void
    {
        $this->expectException(UnexpectedUrssafResponse::class);

        (new ErrorPayloadParser())->parsePayload(Json::payload(['errors' => 'text']));
    }

    public function testParseEntriesWorksOnAnAlreadyExtractedList(): void
    {
        $entries = Json::payload(['errors' => [['code' => 'A'], ['code' => ' B ']]])->items('errors');

        static::assertSame(['A', 'B'], (new ErrorPayloadParser())->parseEntries($entries)->codes());
    }
}
