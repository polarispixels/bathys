<?php

declare(strict_types=1);

namespace Bathys\Domain;

/**
 * Thrown when text is not shaped like an id (a UUID).
 *
 * It sits here, not beside one id class, because every id in the domain shares it.
 */
final class InvalidId extends InvalidValue
{
    public static function notAUuid(string $given): self
    {
        return new self("\"{$given}\" is not a valid id. An id is a UUID such as 0192b4d8-7a3c-7e1f-9b2a-3c4d5e6f7a8b.");
    }
}
