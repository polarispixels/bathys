<?php

declare(strict_types=1);

use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\BookId;
use Bathys\Domain\Book\Status;
use Bathys\Domain\Book\Title;

function exampleBookId(string $last = '000000000001'): BookId
{
    return BookId::fromString('0192b4d8-7a3c-7e1f-9b2a-'.$last);
}

function authorNames(Book $book): array
{
    return array_map(fn (Author $a) => $a->toString(), $book->authors());
}

it('starts on the shelf with no authors when added with only a title', function () {
    $book = Book::add(exampleBookId(), Title::fromString('Dune'), new DateTimeImmutable('2026-11-07T10:00:00Z'));

    expect($book->isOnShelf())->toBeTrue()
        ->and($book->isWanted())->toBeFalse()
        ->and($book->authors())->toBe([]);
});

it('can be added as wanted', function () {
    $book = Book::add(exampleBookId(), Title::fromString('Dune'), new DateTimeImmutable('2026-11-07T10:00:00Z'), Status::Wanted);

    expect($book->status())->toBe(Status::Wanted)
        ->and($book->isWanted())->toBeTrue()
        ->and($book->isOnShelf())->toBeFalse();
});

it('keeps authors in cover order and drops a repeat', function () {
    $book = Book::add(exampleBookId(), Title::fromString('Dune'), new DateTimeImmutable('2026-11-07T10:00:00Z'));
    $a = Author::fromString('Mortimer J. Adler');
    $b = Author::fromString('Charles Van Doren');

    $book->creditAuthors($a, $b, $a);

    expect(authorNames($book))->toBe(['Mortimer J. Adler', 'Charles Van Doren']);
});

it('drops repeated authors given to the constructor too', function () {
    $a = Author::fromString('Gino Wickman');

    $book = new Book(exampleBookId(), Title::fromString('Traction'), [$a, $a], Status::OnShelf, new DateTimeImmutable('2026-11-07T10:00:00Z'));

    expect(authorNames($book))->toBe(['Gino Wickman']);
});

it('stores the moment it was added in UTC', function () {
    $book = Book::add(exampleBookId(), Title::fromString('Dune'), new DateTimeImmutable('2026-11-07T10:00:00-05:00'));

    expect($book->addedAt()->format('c'))->toBe('2026-11-07T15:00:00+00:00')
        ->and($book->addedAt()->getTimezone()->getName())->toBe('UTC');
});

it('is identified by its id, not its title', function () {
    $when = new DateTimeImmutable('2026-11-07T10:00:00Z');
    $one = Book::add(exampleBookId('000000000001'), Title::fromString('Dune'), $when);
    $two = Book::add(exampleBookId('000000000002'), Title::fromString('Dune'), $when);

    expect($one->title()->equals($two->title()))->toBeTrue()
        ->and($one->id()->equals($two->id()))->toBeFalse();
});
