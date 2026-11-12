# URSSAF "API Attestations Vérification Code Sécurité" for PHP

`thebeardedbear/urssaf-attestations-verification-code-securite` — client, domain model and Symfony integration for the
URSSAF API that checks the validity of a certificate (*attestation de vigilance*, CSG/CRDS, formation
professionnelle, micro-entrepreneur turnover, …) from the security code printed on it.

Namespace: `TheBeardedBear\UrssafAttestationsVerificationCodeSecurite`.

> 🚧 **Work in progress.** Implemented so far: domain model.
> The implementation follows [docs/roadmap.md](docs/roadmap.md).

## Scope

| Operation | Endpoint |
|---|---|
| Verify one certificate | `GET /codes_securite/verifier?codeSecurite=…` |
| Verify up to 100 certificates | `POST /codes_securite/verifier` |

OAuth2 client credentials, scope `attn.codes_securite`, base URL `https://api.urssaf.fr/attn/verification/v1`.

## Documentation

- [docs/api-reference.md](docs/api-reference.md) — technical reference (single source of truth for the package).
- [docs/roadmap.md](docs/roadmap.md) — planned architecture and order of work.
- [docs/adr/](docs/adr/) — architecture decision records (0000: decisions inherited from the reference package).
- [docs/openapi/](docs/openapi/) — official OpenAPI contract(s) from the URSSAF API portal.
- [docs/reference/](docs/reference/) — official URSSAF documentation.
- Catalogue page: https://portailapi.urssaf.fr/fr/catalogue-api/prd/avcs

## Requirements

PHP 8.2+. Development only needs Docker: every `make` target runs in a container.

## Development

```sh
make install     # build the image, install dependencies
make ci          # full quality gate: cs, rector, phpstan, mago, deptrac, composer checks, guard, coverage, infection
make test        # unit + functional tests
make help        # every target
```

`make ci PHP_VERSION=8.4` runs the gate on another PHP version.

## Versioning

Semantic Versioning. `SpecVersion::CURRENT` holds the version of the official URSSAF documentation implemented.

## License

Proprietary — see [LICENSE](LICENSE).
