<?php

declare(strict_types=1);

namespace Bathys\Application;

use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\Title;
use Bathys\Ports\BookRepository;
use Bathys\Ports\Clock;
use Bathys\Ports\IdGenerator;

/**
 * A use case: one thing a person can do with the app, written as a small class
 * with one method. It builds domain objects and talks to the outside only
 * through ports, so controllers and tests call it the same way.
 */
final class AddBook
{
    public function __construct(
        private BookRepository $books,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function execute(Title $title, Author ...$authors): Book
    {
        $book = Book::add($this->ids->nextBookId(), $title, $this->clock->now());
        $book->creditAuthors(...$authors);

        $this->books->save($book);

        return $book;
    }
}
