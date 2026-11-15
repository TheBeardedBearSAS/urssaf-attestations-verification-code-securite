# URSSAF "API Attestations Vérification Code Sécurité" — technical reference

This document is the single source of truth used by the package for enums, validation rules and wire fixtures.
It summarises the official specification:

- Swagger contract: **v1.0.1** (`docs/openapi/avcs-prd.json`, production only)
- Technical documentation PDF: **v1.2** (2020-11-03), file name `…_v1.2.1.pdf`
  (download it into `docs/reference/`, a local git-ignored folder: the official documents are not versioned)
- Catalogue page: https://portailapi.urssaf.fr/fr/catalogue-api/prd/avcs
- PDF: https://cedito-medias.urssaf.fr/medias/PortailAPI/files/documentationAPIs/AttestationSecurite/ACOSS-Documentation-API-Attestations-verificationcodesecurite_v1.2.1.pdf

The package constant `SpecVersion::CURRENT` must equal `1.2.1`. Rule shared by the URSSAF packages: `SpecVersion` is
the version in the **file name of the published PDF** (the most detailed source), falling back to the Swagger version
when the PDF is not versioned. The PDF revision history stops at 1.2. Field names, codes and wire values below are reproduced
verbatim from the specification (they are French by design); everything else in the package is English.

> **Source and rights.** The specification, the field names, the codes and the official JSON examples quoted here come
> from the URSSAF documents listed above and remain URSSAF's property. They are reproduced only so the package can
> interoperate with the API, and they are not covered by this package's MIT license. In case of discrepancy, the
> official URSSAF documentation prevails. This package is not affiliated with URSSAF.

> Status: implemented (see the [CHANGELOG](../CHANGELOG.md)); decisions in §4 and [docs/adr](adr/).

---

## 1. General

| Topic | Value |
|---|---|
| Purpose | Check the validity of a certificate (*attestation*) issued by URSSAF from the security code (*code de sécurité*) printed on it. |
| Access | Catalogue: "accès public", subscription form; PDF §2.1: restricted REST offer, access validated by ACOSS. Credentials (client id / client secret) issued after approval. |
| Security | OAuth2 **client credentials** |
| Token endpoint | `POST https://api.urssaf.fr/api/oauth/v1/token` (form-urlencoded: `grant_type=client_credentials`, `client_id`, `client_secret`, `scope`) |
| Scope | `attn.codes_securite` |
| Production base URL | `https://api.urssaf.fr/attn/verification/v1` |
| Sandbox | Not listed in the catalogue (production only). The portal states that a "sandbox" access is granted first: the host (`https://api-edi.urssaf.fr` by analogy with the other URSSAF APIs) must stay configurable. |
| Content type | `application/json` (request and response) |
| HTTP errors | 200 (with per-code functional fault), 400 (functional / security), 401, 403, 429 (quota), 500, 512 (security) |

### 1.1 Quotas (HTTP 429 when exceeded)

Two levels (PDF §2.5): a global quota per API and a quota per API client. Values are **not published**.

### 1.2 Known limitations (official)

- PDF §1.2 and §3.1.2: the `domaine` query parameter (`URSSAF` or `ISU`) is documented as **mandatory but not
  needed**, to be removed in a later delivery. The Swagger v1.0.1 no longer declares it (see §5).
- POST: at most **100** security codes per call.
- A planned change will rename the "more than 100 codes" error from `FUNC013` to `FUNC016` (PDF §3.2, footnote 2).

### 1.3 Error payload

Request-level errors (HTTP 400 / 500) are a list (or a single object for technical errors):

```json
[ { "code": "FUNC013", "message": "Echec de l'appel au service demandé", "description": "Echec de l'appel au service : GestionAttestationsService (operation VerifierCodeSecuriteGenAttestation)" } ]
```

Technical error (500): `{"code":"ERR_TECHNIQUE","message":"Erreur technique","description":"Une erreur système est survenue"}`.

Per-code functional faults are returned with **HTTP 200** inside the result item (`functionalFaultCodeSecurite`), see §2.

---

## 2. Operations

### 2.1 Verify one certificate — `GET /codes_securite/verifier`

`operationId`: `verifierCodeSecuriteAttestationGen`.

Query parameters:

| Field | Type | Req. | Rules |
|---|---|---|---|
| `codeSecurite` | string | yes | Security code printed on the certificate, e.g. `MWLJU8HKNPLF5X9`. Format not specified; *inferred from the examples*: 15 upper-case alphanumeric chars |
| `domaine` | string | PDF: yes / Swagger: absent | `URSSAF` or `ISU` (`ISU` for self-employed craftsmen / traders, `URSSAF` otherwise). See §1.2 |

Example: `GET https://api.urssaf.fr/attn/verification/v1/codes_securite/verifier?domaine=URSSAF&codeSecurite=2PTROKKQU3DTLBM`

Response 200: `VerifierCodeSecuriteAttestationGenResultatDto`

| Field | Type | Rules |
|---|---|---|
| `verifierCodeSecuriteAttestationGen` | array of `VerifierCodeSecuriteAttestationGenDto` | one item per requested code |

`VerifierCodeSecuriteAttestationGenDto`:

| Field | Type | Rules |
|---|---|---|
| `codeSecurite` | string | the requested code |
| `infosSecurisation` | `InfosSecurisationDto` | present when the certificate is valid |
| `functionalFaultCodeSecurite` | `FunctionalFaultCodeSecuriteDto` | present when the code is unknown / expired / incomplete: `{faultName, faultCode}` |

`InfosSecurisationDto`:

| Field | Type | Rules |
|---|---|---|
| `hashKey` | string | equals the security code in the official example |
| `nature` | string | certificate nature, see §3.1 |
| `denomination` | string | holder name (company or person) |
| `codeOrganismeEmetteur` | string | issuing body code, e.g. `837` |
| `libelleOrganismeEmetteur` | string | issuing body label, e.g. `Urssaf AUVERGNE` |
| `date` | string | issue date, **`DD/MM/YYYY`** (e.g. `02/10/2020`) |
| `infosSecurisationSpecifiques` | array of `InfosSecurisationSpecifiquesDto` | nature-specific data; each item holds exactly one of the keys below |

`InfosSecurisationSpecifiquesDto` keys and payloads:

| Key | Payload fields |
|---|---|
| `infosSecurisationSpecifiquesAMP` | `siren`, `nir`, `etablissement[]` |
| `infosSecurisationSpecifiquesAVG` | `siren`, `nir`, `etablissement[]`, `dernierePeriodeAnalysee`, `masseSalariale`, `effectif` (all strings; AVG = AMP + 3 fields) |
| `infosSecurisationSpecifiquesCSG` | `numExt`, `etablissement[]` |
| `infosSecurisationSpecifiquesCFP` | same as CSG |
| `infosSecurisationSpecifiquesEPM` | `etablissement[]` |
| `infosSecurisationSpecifiquesFRS` | `etablissement[]` |
| `infosSecurisationSpecifiquesCAAE` | `etablissement[]` |
| `infosSecurisationSpecifiquesASAA` | `nomArtisteAuteur`, `prenomArtisteAuteur`, `dateDebutDeclaration` (date-time), `dateFinDeclaration` (date-time) |

`EtablissementForCodeSecuDto`: `adresse` (string, full postal address on one field), `siret` (string; 14 digits
*inferred from the example*, not specified).

Errors: see §3.2. Fixtures: `tests/Fixtures/wire/verify_response_valid.json`, `verify_response_unknown.json`,
`verify_response_expired.json`, `verify_response_missing_data.json`, `error_func013.json`, `error_technical.json`.

### 2.2 Verify several certificates — `POST /codes_securite/verifier`

`operationId`: `verifierListeCodesSecuriteAttestationGen`. Same service as §2.1 for up to 100 codes.

Body: `VerifierCodesSecuriteAttestationGenRequeteDto`

| Field | Type | Req. | Rules |
|---|---|---|---|
| `listeCodesSecurite` | array of string | yes | 1–100 security codes (`FUNC015` when empty, `FUNC013` when more than 100) |

Response 200: same `VerifierCodeSecuriteAttestationGenResultatDto` as §2.1, one item per code; each item may carry
its own `functionalFaultCodeSecurite`.

Specific errors (HTTP 400): `FUNC015` "Le code de sécurité est obligatoire", `FUNC013` "le nombre de code securite
pour un appel est limité à 100" (to become `FUNC016`). Fixtures: `verify_many_request.json`, `error_func015.json`,
`error_too_many_codes.json`.

---

## 3. Reference values

### 3.1 Certificate natures (`nature`)

| Code | Label (indicative; "ISU"/"RSI" now means craftsmen / traders) |
|---|---|
| `AVG_UR` | Attestation de Vigilance Urssaf |
| `AVG_RSI` | Attestation de Vigilance ISU |
| `CSG_UR` | Attestation de part déductible csg/crds Urssaf |
| `CSG_RSI` | Attestation de part déductible csg/crds ISU |
| `CFP_UR` | Attestation de contribution à la Formation Professionnelle Urssaf |
| `CFP_RSI` | Attestation de contribution à la Formation Professionnelle ISU |
| `EPM_UR` | Attestation de contribution Employeur de personnel de maison Urssaf |
| `FRS_UR` | Attestation de frontalier suisse |
| `CAA_UR` | Attestation annuelle de chiffre d'affaires pour les micro-entrepreneurs Urssaf |
| `CAA_RSI` | Attestation annuelle de chiffre d'affaires pour les micro-entrepreneurs ISU |
| `CAP_UR` | Attestation périodique de chiffre d'affaires pour les micro-entrepreneurs Urssaf |
| `CAP_RSI` | Attestation périodique de chiffre d'affaires pour les micro-entrepreneurs ISU |
| `ASAA_UR` | Attestation AGESSA (artistes – auteurs) |

### 3.2 Error codes

| Code | HTTP | Where | Meaning |
|---|---|---|---|
| `FUNC201` | 200 | item `functionalFaultCodeSecurite` | Certificate expired (`faultName` e.g. "Attestation perimée depuis le 01/03/2019") |
| `FUNC202` | 200 | item | Unknown certificate ("Attestation inconnue") |
| `FUNC203` | 200 | item | Missing security data ("Donnée de sécurisation manquante") |
| `FUNC013` | 400 | request | Service call failure; also "more than 100 codes" on POST (to become `FUNC016`) |
| `FUNC015` | 400 | request (POST) | Security code list is mandatory |
| `ERR_TECHNIQUE` | 500 | request | Technical error |
| — | 400, 401, 403, 512 | request | Security errors (token missing/invalid, forbidden) |

### 3.3 `domaine`

`URSSAF`, `ISU` (see §1.2).

---

## 4. Package decisions derived from the specification

Architecture decisions are recorded in [docs/adr/](adr/) (ADR 0000 summarises those inherited from
`urssaf-tiers-prestations`).

1. **Verification result is a sum type** ([ADR 0002](adr/0002-verification-result-model.md)). Each item maps to either a
   *verified certificate* (`infosSecurisation`) or a *rejected code* (`FUNC201`/`FUNC202`/`FUNC203`, unknown fault codes
   preserved raw). An item must carry exactly one of the two; anything else is an unexpected response. An HTTP 200
   never means "valid" on its own.
2. **Batching** ([ADR 0003](adr/0003-batching.md)). `verifyMany()` sends 1–100 codes per call (never an empty or
   oversized list); the handler chunks inputs by 100 and preserves the input order; duplicates are sent as given (the
   official example contains a duplicate). An empty input returns no result without calling URSSAF.
3. **Dates.** `date` is parsed strictly as `DD/MM/YYYY` into a calendar date (the `Europe/Paris` zone only matters when a
   caller turns it into an instant); ASAA dates as RFC 3339 instants.
4. **`domaine`** ([ADR 0005](adr/0005-domaine-parameter.md)). Sent on the GET operation, configurable (`URSSAF`
   default, `ISU`, or `null` to omit it), until a contract test against the sandbox proves it can be dropped.
5. **Nature-specific data.** Modelled as one value object per key (AMP, AVG, CSG, CFP, EPM, FRS, CAAE, ASAA); unknown
   keys are ignored and their name is logged. Values whose format is not specified stay raw strings.
6. **Personal data.** `nir`, `denomination`, `nomArtisteAuteur`/`prenomArtisteAuteur` are personal data: never logged and
   masked in `__debugInfo()`.
7. **Quotas** ([ADR 0004](adr/0004-quotas-and-retry.md)). No client-side rate limiter (values not published); HTTP 429 is
   a retryable failure carrying `Retry-After`. No automatic retry inside the package.
8. **Errors** ([ADR 0006](adr/0006-error-codes.md)). Request-level codes `FUNC013`, `FUNC015`, `FUNC016` (announced),
   `ERR_TECHNIQUE`; unknown codes kept raw. Optional fields: only `codeSecurite` is required on a result item.

---

## 5. Open points

- `domaine`: required by the PDF v1.2, absent from the Swagger v1.0.1. To be confirmed on the sandbox. Contract test ready (`SandboxContractTest`, GET with and without `domaine`); closed only once the whole suite passes
  with `URSSAF_SANDBOX_VALID_SECURITY_CODE` set.
- Sandbox host and availability (catalogue lists production only).
- Quota values (not published).
- Nature `CAP_*` has no dedicated `infosSecurisationSpecifiques*` key; `CAAE` key vs `CAA_*` natures mapping to
  confirm.
- Error payload for security errors (400/401/403/512) not documented.
- Formats of `codeSecurite`, `siret`, `siren`, `nir`, `numExt` are not specified (only examples): local validation
  stays minimal (non-empty) until confirmed.
- Swagger v1.0.1 declares `InfosSecurisationSpecifiquesAVGDto` and `InfosSecurisationSpecifiquesCFPDto` without any
  property; the PDF lists their fields (AVG = AMP + `dernierePeriodeAnalysee`, `masseSalariale`, `effectif`; CFP =
  `numExt`, `etablissement`). The package follows the PDF.
- Swagger v1.0.1 declares only HTTP 400 and 401; the PDF adds 403, 429, 500 and 512.
- The PDF POST example targets `https://api.c1.giin.urssaf.fr` (an internal host?) instead of `api.urssaf.fr`.
- No official example exists for AMP, AVG, CFP, EPM, FRS, CAAE and ASAA payloads: their tests use synthetic payloads
  built from the field lists above.
