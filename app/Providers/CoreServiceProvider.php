<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Clock\SystemClock;
use App\Adapters\Ids\UuidV7IdGenerator;
use App\Adapters\Persistence\EloquentBookRepository;
use Bathys\Ports\BookRepository;
use Bathys\Ports\Clock;
use Bathys\Ports\IdGenerator;
use Illuminate\Support\ServiceProvider;

/**
 * The composition root: the one place that decides which adapter stands
 * behind each port. Use cases ask the container for a port, and this file is
 * where it learns the answer. Swap an adapter here and nothing else changes.
 */
final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BookRepository::class, EloquentBookRepository::class);
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(IdGenerator::class, UuidV7IdGenerator::class);
    }
}
