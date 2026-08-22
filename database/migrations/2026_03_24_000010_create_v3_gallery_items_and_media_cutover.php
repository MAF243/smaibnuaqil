<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('media_assets')) {
            Schema::table('media_assets', function (Blueprint $table) {
                if (!Schema::hasColumn('media_assets', 'collection')) {
                    $table->string('collection', 60)->nullable()->after('file_path');
                }
                if (!Schema::hasColumn('media_assets', 'is_public')) {
                    $table->boolean('is_public')->default(true)->after('alt_text');
                }
            });
        }

        if (!Schema::hasTable('gallery_items')) {
            Schema::create('gallery_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('legacy_gallery_id')->nullable()->unique();
                $table->string('title', 255)->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('image_asset_id')->nullable();
                $table->boolean('is_published')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('created_by_account_id')->nullable();
                $table->unsignedBigInteger('updated_by_account_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_published', 'published_at']);
                $table->index('sort_order');
                $table->foreign('image_asset_id')->references('id')->on('media_assets')->nullOnDelete();
                $table->foreign('created_by_account_id')->references('id')->on('accounts')->nullOnDelete();
                $table->foreign('updated_by_account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }

        if (Schema::hasTable('facilities')) {
            Schema::table('facilities', function (Blueprint $table) {
                if (!Schema::hasColumn('facilities', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('is_published');
                }
            });

            DB::table('facilities')
                ->where('is_published', 1)
                ->whereNull('published_at')
                ->update(['published_at' => DB::raw('COALESCE(created_at, NOW())')]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_items');
    }
};
