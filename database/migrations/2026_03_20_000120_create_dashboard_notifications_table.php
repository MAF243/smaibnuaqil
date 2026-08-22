<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashboard_notifications')) {
            return;
        }

        Schema::create('dashboard_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('recipient_type', 20); // user|admin
            $table->unsignedInteger('recipient_id');
            $table->string('type', 30)->default('info');
            $table->string('title', 150);
            $table->text('message')->nullable();
            $table->string('link', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('read_at')->nullable();

            $table->index(['recipient_type', 'recipient_id', 'is_read'], 'dn_recipient_read_idx');
            $table->index(['recipient_type', 'recipient_id', 'created_at'], 'dn_recipient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_notifications');
    }
};
