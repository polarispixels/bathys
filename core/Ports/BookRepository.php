<?php

declare(strict_types=1);

namespace Bathys\Ports;

use Bathys\Domain\Book\Book;

/**
 * A port: an interface the core defines for something it needs from the
 * outside world, here a place to keep books. The core says what it needs and
 * never how it is done, so a database, or a plain array in a test, can stand
 * behind it without the core changing.
 */
interface BookRepository
{
    /** Stores the book. A book with an id already stored replaces that one. */
    public function save(Book $book): void;

    /**
     * Books whose status is on the shelf. The order is not specified.
     *
     * @return list<Book>
     */
    public function onShelf(): array;
}
