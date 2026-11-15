# ADR 0009 — Symfony Flex recipe on symfony/recipes-contrib

- Status: accepted
- Supersedes: [ADR 0008](0008-symfony-flex-recipe.md) (private endpoint part)

## Context

ADR 0008 published the recipe through a private Flex endpoint because the package was proprietary. The package is now
open source under the MIT license and on Packagist, so nothing prevents the public `symfony/recipes-contrib`
repository. The private endpoint was never hosted: the `FLEX_ENDPOINT_URL` variable was never set, and the CI artifact
pointed at a placeholder URL.

## Decision

- The recipe is submitted to `symfony/recipes-contrib`
  ([#2120](https://github.com/symfony/recipes-contrib/pull/2120)), at
  `thebeardedbear/urssaf-attestations-verification-code-securite/0.1/`, identical to `recipe/` in this repository.
- `recipe/` stays the source of the recipe; the tests that check it against the bundle (manifest, the copied
  configuration boots the bundle) are kept. A change of the recipe adds a version directory here and is submitted
  upstream in the same form.
- `bin/build-flex-endpoint`, its tests and the CI job `flex-endpoint` are removed, along with the `symfony/process`
  development dependency they alone used.
- The bundle fixes recorded by ADR 0008 (runtime `%env()%` environment, optional `symfony/clock`) still apply.

## Consequences

- Host applications run `composer config extra.symfony.allow-contrib true` once; `composer require` then registers and
  configures the bundle. Until the upstream pull request is merged, the manual installation applies.
- Upstream review (Symfony bot, then a community reviewer and a core merger) is outside the control of the project.
