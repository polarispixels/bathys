<?php

declare(strict_types=1);

namespace Bathys\Domain\Book;

/**
 * Where a book lives: on the shelf (owned) or on the wishlist (wanted). An enum
 * rather than a string because there are exactly two answers, and a typo in a
 * string would be a silent bug. The backing values are the spec's words, so
 * they can reach the database and the API unchanged.
 */
enum Status: string
{
    case OnShelf = 'shelf';
    case Wanted = 'wanted';
}
