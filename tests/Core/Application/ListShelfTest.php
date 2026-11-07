<?php

declare(strict_types=1);

use Bathys\Application\ListShelf;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\BookId;
use Bathys\Domain\Book\Status;
use Bathys\Domain\Book\Title;
use Tests\Doubles\InMemoryBookRepository;

function shelfBook(string $n, string $title, Status $status = Status::OnShelf): Book
{
    return Book::add(
        BookId::fromString('00000000-0000-7000-8000-00000000000'.$n),
        Title::fromString($title),
        new DateTimeImmutable('2026-11-07T15:04:05+00:00'),
        $status,
    );
}

it('lists shelf books by title, ignoring case', function () {
    $repo = new InMemoryBookRepository;
    $repo->save(shelfBook('1', 'the Checklist Manifesto'));
    $repo->save(shelfBook('2', 'Secrets of Closing the Sale'));
    $repo->save(shelfBook('3', 'Rich Dad Poor Dad'));
    $repo->save(shelfBook('4', 'Good to Great'));

    $titles = array_map(fn (Book $b) => $b->title()->toString(), (new ListShelf($repo))->execute());

    expect($titles)->toBe(['Good to Great', 'Rich Dad Poor Dad', 'Secrets of Closing the Sale', 'the Checklist Manifesto']);
});

it('leaves wanted books out', function () {
    $repo = new InMemoryBookRepository;
    $repo->save(shelfBook('1', 'Dune'));
    $repo->save(shelfBook('2', 'Emma', Status::Wanted));

    $titles = array_map(fn (Book $b) => $b->title()->toString(), (new ListShelf($repo))->execute());

    expect($titles)->toBe(['Dune']);
});

it('lists two books that share a title', function () {
    $repo = new InMemoryBookRepository;
    $repo->save(shelfBook('1', 'Dune'));
    $repo->save(shelfBook('2', 'Dune'));

    expect((new ListShelf($repo))->execute())->toHaveCount(2);
});
