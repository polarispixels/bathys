<?php

declare(strict_types=1);

namespace Bathys\Ports;

use Bathys\Domain\Book\BookId;

/**
 * A port for making new ids. Id schemes are an outside concern, so the core
 * asks for the next one, and a test can hand out predictable ids.
 */
interface IdGenerator
{
    public function nextBookId(): BookId;
}
