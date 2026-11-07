<?php

declare(strict_types=1);

namespace Bathys\Domain\Book;

/**
 * A book in Bathys: the entity the rest of the program is built around. Unlike
 * a value object it is identified by its id, not its fields, so two books can
 * share a title and a book can change while staying the same book. It is a
 * class with methods, not an array, so its rules live next to its data.
 */
final class Book
{
    /** @var list<Author> */
    private array $authors;

    private \DateTimeImmutable $addedAt;

    /**
     * @param  list<Author>  $authors
     */
    public function __construct(
        private readonly BookId $id,
        private Title $title,
        array $authors,
        private Status $status,
        \DateTimeImmutable $addedAt,
    ) {
        $this->authors = self::withoutRepeats($authors);
        // Store the instant in UTC so it does not depend on the caller's time zone.
        $this->addedAt = $addedAt->setTimezone(new \DateTimeZone('UTC'));
    }

    /** The common path: a title and a moment, on the shelf unless said otherwise. */
    public static function add(BookId $id, Title $title, \DateTimeImmutable $addedAt, Status $status = Status::OnShelf): self
    {
        return new self($id, $title, [], $status, $addedAt);
    }

    /** Replaces the author list. Order is kept; an author listed twice is kept once. */
    public function creditAuthors(Author ...$authors): void
    {
        $this->authors = self::withoutRepeats(array_values($authors));
    }

    public function id(): BookId
    {
        return $this->id;
    }

    public function title(): Title
    {
        return $this->title;
    }

    /** @return list<Author> */
    public function authors(): array
    {
        return $this->authors;
    }

    public function status(): Status
    {
        return $this->status;
    }

    public function addedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function isOnShelf(): bool
    {
        return $this->status === Status::OnShelf;
    }

    public function isWanted(): bool
    {
        return $this->status === Status::Wanted;
    }

    /**
     * @param  list<Author>  $authors
     * @return list<Author>
     */
    private static function withoutRepeats(array $authors): array
    {
        $kept = [];
        foreach ($authors as $author) {
            foreach ($kept as $existing) {
                if ($existing->equals($author)) {
                    continue 2;
                }
            }
            $kept[] = $author;
        }

        return $kept;
    }
}
