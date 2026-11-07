<?php

declare(strict_types=1);

use Database\Seeders\ShelfSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        // The deploy script runs `php artisan migrate --force` on every deploy,
        // so this is how the first shelf reaches production without anyone
        // running a command. The seeder is idempotent, so a re-run is safe.
        Artisan::call('db:seed', ['--class' => ShelfSeeder::class, '--force' => true]);
    }

    public function down(): void
    {
        // The books are data, not schema. Rolling back leaves them alone.
    }
};
