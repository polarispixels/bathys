<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. This may run on production, so it holds
     * real data only. The real user arrives with issue #7.
     */
    public function run(): void
    {
        $this->call(ShelfSeeder::class);
    }
}
