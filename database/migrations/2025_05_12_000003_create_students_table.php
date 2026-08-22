<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            return;
        }

        Schema::create('students', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name', 100);
            $table->date('dob');
            $table->string('phone', 15);
            $table->text('address');

            // Legacy dump contains empty status values; keep as string for compatibility.
            $table->string('status', 20)->default('pending');

            $table->timestamp('created_at')->useCurrent();

            // Files
            $table->string('kk_file', 255)->nullable();
            $table->string('ktp_father', 255)->nullable();
            $table->string('ktp_mother', 255)->nullable();

            // Guardian
            $table->string('guardian_name', 100)->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_relation', 50)->nullable();
            $table->string('ktp_guardian', 255)->nullable();
            $table->string('kk_guardian', 255)->nullable();

            // Parents
            $table->string('father_name', 100)->nullable();
            $table->string('father_phone', 20)->nullable();
            $table->string('father_job', 100)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('mother_phone', 20)->nullable();
            $table->string('mother_job', 100)->nullable();

            // Additional documents
            $table->string('akta_lahir', 255)->nullable();
            $table->string('nilai_rapor', 255)->nullable();

            // Extra profile fields
            $table->string('father_email', 100)->nullable();
            $table->string('father_income', 50)->nullable();
            $table->string('father_birthplace', 100)->nullable();
            $table->date('father_dob')->nullable();

            $table->string('mother_email', 100)->nullable();
            $table->string('mother_income', 50)->nullable();
            $table->string('mother_birthplace', 100)->nullable();
            $table->date('mother_dob')->nullable();

            $table->string('guardian_birthplace', 100)->nullable();
            $table->date('guardian_dob')->nullable();
            $table->string('guardian_email', 100)->nullable();
            $table->string('guardian_income', 50)->nullable();

            $table->string('student_hobby', 100)->nullable();
            $table->string('goal', 100)->nullable();
            $table->string('motivation', 100)->nullable();
            $table->string('birthplace', 100)->nullable();

            $table->string('gender', 20)->nullable();
            $table->string('religion_child', 30)->nullable();
            $table->string('father_religion', 30)->nullable();
            $table->string('mother_religion', 30)->nullable();
            $table->string('guardian_religion', 30)->nullable();

            $table->unsignedInteger('user_id')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->index('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
