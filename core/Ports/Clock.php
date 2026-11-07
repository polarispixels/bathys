<?php

declare(strict_types=1);

namespace Bathys\Ports;

/**
 * A port for the current time. The core asks this instead of calling the
 * system clock, so a test can freeze time and check an exact result.
 */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
