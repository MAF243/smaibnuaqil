<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('account_type', 20)->default('student'); // student|staff
                $table->unsignedInteger('legacy_user_id')->nullable()->unique();
                $table->unsignedInteger('legacy_admin_id')->nullable()->unique();
                $table->string('username', 50)->nullable()->unique();
                $table->string('email', 120)->nullable()->unique();
                $table->string('phone', 30)->nullable();
                $table->text('password_hash')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['account_type', 'is_active']);
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code', 100)->unique();
                $table->string('name', 150);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('account_roles')) {
            Schema::create('account_roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();
                $table->unique(['account_id', 'role_id']);
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');
                $table->timestamps();
                $table->unique(['role_id', 'permission_id']);
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            });
        }

        $now = now();
        $roles = [
            ['code' => 'superadmin', 'name' => 'Super Admin'],
            ['code' => 'panitia', 'name' => 'Panitia PPDB'],
            ['code' => 'editor', 'name' => 'Editor Website'],
            ['code' => 'ppdb_operator', 'name' => 'Operator PPDB'],
        ];
        foreach ($roles as $row) {
            DB::table('roles')->updateOrInsert(['code' => $row['code']], ['name' => $row['name'], 'updated_at' => $now, 'created_at' => $now]);
        }

        $permissions = [
            ['code' => 'dashboard.view', 'name' => 'Melihat dashboard'],
            ['code' => 'ppdb.view', 'name' => 'Melihat data PPDB'],
            ['code' => 'ppdb.verify', 'name' => 'Verifikasi PPDB'],
            ['code' => 'ppdb.export', 'name' => 'Export data PPDB'],
            ['code' => 'ppdb.schedule', 'name' => 'Menjadwalkan interview'],
            ['code' => 'cms.news.manage', 'name' => 'Mengelola berita'],
            ['code' => 'cms.gallery.manage', 'name' => 'Mengelola galeri'],
            ['code' => 'cms.facilities.manage', 'name' => 'Mengelola fasilitas'],
            ['code' => 'cms.pages.manage', 'name' => 'Mengelola halaman statis'],
            ['code' => 'settings.manage', 'name' => 'Mengelola pengaturan situs'],
            ['code' => 'audit.view', 'name' => 'Melihat audit log'],
        ];
        foreach ($permissions as $row) {
            DB::table('permissions')->updateOrInsert(['code' => $row['code']], ['name' => $row['name'], 'updated_at' => $now, 'created_at' => $now]);
        }

        $rolePermissions = [
            'superadmin' => ['dashboard.view','ppdb.view','ppdb.verify','ppdb.export','ppdb.schedule','cms.news.manage','cms.gallery.manage','cms.facilities.manage','cms.pages.manage','settings.manage','audit.view'],
            'panitia' => ['dashboard.view','ppdb.view','ppdb.verify','ppdb.export','ppdb.schedule'],
            'editor' => ['dashboard.view','cms.news.manage','cms.gallery.manage','cms.facilities.manage','cms.pages.manage','settings.manage'],
            'ppdb_operator' => ['dashboard.view','ppdb.view','ppdb.verify','ppdb.export','ppdb.schedule'],
        ];
        foreach ($rolePermissions as $roleCode => $codes) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
            if (!$roleId) continue;
            foreach ($codes as $permCode) {
                $permId = DB::table('permissions')->where('code', $permCode)->value('id');
                if (!$permId) continue;
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permId],
                    ['updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('account_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('accounts');
    }
};
