<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * Configuration tree of the "urssaf_attestations_verification_code_securite" bundle configuration.
 */
final class BundleConfiguration
{
    public static function define(DefinitionConfigurator $definition): void
    {
        $root = $definition->rootNode();
        $root
            ->validate()
                ->ifTrue(ConfigurationRules::hasBothCredentialSources(...))
                ->thenInvalid('"credentials" and "credentials_provider" are mutually exclusive.')
            ->end()
            ->validate()
                ->ifTrue(ConfigurationRules::hasNoCredentialSource(...))
                ->thenInvalid('Configure either "credentials" or "credentials_provider".')
            ->end()
            ->validate()
                ->ifTrue(ConfigurationRules::simulatedGatewayInProduction(...))
                ->thenInvalid('"gateway.simulated" is meant for tests and cannot be used with "environment: production".')
            ->end()
            ->children()
                ->scalarNode('environment')
                    ->isRequired()
                    ->info('"sandbox" or "production"; usually "%env(URSSAF_ENV)%" (resolved at runtime).')
                    ->validate()
                        ->ifTrue(ConfigurationRules::isUnknownEnvironment(...))
                        ->thenInvalid('Unknown environment %s: use "sandbox" or "production".')
                    ->end()
                ->end()
                ->scalarNode('base_uri')->defaultNull()->info('Overrides the base URI of the selected environment.')->end()
                ->enumNode('domain')
                    ->values(['URSSAF', 'ISU', null])
                    ->defaultValue('URSSAF')
                    ->info('Value of the "domaine" query parameter of the GET operation: "URSSAF" or "ISU"; null omits it (ADR 0005).')
                ->end()
                ->integerNode('token_safety_margin')->min(0)->defaultValue(60)->info('Seconds before expiry from which an access token is renewed.')->end()
                ->scalarNode('http_client')->defaultNull()->info('Service id of a PSR-18 client (default: a dedicated Symfony HttpClient).')->end()
                ->floatNode('http_timeout')->min(0.1)->defaultValue(15.0)->info('Timeout in seconds of the default HTTP client.')->end()
                ->scalarNode('credentials_provider')->defaultNull()->info('Service id of a CredentialsProvider.')->end()
                ->arrayNode('credentials')
                    ->info('Credentials by tenant id; use "%env(...)%" placeholders.')
                    ->useAttributeAsKey('tenant_id')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('client_id')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('client_secret')->isRequired()->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('tenants')
                    ->info('Tenants processed by commands that cannot discover them (default: the keys of "credentials").')
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('token_store')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('cache_pool')->defaultValue('cache.app')->cannotBeEmpty()->end()
                        ->scalarNode('service')->defaultNull()->info('Service id of a TokenStore (takes precedence over the cache pool).')->end()
                    ->end()
                ->end()
                ->arrayNode('gateway')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('simulated')->defaultFalse()->info('Tests only: replaces the URSSAF API with the in-memory Testing\\SimulatedCertificateVerificationGateway (public service, to script the scenarios).')->end()
                    ->end()
                ->end()
            ->end();
    }
}
