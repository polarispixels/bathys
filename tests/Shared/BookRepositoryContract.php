<?php

declare(strict_types=1);

namespace Tests\Shared;

use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\BookId;
use Bathys\Domain\Book\Status;
use Bathys\Domain\Book\Title;
use Bathys\Ports\BookRepository;

/**
 * One set of tests that every BookRepository must pass. The in-memory double
 * and the database repository each use this trait, so they cannot drift apart:
 * if the double behaves one way and the real thing another, a test here fails.
 * It uses only PHPUnit assertions, so it runs with or without the framework.
 */
trait BookRepositoryContract
{
    abstract protected function makeRepository(): BookRepository;

    public function test_a_saved_shelf_book_is_listed_with_every_field_intact(): void
    {
        $repository = $this->makeRepository();
        $id = BookId::fromString('00000000-0000-7000-8000-0000000000aa');
        $book = Book::add($id, Title::fromString('Good to Great'), new \DateTimeImmutable('2026-11-07T15:04:05+00:00'));
        $book->creditAuthors(Author::fromString('Jim Collins'), Author::fromString('Morten Hansen'));

        $repository->save($book);

        $listed = $repository->onShelf();
        $this->assertCount(1, $listed);
        $this->assertTrue($listed[0]->id()->equals($id));
        $this->assertSame('Good to Great', $listed[0]->title()->toString());
        $this->assertSame(
            ['Jim Collins', 'Morten Hansen'],
            array_map(static fn (Author $a): string => $a->toString(), $listed[0]->authors()),
        );
        $this->assertSame(Status::OnShelf, $listed[0]->status());
        $this->assertSame($book->addedAt()->getTimestamp(), $listed[0]->addedAt()->getTimestamp());
        $this->assertSame('UTC', $listed[0]->addedAt()->getTimezone()->getName());
    }

    public function test_a_wanted_book_is_not_on_the_shelf(): void
    {
        $repository = $this->makeRepository();
        $repository->save(Book::add(
            BookId::fromString('00000000-0000-7000-8000-0000000000ab'),
            Title::fromString('Thinking, Fast and Slow'),
            new \DateTimeImmutable('2026-11-07T15:04:05+00:00'),
            Status::Wanted,
        ));

        $this->assertSame([], $repository->onShelf());
    }

    public function test_saving_the_same_id_again_updates_instead_of_duplicating(): void
    {
        $repository = $this->makeRepository();
        $id = BookId::fromString('00000000-0000-7000-8000-0000000000ac');
        $at = new \DateTimeImmutable('2026-11-07T15:04:05+00:00');
        $repository->save(Book::add($id, Title::fromString('Good to Great'), $at));

        $changed = Book::add($id, Title::fromString('Good to Great'), $at);
        $changed->creditAuthors(Author::fromString('Jim Collins'));
        $repository->save($changed);

        $listed = $repository->onShelf();
        $this->assertCount(1, $listed);
        $this->assertSame(['Jim Collins'], array_map(static fn (Author $a): string => $a->toString(), $listed[0]->authors()));
    }

    public function test_an_empty_repository_lists_nothing(): void
    {
        $this->assertSame([], $this->makeRepository()->onShelf());
    }

    public function test_two_books_with_the_same_title_are_both_listed(): void
    {
        $repository = $this->makeRepository();
        $at = new \DateTimeImmutable('2026-11-07T15:04:05+00:00');
        $repository->save(Book::add(BookId::fromString('00000000-0000-7000-8000-0000000000ad'), Title::fromString('Dune'), $at));
        $repository->save(Book::add(BookId::fromString('00000000-0000-7000-8000-0000000000ae'), Title::fromString('Dune'), $at));

        $this->assertCount(2, $repository->onShelf());
    }
}
