<?php

declare(strict_types=1);

namespace Bathys\Domain\Book;

use Bathys\Domain\InvalidValue;

/**
 * Thrown when a title breaks a rule. Its own class, so a caller can tell a bad
 * title from a bad author.
 */
final class InvalidTitle extends InvalidValue
{
    public static function blank(): self
    {
        return new self('A title cannot be blank.');
    }
}
