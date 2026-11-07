<?php

declare(strict_types=1);

namespace Tests\Doubles;

use Bathys\Domain\Book\Book;
use Bathys\Ports\BookRepository;

/**
 * A double: a small working stand-in for a port, here keeping books in an
 * array. It is not a mock, which would only check that certain calls happened.
 * This one really stores and returns books, and the shared repository suite
 * proves it behaves like the real database repository.
 */
final class InMemoryBookRepository implements BookRepository
{
    /** @var array<string, Book> */
    private array $books = [];

    public function save(Book $book): void
    {
        $this->books[$book->id()->toString()] = $book;
    }

    public function onShelf(): array
    {
        return array_values(array_filter($this->books, static fn (Book $book): bool => $book->isOnShelf()));
    }
}
