<?php

declare(strict_types=1);

namespace Bathys\Domain\Book;

use Bathys\Domain\Text;

/**
 * A title, as a value object: two with the same text are the same, and one
 * cannot exist blank or with stray spacing. A plain string would let a blank
 * or messy value travel through the program until something broke far away.
 * Equality is exact (case-sensitive) after the text is cleaned up.
 */
final readonly class Title
{
    private function __construct(private string $value) {}

    public static function fromString(string $title): self
    {
        $normalized = Text::normalize($title);

        if ($normalized === '') {
            throw InvalidTitle::blank();
        }

        return new self($normalized);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(Title $other): bool
    {
        return $this->value === $other->value;
    }
}
