# ADR 0007 — Static analysis on the minimum supported PHP version

- Status: accepted
- Departure from the reference: its Makefile runs PHPStan on the PHP version passed as `PHP_VERSION`.

## Context

The package supports PHP 8.2 and later. PHPStan includes `tomasvotruba/type-coverage` with `constant: 100`. That rule
reads the PHP version running PHPStan (`PHP_VERSION_ID`), not the `phpVersion` analysed: on PHP 8.2 it is skipped, but
on PHP 8.3+ it requires typed class constants (`public const string X`). That syntax is a parse error on PHP 8.2, so the
rule cannot be satisfied while 8.2 stays supported. `make ci PHP_VERSION=8.4` therefore failed with 93
`typeCoverage.constantTypeCoverage` errors, although nothing was wrong with the code. The reference package has the same
configuration; its CI runs PHPStan on PHP 8.2 only.

## Decision

- The Makefile `phpstan` target always runs in the PHP 8.2 image (`PHP_ANALYSIS`), whatever `PHP_VERSION` is, like the
  CI `quality` jobs.
- Every other step of `make ci PHP_VERSION=x` (code style, Rector, Mago, Deptrac, composer checks, tests, coverage,
  Infection) still runs on the requested version.
- No PHPStan threshold or rule is changed.

## Consequences

- `make ci PHP_VERSION=8.4` checks the runtime behaviour on 8.4 and the static analysis on the supported floor.
- When PHP 8.2 support is dropped, the constants get native types and this pin is removed.
