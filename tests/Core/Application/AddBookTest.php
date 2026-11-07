<?php

declare(strict_types=1);

use Bathys\Application\AddBook;
use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\Title;
use Tests\Doubles\FixedClock;
use Tests\Doubles\InMemoryBookRepository;
use Tests\Doubles\SequentialIdGenerator;

it('adds a book on the shelf with an id from the generator and the clock time', function () {
    $repo = new InMemoryBookRepository;
    $clock = new FixedClock(new DateTimeImmutable('2026-11-07T15:04:05+00:00'));
    $book = (new AddBook($repo, new SequentialIdGenerator, $clock))->execute(Title::fromString('Good to Great'), Author::fromString('Jim Collins'));
    expect($book->id()->toString())->toBe('00000000-0000-7000-8000-000000000001')
        ->and($book->addedAt()->format(DATE_ATOM))->toBe('2026-11-07T15:04:05+00:00')
        ->and($book->isOnShelf())->toBeTrue()
        ->and($book->authors())->toHaveCount(1)
        ->and($repo->onShelf())->toHaveCount(1);
});

it('adds a book with no authors', function () {
    $repo = new InMemoryBookRepository;
    $clock = new FixedClock(new DateTimeImmutable('2026-11-07T15:04:05+00:00'));
    $book = (new AddBook($repo, new SequentialIdGenerator, $clock))->execute(Title::fromString('Dune'));
    expect($book->authors())->toBe([])
        ->and($repo->onShelf())->toHaveCount(1);
});
