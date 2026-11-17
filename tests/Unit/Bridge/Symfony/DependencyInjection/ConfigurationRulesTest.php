<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Bridge\Symfony\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection\ConfigurationRules;

#[CoversClass(ConfigurationRules::class)]
final class ConfigurationRulesTest extends TestCase
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, bool, bool}> credential sources, expected "both", expected "none"
     */
    public static function credentialSources(): iterable
    {
        $credentials = ['tenant-1' => ['client_id' => 'client-1']];

        yield 'credentials only' => [['credentials' => $credentials, 'credentials_provider' => null], false, false];
        yield 'provider only' => [['credentials' => [], 'credentials_provider' => 'app.provider'], false, false];
        yield 'both' => [['credentials' => $credentials, 'credentials_provider' => 'app.provider'], true, false];
        yield 'neither' => [['credentials' => [], 'credentials_provider' => null], false, true];
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[DataProvider('credentialSources')]
    public function testItDetectsTheCredentialSources(array $config, bool $both, bool $none): void
    {
        static::assertSame($both, ConfigurationRules::hasBothCredentialSources($config));
        static::assertSame($none, ConfigurationRules::hasNoCredentialSource($config));
    }

    /**
     * @return iterable<string, array{string, bool, bool}>
     */
    public static function gateways(): iterable
    {
        yield 'simulated in production' => ['production', true, true];
        yield 'simulated in sandbox' => ['sandbox', true, false];
        yield 'real gateway in production' => ['production', false, false];
        yield 'real gateway in sandbox' => ['sandbox', false, false];
    }

    #[DataProvider('gateways')]
    public function testTheSimulatedGatewayIsRefusedInProductionOnly(string $environment, bool $simulated, bool $refused): void
    {
        static::assertSame(
            $refused,
            ConfigurationRules::simulatedGatewayInProduction(['environment' => $environment, 'gateway' => ['simulated' => $simulated]]),
        );
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function environments(): iterable
    {
        yield 'sandbox' => ['sandbox', false];
        yield 'production' => ['production', false];
        yield 'placeholder dummy value' => ['', false];
        yield 'unknown name' => ['staging', true];
        yield 'wrong case' => ['Production', true];
        yield 'not a string' => [null, true];
    }

    #[DataProvider('environments')]
    public function testOnlyKnownEnvironmentsAndThePlaceholderValueAreAccepted(mixed $environment, bool $unknown): void
    {
        static::assertSame($unknown, ConfigurationRules::isUnknownEnvironment($environment));
    }
}
