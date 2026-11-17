# Roadmap

Intended direction for the next twelve months (November 2026 to November 2027). It is a statement of intent, not a
commitment; it is reviewed at each minor release. Proposals are welcome in GitHub issues. The delivery history of the
implementation is in [docs/roadmap.md](docs/roadmap.md).

## Planned

- **Follow the URSSAF API.** Track each new version of the URSSAF *API Attestations Vérification Code Sécurité*
  (swagger and technical documentation), starting with the open points of [docs/api-reference.md](docs/api-reference.md)
  section 5 once they can be checked against the sandbox, and release the matching changes, with upgrade notes in the
  [changelog](CHANGELOG.md).
- **Release 1.0.** Stabilise the public API (domain model, gateway port, standalone builder, Symfony bundle), then
  release `1.0.0`. From 1.0 on, breaking changes only happen in major versions, as required by
  [Semantic Versioning](https://semver.org/).
- **Keep up with the platform.** Support new PHP versions (8.5 and later) and new Symfony versions as they are
  released, and drop the versions that reach their end of life in a minor release, announced in the changelog.
- **Keep the quality and security bar.** 100 % line coverage, at least 96 % branch coverage and 100 % MSI, PHPStan at
  level max, signed releases, and the OpenSSF Best Practices criteria.

## Not planned

- Other URSSAF APIs: each one gets its own package (see *Other URSSAF packages* in the [README](README.md)).
- Official bridges for other frameworks than Symfony: the core works without a framework (see
  [docs/standalone.md](docs/standalone.md)).
- A client-side rate limiter or persistence of verification results: the package is stateless by design (see
  [ADR 0001](docs/adr/0001-stateless-sdk.md) and [ADR 0004](docs/adr/0004-quotas-and-retry.md)); a new ADR would be
  needed first.
