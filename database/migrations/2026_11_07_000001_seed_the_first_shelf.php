<?php

declare(strict_types=1);

use Database\Seeders\ShelfSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // The deploy script runs `php artisan migrate --force`, so this is how
        // the first shelf reaches production with nobody typing a command.
        //
        // The rows are written directly, with the columns as they stood at this
        // timestamp, on purpose. A migration that called today's use cases would
        // break every fresh database (CI, tests, a new server) the first time a
        // later migration adds a column. The list is only data, read from the
        // seeder's constant. The seeder (ShelfSeeder) remains the way to seed
        // through the domain, with `db:seed`.
        $now = now()->utc();

        foreach (ShelfSeeder::BOOKS as $entry) {
            if (DB::table('books')->where('title', $entry['title'])->exists()) {
                continue;
            }

            DB::table('books')->insert([
                'id' => (string) Str::uuid7(),
                'title' => $entry['title'],
                'authors' => json_encode($entry['authors']),
                'status' => 'shelf',
                'added_at' => $now->format('Y-m-d H:i:sP'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // The books are data, not schema. Rolling back leaves them alone.
    }
};
