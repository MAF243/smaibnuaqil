<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'registration_number')) {
                $table->string('registration_number', 30)->nullable()->after('id');
                $table->unique('registration_number');
            }
            if (!Schema::hasColumn('students', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('created_at');
            }
            if (!Schema::hasColumn('students', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('students', 'reviewed_by_admin_id')) {
                $table->unsignedInteger('reviewed_by_admin_id')->nullable()->after('reviewed_at');
                $table->index('reviewed_by_admin_id');
            }
            if (!Schema::hasColumn('students', 'result_note')) {
                $table->text('result_note')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('students', 'announcement_published_at')) {
                $table->timestamp('announcement_published_at')->nullable()->after('result_note');
            }
            if (!Schema::hasColumn('students', 'last_status_note')) {
                $table->text('last_status_note')->nullable()->after('announcement_published_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            foreach (['registration_number','submitted_at','reviewed_at','reviewed_by_admin_id','result_note','announcement_published_at','last_status_note'] as $col) {
                if (Schema::hasColumn('students', $col)) {
                    try { $table->dropColumn($col); } catch (\Throwable $e) {}
                }
            }
        });
    }
};
