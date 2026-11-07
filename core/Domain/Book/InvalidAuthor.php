<?php

declare(strict_types=1);

namespace Bathys\Domain\Book;

use Bathys\Domain\InvalidValue;

/**
 * Thrown when an author name breaks a rule. Its own class, so a caller can
 * tell a bad author from a bad title.
 */
final class InvalidAuthor extends InvalidValue
{
    public static function blank(): self
    {
        return new self('An author name cannot be blank.');
    }
}
