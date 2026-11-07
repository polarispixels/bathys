<?php

declare(strict_types=1);

namespace Tests\Core\Ports;

use Bathys\Ports\BookRepository;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\InMemoryBookRepository;
use Tests\Shared\BookRepositoryContract;

final class InMemoryBookRepositoryTest extends TestCase
{
    use BookRepositoryContract;

    protected function makeRepository(): BookRepository
    {
        return new InMemoryBookRepository;
    }
}
