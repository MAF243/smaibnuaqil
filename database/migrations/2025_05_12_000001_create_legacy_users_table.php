<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            // Legacy primary key name
            $table->increments('user_id');
            $table->string('username', 50)->unique();
            $table->string('password', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
