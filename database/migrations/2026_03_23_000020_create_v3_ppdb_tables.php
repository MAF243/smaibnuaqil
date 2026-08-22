<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('student_applications')) {
            Schema::create('student_applications', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('legacy_student_id')->nullable()->unique();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->string('registration_no', 30)->nullable()->unique();
                $table->string('academic_year', 20)->nullable();
                $table->string('current_status', 30)->default('draft');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by_account_id')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('result_notes')->nullable();
                $table->timestamp('announcement_published_at')->nullable();
                $table->text('last_status_note')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['account_id', 'current_status']);
                $table->index('submitted_at');
                $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
                $table->foreign('reviewed_by_account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('student_profiles')) {
            Schema::create('student_profiles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('application_id')->unique();
                $table->string('full_name', 120);
                $table->string('gender', 20)->nullable();
                $table->string('birth_place', 100)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('religion', 30)->nullable();
                $table->string('phone', 30)->nullable();
                $table->text('address')->nullable();
                $table->string('hobby', 120)->nullable();
                $table->string('goal', 150)->nullable();
                $table->text('motivation')->nullable();
                $table->timestamps();
                $table->foreign('application_id')->references('id')->on('student_applications')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('application_guardians')) {
            Schema::create('application_guardians', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('application_id');
                $table->string('guardian_type', 20); // father|mother|guardian
                $table->string('full_name', 120)->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email', 120)->nullable();
                $table->string('occupation', 120)->nullable();
                $table->string('income_range', 60)->nullable();
                $table->string('birth_place', 100)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('religion', 30)->nullable();
                $table->string('relationship_to_student', 60)->nullable();
                $table->timestamps();
                $table->unique(['application_id', 'guardian_type']);
                $table->foreign('application_id')->references('id')->on('student_applications')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('application_documents')) {
            Schema::create('application_documents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('application_id');
                $table->string('document_type', 50);
                $table->string('legacy_source', 40)->nullable();
                $table->unsignedBigInteger('legacy_source_id')->nullable();
                $table->string('original_name', 255)->nullable();
                $table->string('stored_name', 255)->nullable();
                $table->string('file_path', 255);
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->unsignedBigInteger('uploaded_by_account_id')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->unsignedBigInteger('verified_by_account_id')->nullable();
                $table->string('verification_status', 30)->default('pending');
                $table->text('verification_notes')->nullable();
                $table->timestamps();
                $table->unique(['application_id', 'document_type']);
                $table->index(['verification_status', 'verified_at']);
                $table->foreign('application_id')->references('id')->on('student_applications')->cascadeOnDelete();
                $table->foreign('uploaded_by_account_id')->references('id')->on('accounts')->nullOnDelete();
                $table->foreign('verified_by_account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }

        if (Schema::hasTable('application_status_histories')) {
            Schema::table('application_status_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('application_status_histories', 'application_id')) {
                    $table->unsignedBigInteger('application_id')->nullable()->after('id');
                    $table->index(['application_id', 'created_at'], 'app_status_histories_application_created_idx');
                }
                if (!Schema::hasColumn('application_status_histories', 'changed_by_account_id')) {
                    $table->unsignedBigInteger('changed_by_account_id')->nullable()->after('actor_id');
                }
            });
        }

        if (!Schema::hasTable('application_interviews')) {
            Schema::create('application_interviews', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('application_id');
                $table->string('interview_type', 30)->default('interview');
                $table->dateTime('scheduled_at');
                $table->string('location', 255)->nullable();
                $table->string('meeting_link', 255)->nullable();
                $table->string('attendance_status', 30)->default('scheduled');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by_account_id')->nullable();
                $table->timestamps();
                $table->index(['application_id', 'scheduled_at']);
                $table->foreign('application_id')->references('id')->on('student_applications')->cascadeOnDelete();
                $table->foreign('created_by_account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('application_results')) {
            Schema::create('application_results', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('application_id')->unique();
                $table->string('result_status', 30)->default('waiting_list');
                $table->string('announcement_title', 180)->nullable();
                $table->longText('announcement_body')->nullable();
                $table->string('document_path', 255)->nullable();
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('published_by_account_id')->nullable();
                $table->timestamps();
                $table->foreign('application_id')->references('id')->on('student_applications')->cascadeOnDelete();
                $table->foreign('published_by_account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('account_id');
                $table->string('category', 30)->default('system');
                $table->string('type', 30)->default('info');
                $table->string('title', 150);
                $table->text('message')->nullable();
                $table->string('url', 255)->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['account_id', 'is_read', 'created_at']);
                $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('application_results');
        Schema::dropIfExists('application_interviews');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('application_guardians');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('student_applications');
    }
};
