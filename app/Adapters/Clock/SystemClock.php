<?php

declare(strict_types=1);

namespace App\Adapters\Clock;

use Bathys\Ports\Clock;

/**
 * An adapter for the Clock port: the real system time, always in UTC.
 */
final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
