<?php

declare(strict_types=1);

namespace App\Adapters\Ids;

use Bathys\Domain\Book\BookId;
use Bathys\Ports\IdGenerator;
use Illuminate\Support\Str;

/**
 * An adapter for the IdGenerator port. UUID version 7 ids start with a
 * timestamp, so new rows land at the end of the index instead of all over it.
 */
final class UuidV7IdGenerator implements IdGenerator
{
    public function nextBookId(): BookId
    {
        return BookId::fromString((string) Str::uuid7());
    }
}
