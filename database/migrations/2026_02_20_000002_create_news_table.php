<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news')) {
            return;
        }

        Schema::create('news', function (Blueprint $table) {
            $table->id();

            // canonical columns
            $table->string('title');
            $table->longText('content');
            $table->string('image_path')->nullable();

            // legacy-compat columns (some native files referenced these)
            $table->string('news_title')->nullable();
            $table->longText('news_content')->nullable();
            $table->string('news_image_path')->nullable();

            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
