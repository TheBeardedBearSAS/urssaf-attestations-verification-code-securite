# Testing a host application

The package ships `TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway`,
an in-memory URSSAF certificate verification API: it implements the gateway port
(`Application\Port\Urssaf\CertificateVerificationGateway`) and answers `verify()` and `verifyMany()` from the results you
script, without any network call. Use it to test your integration end to end; keep the sandbox for the contract tests.

It must never be used outside tests: nothing it answers comes from URSSAF.

## Default behaviour

A security code that was not scripted is rejected as unknown: a `RejectedCode` with the fault `FUNC202` /
"Attestation inconnue". Codes are matched by value (after the normalisation of `SecurityCode`). `verifyMany()` accepts
1 to 100 codes like the real gateway and answers in the order of the codes.

## Scripting

```php
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafUnavailable;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway;

$urssaf = new SimulatedCertificateVerificationGateway();

$urssaf->certify($verifiedCertificate);                                          // this code is now a valid certificate
$urssaf->reject(SecurityCode::fromString('OLD'), 'FUNC201', 'Attestation perimée depuis le 01/03/2019'); // this code is now rejected
$urssaf->failNext(Operation::VerifyCertificate, UrssafUnavailable::withStatus(Operation::VerifyCertificate, 503, 30)); // the next verify() throws
```

The last scripting of a code wins. `failNext()` queues a failure for one operation (`Operation::VerifyCertificate` for `verify()`,
`Operation::VerifyCertificates` for `verifyMany()`); failures queued for the same operation are thrown in order, one per
call, and the failed call is still recorded.

Inspection: `calls()` returns every call received, in order, as `['operation' => Operation, 'tenantId' => TenantId,
'securityCodes' => list<SecurityCode>]`; `verify()` records `Operation::VerifyCertificate` and `verifyMany()` records
`Operation::VerifyCertificates`.

## Without Symfony

Give the simulator to the builder: the HTTP gateway is not built and no request is sent.

```php
$urssaf = new SimulatedCertificateVerificationGateway();
$urssaf->certify($verifiedCertificate);

$package = UrssafAttestationsVerificationBuilder::create($transport, $credentials, $configuration)
    ->withGateway($urssaf)
    ->build();

$result = $package->verifyCertificate()(new VerifyCertificate($tenant, $verifiedCertificate->securityCode()));
self::assertSame($verifiedCertificate, $result);
self::assertCount(1, $urssaf->calls());
```

The builder still needs a transport, credentials and configuration (they are not used): any PSR-18 client works. See
[standalone.md](standalone.md).
