<?php

declare(strict_types=1);

use App\Models\BookRecord;
use Bathys\Application\AddBook;
use Bathys\Domain\Book\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('lists the seeded shelf in title order', function () {
    // Seeded by the migration. Sorted: 0 Building a StoryBrand ... 7 What Color Is Your Parachute?
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('shelf')
        ->has('books.data', 8)
        ->where('books.data.0.attributes.title', 'Building a StoryBrand')
        ->where('books.data.7.attributes.title', 'What Color Is Your Parachute?')
        ->where('books.data.2.attributes.authors', ['Mortimer J. Adler', 'Charles Van Doren'])
        ->where('books.data.0.type', 'books')
        ->where('books.data.0.attributes.status', 'shelf'));
});

it('passes an empty collection when there are no books', function () {
    BookRecord::query()->delete();

    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('books.data', 0));
});

it('passes a book with no authors as an empty list', function () {
    BookRecord::query()->delete();
    app(AddBook::class)->execute(Title::fromString('Walden'));

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('books.data.0.attributes.authors', []));
});

it('keeps the version meta tag', function () {
    $this->get('/')->assertSee('<meta name="app-version"', false);
});

it('gives the page the package.json version to display', function () {
    $version = json_decode((string) file_get_contents(base_path('package.json')), true)['version'];

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('appVersion', $version));
});

it('rate limits the home page', function () {
    $this->get('/')->assertHeader('X-RateLimit-Limit', '60');
});

it('serves addedAt in UTC', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('books.data.0.attributes.addedAt', fn ($value) => str_ends_with((string) $value, '+00:00')));
});
