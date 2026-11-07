<?php

declare(strict_types=1);

namespace Tests\Doubles;

use Bathys\Domain\Book\BookId;
use Bathys\Ports\IdGenerator;

/**
 * A fake id generator that counts up: ...0001, ...0002, and so on.
 * Predictable ids let a test name the exact id it expects. It is a fake (one
 * kind of test double); unlike a mock, it does not check which calls happened.
 */
final class SequentialIdGenerator implements IdGenerator
{
    private int $counter = 0;

    public function nextBookId(): BookId
    {
        $this->counter++;

        return BookId::fromString(sprintf('00000000-0000-7000-8000-%012d', $this->counter));
    }
}
