<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection;

/**
 * Ids of the private services of the bundle that have no interface to be identified by.
 */
final class ServiceIds
{
    public const SYMFONY_HTTP_CLIENT = 'urssaf_attestations_verification_code_securite.http_client';
    public const PSR18_CLIENT = 'urssaf_attestations_verification_code_securite.psr18_client';
    public const API_CONFIGURATION = 'urssaf_attestations_verification_code_securite.api_configuration';
    public const GATEWAY_OPTIONS = 'urssaf_attestations_verification_code_securite.gateway_options';
    public const HTTP_TRANSPORT = 'urssaf_attestations_verification_code_securite.http_transport';
    /** Token provider of the "urssaf:check" command: its own in-memory store, so that every check asks URSSAF. */
    public const PROBE_TOKEN_PROVIDER = 'urssaf_attestations_verification_code_securite.probe_token_provider';
}
