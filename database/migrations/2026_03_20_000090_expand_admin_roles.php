<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('admin') || !Schema::hasColumn('admin', 'role')) {
            return;
        }

        try {
            DB::statement("ALTER TABLE admin MODIFY role VARCHAR(30) NOT NULL DEFAULT 'superadmin'");
            DB::statement("UPDATE admin SET role='superadmin' WHERE role IS NULL OR role='' ");
        } catch (\Throwable $e) {
            // ignore for compatibility
        }
    }

    public function down(): void
    {
        // no-op to avoid destructive enum downgrade
    }
};
