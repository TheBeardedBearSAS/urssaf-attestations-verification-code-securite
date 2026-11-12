# ADR 0008 — Symfony Flex recipe and private endpoint

- Status: accepted
- Reference: `urssaf-tiers-prestations` commit `6f6e290` ("Symfony Flex recipe and private endpoint generator", since
  squash-merged into `1aab404` on its `main`).

## Context

The package is proprietary, so its recipe cannot go to the public `symfony/recipes-contrib` repository. The reference
package ships the recipe in `recipe/` and turns it into a private Flex endpoint with `bin/build-flex-endpoint`, built
as a CI artifact. Validating the reference recipe on a fresh Symfony skeleton revealed two bundle bugs:
`environment: '%env(URSSAF_ENV)%'` broke the container (the value was used at compile time), and the bundle needed
`symfony/clock` without declaring it.

## Decision

- `recipe/thebeardedbear/urssaf-attestations-verification-code-securite/0.1/` holds a `manifest.json` (registers the
  bundle for all environments, copies `config/`, adds `URSSAF_ENV`, `URSSAF_CLIENT_ID`, `URSSAF_CLIENT_SECRET` to
  `.env`) and a commented `config/packages/urssaf_attestations_verification_code_securite.yaml`. That file uses the env
  variables, documents the token cache pool and `domain`, and enables the simulated gateway under `when@test`.
- `bin/build-flex-endpoint <output-dir> <base-url>` is reused from the reference unchanged; the CI job `flex-endpoint`
  publishes its output as an artifact (base URL from the `FLEX_ENDPOINT_URL` repository variable).
- The bundle must accept an `%env()%` placeholder for `environment` (resolved at runtime) and work without
  `symfony/clock` (falls back to the package UTC clock), as the reference does after its fix; functional tests cover
  both, plus a recipe test (manifest, copied configuration loads in the test kernel).

## Consequences

- Host applications add the private endpoint to `extra.symfony.endpoint` in their `composer.json` and get the bundle
  configured by `composer require`.
- Since an `%env()%` environment is only known at runtime, the "simulated gateway refused in production" rule still
  only applies to a literal value (see docs/symfony.md).
