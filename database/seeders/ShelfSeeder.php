<?php

declare(strict_types=1);

namespace Database\Seeders;

use Bathys\Application\AddBook;
use Bathys\Application\ListShelf;
use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\Title;
use Illuminate\Database\Seeder;

/**
 * Puts Ryan's first eight books on the shelf.
 *
 * It goes through the same use cases the app uses, so a seeded book is built
 * exactly like a book added by hand. It is safe to run any number of times:
 * a book already on the shelf is skipped. The seeder compares value objects,
 * so whitespace differences are absorbed, while a different capitalization or
 * a different author list counts as a different book. Running it again adds
 * nothing.
 */
class ShelfSeeder extends Seeder
{
    /** @var list<array{title: string, authors: list<string>}> */
    public const BOOKS = [
        ['title' => 'Think and Grow Rich', 'authors' => ['Napoleon Hill']],
        ['title' => 'How to Read a Book', 'authors' => ['Mortimer J. Adler', 'Charles Van Doren']],
        ['title' => 'Building a StoryBrand', 'authors' => ['Donald Miller']],
        ['title' => 'What Color Is Your Parachute?', 'authors' => ['Richard N. Bolles']],
        ['title' => 'Rich Dad Poor Dad', 'authors' => ['Robert T. Kiyosaki']],
        ['title' => 'Good to Great', 'authors' => ['Jim Collins']],
        ['title' => 'The Checklist Manifesto', 'authors' => ['Atul Gawande']],
        ['title' => 'Secrets of Closing the Sale', 'authors' => ['Zig Ziglar']],
    ];

    public function run(ListShelf $listShelf, AddBook $addBook): void
    {
        foreach (self::BOOKS as $entry) {
            $title = Title::fromString($entry['title']);
            $authors = array_map(Author::fromString(...), $entry['authors']);

            foreach ($listShelf->execute() as $book) {
                if ($this->isSameBook($book, $title, $authors)) {
                    continue 2;
                }
            }

            $addBook->execute($title, ...$authors);
        }
    }

    /** @param list<Author> $authors */
    private function isSameBook(Book $book, Title $title, array $authors): bool
    {
        if (! $book->title()->equals($title)) {
            return false;
        }

        $listed = $book->authors();
        if (count($listed) !== count($authors)) {
            return false;
        }

        foreach ($listed as $position => $author) {
            if (! $author->equals($authors[$position])) {
                return false;
            }
        }

        return true;
    }
}
