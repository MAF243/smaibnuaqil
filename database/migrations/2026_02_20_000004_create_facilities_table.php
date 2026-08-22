<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facilities')) {
            return;
        }

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_description', 500)->nullable();
            $table->string('icon_class', 100)->nullable();
            $table->string('facility_key', 100)->unique();
            $table->string('modal_title')->nullable();
            $table->text('modal_description')->nullable();
            $table->string('modal_image_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
