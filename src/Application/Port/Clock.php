<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
