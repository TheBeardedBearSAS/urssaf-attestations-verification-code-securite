# ADR 0001 — Stateless SDK

- Status: accepted

## Context

The reference package orchestrates a long-running workflow: local records, state machines, atomic claims, a
transactional outbox, integration events, polling and reconciliation, persisted through Doctrine and driven by
Messenger, Lock and Scheduler. This API only *reads*: one call verifies one or more security codes and returns their
status. Nothing is created on the URSSAF side, and nothing has to be followed up over time.

## Decision

- No aggregate, repository, unit of work, transaction manager, domain event, outbox or integration event.
- No Doctrine bridge, no Messenger messages, no Lock, no Scheduler schedule.
- The application layer exposes two queries (`VerifyCertificate`, `VerifyCertificates`) and their handlers, which call
  the gateway port and return domain values.
- A caller that needs an audit trail stores the returned results itself.

## Consequences

- The package is small; the only shared state is the OAuth2 token cache.
- The Deptrac `DoctrineBridge` layer and the Doctrine dev dependencies stay, so a later optional verification log can
  be added without changing the tooling. Doing so needs a new ADR.
