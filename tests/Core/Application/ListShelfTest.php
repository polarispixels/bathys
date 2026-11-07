<?php

declare(strict_types=1);

use Bathys\Application\ListShelf;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\BookId;
use Bathys\Domain\Book\Status;
use Bathys\Domain\Book\Title;
use Tests\Doubles\InMemoryBookRepository;

function shelfBookNamed(string $n, string $title, Status $status = Status::OnShelf): Book
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
    $repo->save(shelfBookNamed('1', 'the Checklist Manifesto'));
    $repo->save(shelfBookNamed('2', 'Secrets of Closing the Sale'));
    $repo->save(shelfBookNamed('3', 'Rich Dad Poor Dad'));
    $repo->save(shelfBookNamed('4', 'Good to Great'));

    $titles = array_map(fn (Book $b) => $b->title()->toString(), (new ListShelf($repo))->execute());

    expect($titles)->toBe(['Good to Great', 'Rich Dad Poor Dad', 'Secrets of Closing the Sale', 'the Checklist Manifesto']);
});

it('leaves wanted books out', function () {
    $repo = new InMemoryBookRepository;
    $repo->save(shelfBookNamed('1', 'Dune'));
    $repo->save(shelfBookNamed('2', 'Emma', Status::Wanted));

    $titles = array_map(fn (Book $b) => $b->title()->toString(), (new ListShelf($repo))->execute());

    expect($titles)->toBe(['Dune']);
});

it('lists two books that share a title', function () {
    $repo = new InMemoryBookRepository;
    $repo->save(shelfBookNamed('1', 'Dune'));
    $repo->save(shelfBookNamed('2', 'Dune'));

    expect((new ListShelf($repo))->execute())->toHaveCount(2);
});

it('orders books that share a title by id, whatever order they were saved in', function () {
    $repo = new InMemoryBookRepository;
    $repo->save(shelfBookNamed('2', 'Dune'));
    $repo->save(shelfBookNamed('1', 'Dune'));

    $ids = array_map(fn (Book $b) => $b->id()->toString(), (new ListShelf($repo))->execute());

    expect($ids)->toBe(['00000000-0000-7000-8000-000000000001', '00000000-0000-7000-8000-000000000002']);
});
