<?php

declare(strict_types=1);

namespace App\Adapters\Persistence;

use App\Models\BookRecord;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\Status;
use Bathys\Ports\BookRepository;

/**
 * An adapter: the piece that plugs a port (BookRepository, defined by the
 * core) into a real technology (Postgres through Eloquent). The core never
 * sees this class, only the interface it implements.
 */
final class EloquentBookRepository implements BookRepository
{
    public function __construct(private readonly BookMapper $mapper) {}

    public function save(Book $book): void
    {
        $id = $book->id()->toString();
        $record = BookRecord::query()->find($id) ?? new BookRecord(['id' => $id]);

        $this->mapper->fill($record, $book)->save();
    }

    public function onShelf(): array
    {
        return array_values(
            BookRecord::query()
                ->where('status', Status::OnShelf->value)
                ->get()
                ->map(fn (BookRecord $record): Book => $this->mapper->toDomain($record))
                ->all(),
        );
    }
}
