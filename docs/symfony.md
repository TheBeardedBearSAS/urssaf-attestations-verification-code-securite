# Symfony bundle

Bundle class: `TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle`
(alias `urssaf_attestations_verification_code_securite`), for Symfony 6.4 and 7.x.

The package is stateless ([ADR 0001](adr/0001-stateless-sdk.md)): the bundle needs no database, no Messenger, no
Scheduler and no lock. The only state it shares is the cache of the OAuth2 access tokens.

## Installation with Symfony Flex

The recipe is published on the public `symfony/recipes-contrib` repository
([ADR 0009](adr/0009-flex-recipe-on-recipes-contrib.md), submitted in
[symfony/recipes-contrib#2120](https://github.com/symfony/recipes-contrib/pull/2120); until it is merged, install the
bundle by hand, next section). Its source lives in
`recipe/thebeardedbear/urssaf-attestations-verification-code-securite/<version>/` (manifest and copied files). Contrib
recipes must be allowed once in the application:

```sh
composer config extra.symfony.allow-contrib true
```

`composer require thebeardedbear/urssaf-attestations-verification-code-securite` then registers the bundle, copies a
commented `config/packages/urssaf_attestations_verification_code_securite.yaml` (the simulated gateway in the `test`
environment) and adds `URSSAF_ENV`, `URSSAF_CLIENT_ID` and `URSSAF_CLIENT_SECRET` to `.env`. Check the result with
`php bin/console urssaf:check`. A test boots the bundle with the copied configuration, so that the recipe follows the
configuration tree. Add a new version directory when a release changes the recipe (Flex picks the highest version not
above the installed package version), and submit it to `symfony/recipes-contrib` in the same form.

Without the recipe, do these steps by hand (next section).

## Manual installation

```sh
composer require thebeardedbear/urssaf-attestations-verification-code-securite
composer require symfony/framework-bundle symfony/console symfony/http-client nyholm/psr7
```

`symfony/http-client` and `nyholm/psr7` provide the default PSR-18 client and the PSR-17 factories; you can use another
PSR-18 client (see `http_client`). Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle::class => ['all' => true],
];
```

Then configure it, for instance in `config/packages/urssaf_attestations_verification_code_securite.yaml`:

```yaml
urssaf_attestations_verification_code_securite:
    environment: '%env(URSSAF_ENV)%'            # sandbox | production
    credentials:
        tenant-1:
            client_id: '%env(URSSAF_CLIENT_ID)%'
            client_secret: '%env(URSSAF_CLIENT_SECRET)%'
```

## Configuration reference

The configuration tree is defined in `Bridge\Symfony\DependencyInjection\BundleConfiguration`.

| Key | Type | Default | Description |
|---|---|---|---|
| `environment` | enum `sandbox` \| `production` | required | Target platform. Usually `%env(URSSAF_ENV)%`: the value is resolved at runtime. |
| `base_uri` | string \| null | `null` | Overrides the base URI of the selected environment (the sandbox host may differ). |
| `domain` | enum `URSSAF` \| `ISU` \| null | `URSSAF` | Value of the `domaine` query parameter of the GET operation; `null` omits it ([ADR 0005](adr/0005-domaine-parameter.md)). See [Domain option](#domain-option). |
| `token_safety_margin` | int >= 0 | `60` | Seconds before expiry from which an access token is renewed. |
| `http_client` | string \| null | `null` | Service id of a PSR-18 client. Default: a dedicated Symfony HttpClient wrapped in `Psr18Client` (it also provides the PSR-17 factories, whatever the client in use). |
| `http_timeout` | float >= 0.1 | `15.0` | Timeout in seconds of the default HTTP client. |
| `credentials_provider` | string \| null | `null` | Service id of a `CredentialsProvider`. Mutually exclusive with `credentials`. |
| `credentials` | map tenant id => `{client_id, client_secret}` | `[]` | Static credentials by tenant (both keys required, non-empty). Use `%env(...)%` placeholders. |
| `tenants` | list of string | keys of `credentials` | Tenants checked by `urssaf:check` and defaulted by `urssaf:certificate:verify`. Set it with `credentials_provider`, which gives no tenant list. |
| `token_store.cache_pool` | string | `cache.app` | PSR-6 cache pool that stores the access tokens. Recommended: a **dedicated pool** (see below). |
| `token_store.service` | string \| null | `null` | Service id of a `TokenStore`; takes precedence over the cache pool. |
| `gateway.simulated` | bool | `false` | Tests only: replaces the URSSAF API with the in-memory `Testing\SimulatedCertificateVerificationGateway`, exposed as a public service to script the scenarios (see [testing.md](testing.md)). Refused with a literal `environment: production` (see below). |

Validation rules enforced at container build time:

- `credentials` and `credentials_provider` are mutually exclusive, and one of them is required.
- `environment` must be `sandbox` or `production` (an environment variable placeholder is resolved, and checked, at runtime).
- `gateway.simulated` cannot be used with a literal `environment: production`. An `%env()%` placeholder is not resolved when the container is compiled, so `environment: '%env(URSSAF_ENV)%'` is not checked: the option belongs in test-only configuration (a `when@test` block, as the recipe does, or `config/packages/test/`), never in a configuration that applies to production.
- `token_safety_margin` must not be negative, `http_timeout` must be at least `0.1`.
- `domain` must be `URSSAF`, `ISU` or `null`.

### Credentials per tenant

A tenant is an identifier of your application (at most 64 characters) that owns one pair of URSSAF OAuth2 credentials.
Every call is made on behalf of a tenant: `new VerifyCertificate(TenantId::fromString('tenant-1'), ...)`. The
`credentials` map holds one entry per tenant; the client secret is never logged and is masked in `var_dump()`.

When the credentials live elsewhere (a vault, a database), implement
`Application\Port\CredentialsProvider` (`forTenant(TenantId): Credentials`, throwing `Domain\Exception\RecordNotFound`
when the tenant is unknown) and give its service id:

```yaml
urssaf_attestations_verification_code_securite:
    environment: '%env(URSSAF_ENV)%'
    credentials_provider: App\Urssaf\VaultCredentialsProvider
    tenants: [tenant-1, tenant-2]     # no list can be derived from a provider
```

### Dedicated cache pool for the access tokens

The access tokens are stored in the `token_store.cache_pool` pool (`cache.app` by default). Declare a dedicated pool, so
that `cache:pool:clear cache.app` (a routine deployment step) does not force a new token request for every tenant, and
so that the tokens live in an adapter you control (Redis with authentication, for instance):

```yaml
framework:
    cache:
        pools:
            urssaf.token_cache:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_DSN)%'

urssaf_attestations_verification_code_securite:
    token_store: { cache_pool: urssaf.token_cache }
```

The pool must be shared by every process that calls URSSAF (web, workers, cron): a per-process `array` adapter would
request a token per run. Tokens are secrets: do not expose the pool through a cache dashboard or a shared namespace.
To store the tokens somewhere else, implement `Application\Port\TokenStore` and set `token_store.service`.

### Domain option

The PDF v1.2 of the API documents `domaine` (`URSSAF` or `ISU`) as mandatory on the GET operation while announcing its
removal, and the Swagger v1.0.1 no longer declares it. The bundle sends `domaine=URSSAF` by default; set
`domain: null` to omit it, or `domain: ISU` for the other value. It applies to the GET operation (one code) only, there
is no per-call override. See [ADR 0005](adr/0005-domaine-parameter.md).

## Services

The use case handlers are the only public services:

| Service | Use |
|---|---|
| `Application\Query\VerifyCertificateHandler` | Verifies one security code (`VerifyCertificate`). |
| `Application\Query\VerifyCertificatesHandler` | Verifies any number of security codes, in chunks the API accepts (`VerifyCertificates`). |

Inject them in your own services:

```php
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

final readonly class CertificateChecker
{
    public function __construct(private VerifyCertificateHandler $verifyCertificate) {}

    public function isValid(string $codeTypedByTheUser): bool
    {
        $result = ($this->verifyCertificate)(new VerifyCertificate(
            TenantId::fromString('tenant-1'),
            SecurityCode::fromString($codeTypedByTheUser),
        ));

        // A rejected code (expired, unknown...) is an answer of URSSAF, not an exception.
        return !$result instanceof RejectedCode;
    }
}
```

`VerifiedCertificate` and `RejectedCode` are described in [standalone.md](standalone.md). Failures of the platform
(authentication refused, unavailable, quota) are exceptions implementing `Domain\Exception\UrssafException`.

## Simulated gateway for tests

With `gateway.simulated: true` (for instance in `config/packages/test/`), the HTTP gateway, the HTTP client and the
OAuth2 flow are not registered: the handlers answer from
`Testing\SimulatedCertificateVerificationGateway`, a public service you script in your tests (`certify()`, `reject()`,
`failNext()`, `calls()`). Nothing is sent to URSSAF. See [testing.md](testing.md). The option is refused with
`environment: production`, and `urssaf:check` reports it as a warning.

## Console commands

Both commands are registered by the bundle. Their output never contains personal data (holder, NIR, names).

### Verifying certificates

```sh
bin/console urssaf:certificate:verify <code>... [--tenant=<id>] [--format=table|json]
```

| Argument / option | Description |
|---|---|
| `codes` | One or more security codes printed on the certificates (one code calls the GET operation, several call the POST operation). |
| `--tenant` | Tenant id. Default: the only configured tenant (`tenants`, else the keys of `credentials`); required when several or none are configured. |
| `--format` | `table` (default) or `json`. |

Output: with `table`, one row per code with the columns `Code`, `Result` (`valid` or `rejected`), `Nature`,
`Issuing body`, `Issued on` and `Fault` (`<code> - <name>`, for a rejected code). With `json`, a list of objects with the
keys `code`, `valid`, `nature`, `issuing_body`, `issued_on`, `fault_code` and `fault_name`.

Exit code: `0` when every code is valid; `1` when at least one code is rejected, when an option is invalid (unknown
format, unknown or missing tenant, empty code) or when URSSAF cannot be called (the message is printed).

### Checking the installation

`urssaf:check` prints one line per check and exits with `1` when one of them fails (warnings do not fail): run it after
each deployment, or in the CI of the application with `--skip-remote`.

```sh
bin/console urssaf:check [--tenant=<id>] [--skip-remote]
```

| Option | Description |
|---|---|
| `--tenant` | Tenant to check. Default: the configured tenants (`tenants`, else the keys of `credentials`). |
| `--skip-remote` | Does not call URSSAF (no authentication check). |

The report has the columns `Check`, `Subject`, `Status` (`ok`, `warning`, `failure`, `skipped`) and `Detail`, followed
by `The URSSAF integration is ready.` (exit `0`) or `N check(s) failed.` (exit `1`).

| Check | Failure / warning |
|---|---|
| `credentials` | failure: no credentials for a tenant in the configured provider |
| `authentication` | failure: URSSAF refuses the credentials or cannot be reached. A new access token is requested for every tenant (any cached one is ignored), the tokens of the application are not touched. Skipped with `--skip-remote`; replaced by a `gateway` warning when `gateway.simulated` is enabled |
| `token store` | failure: a probe token cannot be saved, read back and deleted in the token store (the probe has its own key and expires after 60 seconds). Warning: the tokens are in the shared `cache.app` pool (the default), which `cache:pool:clear cache.app` empties; declare a dedicated pool or a `token_store.service`. The probe is also written when `--skip-remote` is used |

Add checks of your own by implementing `Bridge\Symfony\Diagnostic\InstallationCheck` (autoconfigured, tag
`urssaf_attestations_verification_code_securite.installation_check`): they run after the package checks, unless their
tag has a `priority` of 1 or more, which puts them before the checks of the package.

## Translations

The violation codes (`<subject>.<reason>`, for example `tenant_id.empty`) are translatable messages of the translation
domain `urssaf_attestations_verification_code_securite`. The bundle ships English and French:
`Resources/translations/urssaf_attestations_verification_code_securite.en.yaml` and
`urssaf_attestations_verification_code_securite.fr.yaml` (same keys, kept in sync by the test suite).

To add another locale: create `translations/urssaf_attestations_verification_code_securite.<locale>.yaml` in your
application, with the same keys as the English file. To reword a shipped message, define the key in your application's
file of that locale:

```yaml
# translations/urssaf_attestations_verification_code_securite.fr.yaml
"credentials.not_found": "Ce tenant n'a pas d'identifiants URSSAF."
```

Keys defined in your application's `translations/` directory override the shipped messages. Translate the violation
code of a `Domain\Exception\DomainException` with
`$translator->trans($exception->violationCode(), $exception->params(), 'urssaf_attestations_verification_code_securite')`.
