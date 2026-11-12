<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\App\TestKernel;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;

final class BundleConfigurationValidationTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('invalidConfigurations')]
    public function testRejectsAnInvalidConfiguration(array $config, string $message): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($message);

        Harness::boot($config);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidConfigurations(): iterable
    {
        yield 'unknown environment' => [['environment' => 'staging'], 'Unknown environment "staging": use "sandbox" or "production".'];
        yield 'negative safety margin' => [['token_safety_margin' => -1], 'token_safety_margin'];
        yield 'unknown domain' => [['domain' => 'CNAF'], 'urssaf_attestations_verification_code_securite.domain'];
        yield 'timeout too small' => [['http_timeout' => 0.0], 'http_timeout'];
        yield 'credentials and provider' => [['credentials_provider' => 'test.credentials_provider'], '"credentials" and "credentials_provider" are mutually exclusive.'];
        // @mago-expect lint:no-literal-password Fake credentials of a test configuration.
        yield 'empty client id' => [['credentials' => ['tenant-1' => ['client_id' => '', 'client_secret' => 'x']]], 'client_id'];
        yield 'empty client secret' => [['credentials' => ['tenant-1' => ['client_id' => 'x', 'client_secret' => '']]], 'client_secret'];
        yield 'missing client secret' => [['credentials' => ['tenant-1' => ['client_id' => 'x', 'client_secret' => null]]], 'client_secret'];
        yield 'empty cache pool' => [['token_store' => ['cache_pool' => '']], 'token_store.cache_pool'];
        yield 'empty tenant' => [['tenants' => ['']], 'tenants'];
        yield 'simulated gateway in production' => [
            ['environment' => 'production', 'gateway' => ['simulated' => true]],
            '"gateway.simulated" is meant for tests and cannot be used with "environment: production".',
        ];
    }

    public function testTheEnvironmentIsRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('environment');

        (new TestKernel(['environment' => null]))->boot();
    }

    public function testSomeCredentialsSourceIsRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configure either "credentials" or "credentials_provider".');

        Harness::boot(['credentials' => null]);
    }

    public function testTheSimulatedGatewayIsAllowedInSandbox(): void
    {
        $harness = Harness::boot(['gateway' => ['simulated' => true]]);

        static::assertTrue($harness->has(VerifyCertificateHandler::class));
    }

    public function testTheCredentialsProviderAloneIsAccepted(): void
    {
        $harness = Harness::boot(['credentials' => null, 'credentials_provider' => 'test.credentials_provider']);

        static::assertTrue($harness->has(VerifyCertificateHandler::class));
    }
}
