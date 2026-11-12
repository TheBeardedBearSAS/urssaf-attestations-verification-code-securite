# ADR 0002 — Verification result model

- Status: accepted
- Spec: api-reference §2.1, §3.1, §3.2, §4.1, §4.3, §4.5, §4.6

## Context

An HTTP 200 carries, per requested code, either the certificate data (`infosSecurisation`) or a functional fault
(`functionalFaultCodeSecurite`, `FUNC201`/`FUNC202`/`FUNC203`). Neither the Swagger nor the PDF marks any field as
required, and the formats of the identifiers are only illustrated by examples. Natures and fault codes may evolve.

## Decision

- `Domain\Certificate\VerificationResult` is a sum type (interface) with two `final readonly` variants:
  `VerifiedCertificate` and `RejectedCode`. Both expose `securityCode(): SecurityCode` and `isValid(): bool`.
- Each wire item must carry **exactly one** of `infosSecurisation` / `functionalFaultCodeSecurite`. Neither or both is
  an `UnexpectedUrssafResponse`: an HTTP 200 never means "valid" on its own, and the package does not guess.
- `codeSecurite` is required on each item (it identifies the result). Every other scalar field is optional
  (`?string`): only structure and JSON types are checked, never formats that the specification does not define.
- `nature` and `faultCode` are kept raw, next to a nullable typed view: `natureCode(): ?string` +
  `nature(): ?CertificateNature`, `faultCode(): ?string` + `fault(): ?FaultCode`.
- `date` is parsed strictly as `d/m/Y` (round-trip checked) into a `CalendarDate`; an invalid value is an
  `UnexpectedUrssafResponse`. §4.3 names `Europe/Paris`: a calendar date has no time zone, so the zone only matters if
  a caller converts it to an instant; this is documented, not encoded.
- ASAA `dateDebutDeclaration`/`dateFinDeclaration` (Swagger `date-time`) are parsed as RFC 3339 into `Instant`.
- `infosSecurisationSpecifiques` maps to one value object per key (`Amp`, `Avg`, `Csg`, `Cfp`, `Epm`, `Frs`, `Caae`,
  `AsaaDetails`, interface `CertificateDetails`) and `Establishment(?siret, ?address)`. AVG figures
  (`dernierePeriodeAnalysee`, `masseSalariale`, `effectif`) stay raw strings: their format is not specified. An unknown
  key is ignored and only its name is logged at warning level.
- Personal data (`nir`, `denomination`, `nomArtisteAuteur`, `prenomArtisteAuteur`) is masked in `__debugInfo()` and
  never logged.

## Consequences

- Callers branch on the variant (`instanceof` or `isValid()`) and always see the fault code, even an unknown one.
- A stricter validation (SIRET, SIREN, NIR, security code format) can be added once confirmed (api-reference §5)
  without changing the shape of the model.
