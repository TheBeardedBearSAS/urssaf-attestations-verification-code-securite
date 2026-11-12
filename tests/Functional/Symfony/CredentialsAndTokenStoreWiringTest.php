<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials\InMemoryCredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\TokenStore\Psr6TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\InMemoryTokenStore;

final class CredentialsAndTokenStoreWiringTest extends TestCase
{
    public function testTheDefaultTokenStoreIsThePsr6StoreOfTheAppCachePool(): void
    {
        $harness = Harness::boot();

        static::assertInstanceOf(Psr6TokenStore::class, $harness->privateService(TokenStore::class, TokenStore::class));
    }

    public function testTheTokenIsStoredInTheConfiguredCachePool(): void
    {
        $harness = Harness::boot(['token_store' => ['cache_pool' => 'test.pool']]);

        self::verify($harness);

        $pool = $harness->privateService('test.pool', ArrayAdapter::class);
        static::assertCount(1, $pool->getValues());
    }

    public function testTheTokenStoreServiceTakesPrecedenceOverTheCachePool(): void
    {
        $harness = Harness::boot(['token_store' => ['cache_pool' => 'test.pool', 'service' => 'test.token_store']]);

        self::verify($harness);

        $store = $harness->privateService('test.token_store', InMemoryTokenStore::class);
        static::assertSame($store, $harness->privateService(TokenStore::class, TokenStore::class));
        static::assertCount(1, $store->keys());
        static::assertSame([], $harness->privateService('test.pool', ArrayAdapter::class)->getValues());
    }

    public function testTheCredentialsOfTheConfigurationAreServedByAnInMemoryProvider(): void
    {
        $harness = Harness::boot();

        $provider = $harness->privateService(CredentialsProvider::class, CredentialsProvider::class);

        static::assertInstanceOf(InMemoryCredentialsProvider::class, $provider);
        static::assertSame('client-1', $provider->forTenant(TenantId::fromString('tenant-1'))->clientId());
    }

    public function testTheCredentialsProviderServiceIsUsed(): void
    {
        $harness = Harness::boot(['credentials' => null, 'credentials_provider' => 'test.credentials_provider']);

        static::assertSame(
            $harness->privateService('test.credentials_provider', CredentialsProvider::class),
            $harness->privateService(CredentialsProvider::class, CredentialsProvider::class),
        );
        self::verify($harness);
        static::assertStringContainsString('client_id=provider-client', (string) $harness->client->getRequests()[0]->getBody());
    }

    private static function verify(Harness $harness): void
    {
        $harness->get(VerifyCertificateHandler::class)(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString('42TROLLQU3DT999')),
        );
    }
}
