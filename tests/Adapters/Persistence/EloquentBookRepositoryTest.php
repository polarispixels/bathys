<?php

declare(strict_types=1);

namespace Tests\Adapters\Persistence;

use App\Models\BookRecord;
use Bathys\Domain\Book\Book;
use Bathys\Domain\Book\BookId;
use Bathys\Domain\Book\Title;
use Bathys\Ports\BookRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Shared\BookRepositoryContract;
use Tests\TestCase;

/**
 * Runs the shared repository suite against real Postgres, so the database
 * adapter must behave exactly like the in-memory double.
 */
final class EloquentBookRepositoryTest extends TestCase
{
    use BookRepositoryContract;
    use RefreshDatabase;

    /**
     * The seed migration puts the first shelf in the table, outside the test
     * transaction. These tests need an empty repository, so clear it first.
     */
    protected function setUp(): void
    {
        parent::setUp();

        BookRecord::query()->delete();
    }

    protected function makeRepository(): BookRepository
    {
        return app(BookRepository::class);
    }

    public function test_the_instant_survives_a_session_time_zone(): void
    {
        DB::statement("SET TIME ZONE 'America/New_York'");
        $repository = $this->makeRepository();
        $at = new \DateTimeImmutable('2026-11-07T15:04:05+00:00');
        $repository->save(Book::add(
            BookId::fromString('00000000-0000-7000-8000-0000000000af'),
            Title::fromString('Dune'),
            $at,
        ));

        $listed = $repository->onShelf();

        $this->assertCount(1, $listed);
        $this->assertSame($at->getTimestamp(), $listed[0]->addedAt()->getTimestamp());
        $this->assertSame('UTC', $listed[0]->addedAt()->getTimezone()->getName());
    }
}
