<?php

declare(strict_types=1);

namespace Bathys\Application;

use Bathys\Domain\Book\Book;
use Bathys\Ports\BookRepository;

/**
 * A use case: show what is on the shelf, ordered by title with case ignored.
 * Sorting lives here, not in the repository, so every storage gives the same
 * order. Books with equal titles are ordered by id; a UUID v7 follows the time
 * it was made, so same-title books keep a stable order across page loads.
 */
final class ListShelf
{
    public function __construct(private BookRepository $books) {}

    /** @return list<Book> */
    public function execute(): array
    {
        $shelf = $this->books->onShelf();

        usort(
            $shelf,
            static fn (Book $a, Book $b): int => [mb_strtolower($a->title()->toString()), $a->id()->toString()]
                <=> [mb_strtolower($b->title()->toString()), $b->id()->toString()],
        );

        return $shelf;
    }
}
