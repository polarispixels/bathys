<?php

declare(strict_types=1);

use App\Models\BookRecord;
use App\Models\User;
use Bathys\Application\AddBook;
use Bathys\Application\ListShelf;
use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// RefreshDatabase runs every migration, and one of them seeds the first shelf,
// so each test below starts with the eight books already present.

it('seeds the eight books through the use case', function () {
    $shelf = app(ListShelf::class)->execute();
    $titles = array_map(fn (Book $book): string => $book->title()->toString(), $shelf);

    expect($shelf)->toHaveCount(8)
        ->and($titles[0])->toBe('Building a StoryBrand')
        ->and($titles[7])->toBe('What Color Is Your Parachute?');

    $adler = collect($shelf)->first(fn (Book $book): bool => $book->title()->toString() === 'How to Read a Book');
    expect(array_map(fn (Author $a): string => $a->toString(), $adler->authors()))
        ->toBe(['Mortimer J. Adler', 'Charles Van Doren']);
});

it('is idempotent', function () {
    // Start from empty so the seeder itself, not the migration, does the seeding.
    BookRecord::query()->delete();

    $this->seed();
    $this->seed();

    expect(app(ListShelf::class)->execute())->toHaveCount(8);
});

it('does not create the development user', function () {
    $this->seed();

    expect(User::count())->toBe(0);
});

it('treats a book with different authors as a different book', function () {
    BookRecord::query()->delete();
    app(AddBook::class)->execute(Title::fromString('Good to Great'), Author::fromString('Someone Else'));

    $this->seed();

    $shelf = app(ListShelf::class)->execute();
    $goodToGreat = array_filter($shelf, fn (Book $book): bool => $book->title()->toString() === 'Good to Great');

    expect($shelf)->toHaveCount(9)
        ->and($goodToGreat)->toHaveCount(2);
});
