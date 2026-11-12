# Without the Symfony bundle

`TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone\UrssafAttestationsVerificationBuilder` builds the
package from a PSR-18 transport, a `CredentialsProvider` and the `ApiConfiguration`. `build()` returns
`Standalone\UrssafAttestationsVerification`, whose methods return the two handlers (`verifyCertificate()`,
`verifyCertificates()`) and the gateway (`gateway()`).

## Install

```sh
composer require thebeardedbear/urssaf-attestations-verification-code-securite
```

The package needs a PSR-18 HTTP client and PSR-17 request and stream factories, none is imposed. For instance:

```sh
composer require symfony/http-client nyholm/psr7 psr/http-client
```

## Build the entry point

```php
use Symfony\Component\HttpClient\Psr18Client;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials\InMemoryCredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone\UrssafAttestationsVerificationBuilder;

$client = new Psr18Client();                       // implements ClientInterface and the PSR-17 factories
$urssaf = UrssafAttestationsVerificationBuilder::create(
    new HttpTransport($client, $client, $client),
    new InMemoryCredentialsProvider(['tenant-1' => Credentials::of($clientId, $clientSecret)]),
    ApiConfiguration::forEnvironment(Environment::Production),
)->build();
```

Every `with...()` method returns a new builder:

| Method | Default | Use |
|---|---|---|
| `withTokenStore(TokenStore)` | in-memory | A store shared by every process (`Psr6TokenStore`, `Psr16TokenStore`). |
| `withLogger(LoggerInterface)` | none | Logs of the HTTP gateway (requests, status, duration; never a secret or a token). |
| `withClock(Clock)` | UTC system clock | Token expiry and call durations; for instance `new FrozenClock(...)` in tests. |
| `withGateway(CertificateVerificationGateway)` | HTTP gateway | Replaces the HTTP gateway, for instance the `Testing\SimulatedCertificateVerificationGateway` ([testing.md](testing.md)). Nothing is sent to URSSAF then. |

## Verify one certificate

```php
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

$result = $urssaf->verifyCertificate()(new VerifyCertificate(
    TenantId::fromString('tenant-1'),
    SecurityCode::fromString($codeTypedByTheUser),
));

if ($result instanceof VerifiedCertificate) {
    // Valid: nature, issuing body, issue date and the details specific to the nature.
    $result->nature();            // ?CertificateNature, null when absent or unknown
    $result->issuingBody()->label();
    $result->details();
} elseif ($result instanceof RejectedCode) {
    // Functional rejection (an answer, not an error): the code is expired, unknown...
    $result->fault();             // ?FaultCode: Expired (FUNC201), Unknown (FUNC202), MissingSecurityData (FUNC203)
    $result->faultName();         // wording sent by URSSAF
}
```

`$result->isValid()` is the shortcut when only the verdict matters. A rejected code is **not** an exception: the API
answered, the certificate is not valid.

## Verify many certificates

```php
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificates;

$results = $urssaf->verifyCertificates()(new VerifyCertificates(
    TenantId::fromString('tenant-1'),
    ...array_map(SecurityCode::fromString(...), $codes),
));
```

The handler splits the codes into calls of at most 100 (the API limit), runs them one after the other and returns the
results in the order of the codes, one per code. Duplicates are sent as given; no code means no call. A failure of a
call aborts the whole verification: the exception is thrown and the results of the previous calls are lost.

## Error handling

Every error of the package implements `Domain\Exception\UrssafException`.

| Exception | When | Retry |
|---|---|---|
| `UrssafUnavailable` (a `RetryableException`) | HTTP 429, 5xx or transport error, on the token or the API call | Yes, later. `retryAfterSeconds()` is the `Retry-After` header when provided (`null` otherwise); `isQuotaExceeded()` is true on 429; `mayHaveReachedUrssaf()` tells whether URSSAF may have processed the call. |
| `UrssafAuthenticationFailed` | The client credentials are refused | No: fix the credentials. |
| `UrssafRequestRejected` | URSSAF refused the whole call (HTTP 400) | No as is: fix the request. |

```php
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafUnavailable;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\RetryableException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\UrssafException;

try {
    $result = $urssaf->verifyCertificate()($query);
} catch (UrssafUnavailable $e) {
    $retryIn = $e->retryAfterSeconds() ?? 60;   // wait, then try again
} catch (RetryableException $e) {
    // any other transient failure
} catch (UrssafException $e) {
    // not retryable: log it and report the failure
}
```

URSSAF publishes no quota value for this API, so the package has no rate limiter: honour `retryAfterSeconds()`.

## Token store

The OAuth2 access token is cached per client, environment and host. The default store lives in the memory of the
process: in a PHP-FPM application every request would fetch a new token. Share it between the processes with a PSR-6 or
PSR-16 cache (recommended):

```php
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\TokenStore\Psr6TokenStore;

$builder = $builder->withTokenStore(new Psr6TokenStore($cacheItemPool));
// PSR-16: new Psr16TokenStore($simpleCache, $clock)
```

The entry lasts as long as the token is valid; the cache key is a hash that never contains the secret.

## Several tenants

`CredentialsProvider::forTenant(TenantId)` is called at every request, so each tenant can use its own URSSAF client:

```php
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\RecordNotFound;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

final readonly class DatabaseCredentialsProvider implements CredentialsProvider
{
    public function __construct(private TenantCredentialsRepository $repository) {}

    public function forTenant(TenantId $tenantId): Credentials
    {
        $row = $this->repository->find($tenantId->value())
            ?? throw RecordNotFound::withIdentifier('credentials', $tenantId->value());

        return Credentials::of($row->clientId, $this->decrypt($row->clientSecret));
    }
}
```

Tokens are cached per client id, so two tenants sharing a client share a token. The package never stores or logs the
secret.

## Manual wiring

The builder only calls public constructors: wire the classes yourself when you need another combination.

```php
$gateway = UrssafGatewayFactory::createWith($transport, $credentials, $tokenStore, $configuration, GatewayOptions::defaults()->withClock($clock));
$verifyCertificate = new VerifyCertificateHandler($gateway);
$verifyCertificates = new VerifyCertificatesHandler($gateway);
```
