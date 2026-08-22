<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('interviews')) {
            return;
        }

        Schema::create('interviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('student_id');
            $table->dateTime('scheduled_at');
            $table->string('location', 255)->nullable();
            $table->string('meeting_link', 255)->nullable();
            $table->string('attendance_status', 30)->default('scheduled'); // scheduled|present|absent|rescheduled
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index(['student_id']);
            $table->index(['scheduled_at']);
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
