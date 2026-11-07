<?php

declare(strict_types=1);

namespace App\Adapters\Persistence;

use App\Models\BookRecord;
use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\BookId;
use Bathys\Domain\Book\Status;
use Bathys\Domain\Book\Title;

/**
 * Translates between the domain's Book and the database's BookRecord.
 *
 * Why it exists (ADR-0004): the core must not know about Eloquent, and the
 * table must not dictate the shape of the domain. This is the one place that
 * knows both, so a change to either side stays in this file.
 */
final class BookMapper
{
    public function toDomain(BookRecord $record): Book
    {
        $authors = array_map(
            static fn (string $name): Author => Author::fromString($name),
            $record->authors,
        );

        return new Book(
            BookId::fromString($record->id),
            Title::fromString($record->title),
            $authors,
            Status::from($record->status),
            $record->added_at,
        );
    }

    public function fill(BookRecord $record, Book $book): BookRecord
    {
        $record->fill([
            'title' => $book->title()->toString(),
            'authors' => array_map(static fn (Author $a): string => $a->toString(), $book->authors()),
            'status' => $book->status()->value,
            'added_at' => $book->addedAt(),
        ]);

        return $record;
    }
}
