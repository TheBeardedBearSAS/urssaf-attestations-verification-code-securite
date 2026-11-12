<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Clock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials\InMemoryCredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\TokenStore\Psr6TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\VerificationDomain;

/**
 * Generic infrastructure: clock, API configuration, credentials, access token cache.
 *
 * @phpstan-import-type Config from ServiceRegistrar
 */
final readonly class InfrastructureRegistrar
{
    public function __construct(private ServicesConfigurator $services) {}

    /**
     * @param Config $config
     */
    public function register(array $config): void
    {
        $this->services->set(Clock::class, Clock::class)
            ->factory(ClockFactory::class . '::create')
            ->args([new Reference('clock', ContainerInterface::NULL_ON_INVALID_REFERENCE)]);
        $this->services->set(ServiceIds::API_CONFIGURATION, ApiConfiguration::class)->args([
            // Resolved at runtime: the environment is usually an "%env(URSSAF_ENV)%" placeholder.
            (new Definition(Environment::class, [$config['environment']]))->setFactory([Environment::class, 'from']),
            $config['base_uri'],
            $config['domain'] === null ? null : VerificationDomain::from($config['domain']),
            $config['token_safety_margin'],
            ApiConfiguration::DEFAULT_USER_AGENT,
        ]);
        $this->registerCredentials($config['credentials_provider'], $config['credentials']);
        $this->registerTokenStore($config['token_store']);
    }

    /**
     * @param array<string, array{client_id: string, client_secret: string}> $credentials
     */
    private function registerCredentials(?string $provider, array $credentials): void
    {
        if ($provider !== null) {
            $this->services->alias(CredentialsProvider::class, $provider);

            return;
        }
        $definitions = [];
        foreach ($credentials as $tenantId => $tenant) {
            $definitions[$tenantId] = (new Definition(Credentials::class, [$tenant['client_id'], $tenant['client_secret']]))
                ->setFactory([Credentials::class, 'of']);
        }
        $this->services->set(CredentialsProvider::class, InMemoryCredentialsProvider::class)->args([$definitions]);
    }

    /**
     * @param array{cache_pool: string, service: ?string} $tokenStore
     */
    private function registerTokenStore(array $tokenStore): void
    {
        if ($tokenStore['service'] !== null) {
            $this->services->alias(TokenStore::class, $tokenStore['service']);

            return;
        }
        $this->services->set(TokenStore::class, Psr6TokenStore::class)->args([new Reference($tokenStore['cache_pool'])]);
    }
}
