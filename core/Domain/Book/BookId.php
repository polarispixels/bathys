<?php

declare(strict_types=1);

namespace Bathys\Domain\Book;

use Bathys\Domain\InvalidId;

/**
 * The identity of a book. A book is the same book even if its title changes,
 * so it needs an id of its own. A class keeps a book id from being mixed up
 * with any other string. It checks the shape only (anchored with \A and \z, because $ would also match before a trailing newline) (a UUID); it does not make ids.
 */
final readonly class BookId
{
    private function __construct(private string $value) {}

    public static function fromString(string $id): self
    {
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $id) !== 1) {
            throw InvalidId::notAUuid($id);
        }

        return new self(strtolower($id));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(BookId $other): bool
    {
        return $this->value === $other->value;
    }
}
