<?php

declare(strict_types=1);

namespace Bathys\Domain;

/**
 * Thrown when typed text holds a character that should never be in a name:
 * a control character, or one of the bidirectional controls that can make
 * text display in a different order than it is stored.
 */
final class InvalidText extends InvalidValue
{
    public static function controlCharacters(): self
    {
        return new self('Text cannot contain control characters.');
    }
}
