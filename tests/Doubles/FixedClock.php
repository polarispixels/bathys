<?php

declare(strict_types=1);

namespace Tests\Doubles;

use Bathys\Ports\Clock;

/**
 * A double for the clock that always says the same moment, so a test can
 * check an exact time. It returns a value instead of recording calls, which
 * is what separates a double from a mock.
 */
final class FixedClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
