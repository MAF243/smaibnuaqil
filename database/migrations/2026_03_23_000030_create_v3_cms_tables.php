<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('media_assets')) {
            Schema::create('media_assets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('disk', 40)->default('public');
                $table->string('file_path', 255)->unique();
                $table->string('original_name', 255)->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('alt_text', 255)->nullable();
                $table->unsignedBigInteger('uploaded_by_account_id')->nullable();
                $table->timestamps();
                $table->foreign('uploaded_by_account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('news_categories')) {
            Schema::create('news_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 100);
                $table->string('slug', 140)->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('news_posts')) {
            Schema::create('news_posts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('legacy_news_id')->nullable()->unique();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('slug', 180)->unique();
                $table->string('title', 255);
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->unsignedBigInteger('featured_image_id')->nullable();
                $table->string('status', 30)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('published_by_account_id')->nullable();
                $table->unsignedBigInteger('created_by_account_id')->nullable();
                $table->unsignedBigInteger('updated_by_account_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['status', 'published_at']);
                $table->foreign('category_id')->references('id')->on('news_categories')->nullOnDelete();
                $table->foreign('featured_image_id')->references('id')->on('media_assets')->nullOnDelete();
                $table->foreign('published_by_account_id')->references('id')->on('accounts')->nullOnDelete();
                $table->foreign('created_by_account_id')->references('id')->on('accounts')->nullOnDelete();
                $table->foreign('updated_by_account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('homepage_banners')) {
            Schema::create('homepage_banners', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title', 150)->nullable();
                $table->string('subtitle', 255)->nullable();
                $table->string('cta_label', 80)->nullable();
                $table->string('cta_url', 255)->nullable();
                $table->unsignedBigInteger('image_asset_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();
                $table->foreign('image_asset_id')->references('id')->on('media_assets')->nullOnDelete();
            });
        }

        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                if (!Schema::hasColumn('pages', 'slug')) {
                    $table->string('slug', 160)->nullable()->after('page_key');
                }
                if (!Schema::hasColumn('pages', 'status')) {
                    $table->string('status', 30)->default('published')->after('content');
                    $table->index(['status', 'updated_at']);
                }
                if (!Schema::hasColumn('pages', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('pages', 'content_json')) {
                    $table->longText('content_json')->nullable()->after('content');
                }
                if (!Schema::hasColumn('pages', 'created_by_account_id')) {
                    $table->unsignedBigInteger('created_by_account_id')->nullable()->after('is_published');
                }
                if (!Schema::hasColumn('pages', 'updated_by_account_id')) {
                    $table->unsignedBigInteger('updated_by_account_id')->nullable()->after('created_by_account_id');
                }
            });

            DB::table('pages')->whereNull('slug')->orWhere('slug', '')->get()->each(function ($page) {
                DB::table('pages')->where('id', $page->id)->update([
                    'slug' => \Illuminate\Support\Str::slug($page->page_key ?: $page->title ?: ('page-'.$page->id)),
                    'published_at' => $page->created_at ?? now(),
                    'status' => ((int) ($page->is_published ?? 1)) === 1 ? 'published' : 'draft',
                ]);
            });
        }

        if (Schema::hasTable('site_settings')) {
            Schema::table('site_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('site_settings', 'group_name')) {
                    $table->string('group_name', 60)->nullable()->after('setting_value');
                }
                if (!Schema::hasColumn('site_settings', 'updated_by_account_id')) {
                    $table->unsignedBigInteger('updated_by_account_id')->nullable()->after('updated_at');
                }
            });
        }

        if (Schema::hasTable('facilities')) {
            Schema::table('facilities', function (Blueprint $table) {
                if (!Schema::hasColumn('facilities', 'slug')) {
                    $table->string('slug', 160)->nullable()->after('name');
                }
                if (!Schema::hasColumn('facilities', 'content')) {
                    $table->longText('content')->nullable()->after('short_description');
                }
                if (!Schema::hasColumn('facilities', 'image_asset_id')) {
                    $table->unsignedBigInteger('image_asset_id')->nullable()->after('modal_image_path');
                }
                if (!Schema::hasColumn('facilities', 'created_by_account_id')) {
                    $table->unsignedBigInteger('created_by_account_id')->nullable()->after('display_order');
                }
                if (!Schema::hasColumn('facilities', 'updated_by_account_id')) {
                    $table->unsignedBigInteger('updated_by_account_id')->nullable()->after('created_by_account_id');
                }
            });

            DB::table('facilities')->whereNull('slug')->orWhere('slug', '')->get()->each(function ($row) {
                DB::table('facilities')->where('id', $row->id)->update([
                    'slug' => \Illuminate\Support\Str::slug($row->facility_key ?: $row->name ?: ('facility-'.$row->id)),
                    'content' => $row->modal_description ?? $row->short_description,
                ]);
            });
        }

        $categoryId = DB::table('news_categories')->where('slug', 'umum')->value('id');
        if (!$categoryId) {
            DB::table('news_categories')->insert([
                'name' => 'Umum',
                'slug' => 'umum',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_banners');
        Schema::dropIfExists('news_posts');
        Schema::dropIfExists('news_categories');
        Schema::dropIfExists('media_assets');
    }
};
