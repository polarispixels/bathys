<?php

declare(strict_types=1);

namespace Bathys\Application;

use Bathys\Domain\Book\Book;
use Bathys\Ports\BookRepository;

/**
 * A use case: show what is on the shelf, ordered by title with case ignored.
 * Sorting lives here, not in the repository, so every storage gives the same
 * order. PHP's sort is stable, so books with equal titles keep their order.
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
            static fn (Book $a, Book $b): int => mb_strtolower($a->title()->toString()) <=> mb_strtolower($b->title()->toString()),
        );

        return $shelf;
    }
}
