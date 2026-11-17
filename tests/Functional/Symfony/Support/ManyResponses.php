<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support;

/**
 * Responses of the verification of several codes (POST), built from the official items.
 */
final class ManyResponses
{
    private const VALID_ITEM = <<<'JSON'
        {
            "codeSecurite": "42TROLLQU3DT999",
            "infosSecurisation": {
                "hashKey": "42TROLLQU3DT999",
                "nature": "CSG_RSI",
                "denomination": "Cooper Sheldon",
                "codeOrganismeEmetteur": "837",
                "libelleOrganismeEmetteur": "Urssaf AUVERGNE",
                "date": "02/10/2020",
                "infosSecurisationSpecifiques": []
            }
        }
        JSON;

    private const EXPIRED_ITEM = <<<'JSON'
        {
            "codeSecurite": "4W6UKDR41G457CW",
            "functionalFaultCodeSecurite": {
                "faultName": "Attestation perimée depuis le 01/03/2019",
                "faultCode": "FUNC201"
            }
        }
        JSON;

    private function __construct() {}

    public static function validThenExpired(): string
    {
        return self::wrap(self::VALID_ITEM . ',' . self::EXPIRED_ITEM);
    }

    public static function twoValid(): string
    {
        return self::wrap(self::VALID_ITEM . ',' . self::VALID_ITEM);
    }

    private static function wrap(string $items): string
    {
        return '{"verifierCodeSecuriteAttestationGen": [' . $items . ']}';
    }
}
