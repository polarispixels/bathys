<?php

declare(strict_types=1);

use Bathys\Domain\Book\BookId;
use Bathys\Domain\InvalidId;

it('accepts a UUID and lowercases it', fn () => expect(BookId::fromString('0192B4D8-7A3C-7E1F-9B2A-3C4D5E6F7A8B')->toString())->toBe('0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8b'));
it('rejects anything that is not a UUID', fn (string $bad) => BookId::fromString($bad))->with(['', 'not-a-uuid', '0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8', '0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8g'])->throws(InvalidId::class);
