<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;

/**
 * Declares the services of the bundle from its processed configuration. Services are private (the default of the
 * container configurator), except the use case handlers (the entry points of the host application) and the simulated
 * gateway (tests only).
 *
 * @phpstan-type Config array{
 *     environment: string,
 *     base_uri: ?string,
 *     domain: ?string,
 *     token_safety_margin: int,
 *     http_client: ?string,
 *     http_timeout: float,
 *     credentials_provider: ?string,
 *     credentials: array<string, array{client_id: string, client_secret: string}>,
 *     tenants: list<string>,
 *     token_store: array{cache_pool: string, service: ?string},
 *     gateway: array{simulated: bool}
 * }
 */
final readonly class ServiceRegistrar
{
    public function __construct(private ServicesConfigurator $services) {}

    /**
     * @param Config $config
     */
    public function register(array $config): void
    {
        (new InfrastructureRegistrar($this->services))->register($config);
        (new GatewayRegistrar($this->services))->register($config);
        (new ApplicationRegistrar($this->services))->register();
        (new EntryPointRegistrar($this->services))->registerConsole();
        (new DiagnosticRegistrar($this->services))->register($config);
    }
}
