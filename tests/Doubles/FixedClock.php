<?php

declare(strict_types=1);

namespace Tests\Doubles;

use Bathys\Ports\Clock;

/**
 * A stub clock that always says the same moment, so a test can check an
 * exact time. It is a fake (one kind of test double); unlike a mock, it does
 * not check which calls happened.
 */
final class FixedClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
