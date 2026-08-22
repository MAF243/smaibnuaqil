<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_settings')) {
            return;
        }

        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('setting_name', 100)->primary();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['setting_name' => 'hero_title', 'setting_value' => "SMA Ibnu'Aqil"],
            ['setting_name' => 'hero_subtitle', 'setting_value' => 'Serba Bisa, Pasti Bisa SUKSES!'],
            ['setting_name' => 'hero_image_path', 'setting_value' => 'asset/gedunghd.png'],
            ['setting_name' => 'map_title', 'setting_value' => 'Lokasi Sekolah'],
            ['setting_name' => 'map_address', 'setting_value' => "[Alamat Lengkap SMA Ibnu'Aqil]"],
            ['setting_name' => 'map_iframe_src', 'setting_value' => ''],
        ];

        foreach ($defaults as $row) {
            DB::table('site_settings')->updateOrInsert(
                ['setting_name' => $row['setting_name']],
                ['setting_value' => $row['setting_value'], 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
