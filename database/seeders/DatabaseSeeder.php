<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Project ini mengandalkan data legacy SQL + migration seed ringan.
        // Tidak ada factory default yang dijalankan agar skema users legacy tidak bentrok.
    }
}
