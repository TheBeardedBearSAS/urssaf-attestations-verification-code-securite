<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate;

/**
 * Certificate nature (wire "nature"). Indicative French labels:
 * AVG_UR "Attestation de Vigilance Urssaf", AVG_RSI "Attestation de Vigilance ISU",
 * CSG_UR / CSG_RSI "Attestation de part déductible csg/crds" (Urssaf / ISU),
 * CFP_UR / CFP_RSI "Attestation de contribution à la Formation Professionnelle" (Urssaf / ISU),
 * EPM_UR "Attestation de contribution Employeur de personnel de maison Urssaf",
 * FRS_UR "Attestation de frontalier suisse",
 * CAA_UR / CAA_RSI "Attestation annuelle de chiffre d'affaires pour les micro-entrepreneurs" (Urssaf / ISU),
 * CAP_UR / CAP_RSI "Attestation périodique de chiffre d'affaires pour les micro-entrepreneurs" (Urssaf / ISU),
 * ASAA_UR "Attestation AGESSA (artistes – auteurs)".
 */
enum CertificateNature: string
{
    case VigilanceUrssaf = 'AVG_UR';
    case VigilanceIsu = 'AVG_RSI';
    case CsgCrdsUrssaf = 'CSG_UR';
    case CsgCrdsIsu = 'CSG_RSI';
    case ProfessionalTrainingUrssaf = 'CFP_UR';
    case ProfessionalTrainingIsu = 'CFP_RSI';
    case HouseholdEmployerUrssaf = 'EPM_UR';
    case SwissCrossBorderWorker = 'FRS_UR';
    case AnnualTurnoverUrssaf = 'CAA_UR';
    case AnnualTurnoverIsu = 'CAA_RSI';
    case PeriodicTurnoverUrssaf = 'CAP_UR';
    case PeriodicTurnoverIsu = 'CAP_RSI';
    case ArtistAuthor = 'ASAA_UR';
}
