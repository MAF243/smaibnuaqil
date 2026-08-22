<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('site_settings')) {
            $settings = [
                'hero_title' => "SMA Ibnu'Aqil",
                'hero_subtitle' => 'Serba Bisa, Pasti Bisa SUKSES!',
                'hero_image_path' => 'asset/gedunghd.png',
                'map_title' => 'Lokasi Sekolah',
                'map_address' => "Jl. Raya Pendidikan - Silakan sesuaikan dengan alamat resmi sekolah.",
                'map_iframe_src' => '',
            ];

            foreach ($settings as $key => $value) {
                DB::table('site_settings')->updateOrInsert(
                    ['setting_name' => $key],
                    ['setting_value' => $value, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        if (Schema::hasTable('admin') && DB::table('admin')->count() === 0) {
            DB::table('admin')->insert([
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'panitia',
            ]);
        }

        if (Schema::hasTable('news') && DB::table('news')->count() === 0) {
            DB::table('news')->insert([
                [
                    'title' => 'Selamat Datang di Website SMA Ibnu\'Aqil',
                    'content' => 'Website sekolah dan portal PPDB kini telah dimigrasikan ke Laravel. Konten ini adalah data awal yang dapat diganti oleh admin kapan saja melalui panel CMS.',
                    'news_title' => 'Selamat Datang di Website SMA Ibnu\'Aqil',
                    'news_content' => 'Website sekolah dan portal PPDB kini telah dimigrasikan ke Laravel. Konten ini adalah data awal yang dapat diganti oleh admin kapan saja melalui panel CMS.',
                    'image_path' => 'uploads/news_images/news_6827bc0a9b9a6.jpeg',
                    'news_image_path' => 'uploads/news_images/news_6827bc0a9b9a6.jpeg',
                    'is_published' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'title' => 'Portal PPDB Online Sudah Tersedia',
                    'content' => 'Calon peserta didik dapat membuat akun, mengisi formulir bertahap, mengunggah dokumen, lalu memantau status pendaftaran langsung dari dashboard.',
                    'news_title' => 'Portal PPDB Online Sudah Tersedia',
                    'news_content' => 'Calon peserta didik dapat membuat akun, mengisi formulir bertahap, mengunggah dokumen, lalu memantau status pendaftaran langsung dari dashboard.',
                    'image_path' => 'uploads/news_images/news_6827c1d598e45.jpeg',
                    'news_image_path' => 'uploads/news_images/news_6827c1d598e45.jpeg',
                    'is_published' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (Schema::hasTable('gallery') && DB::table('gallery')->count() === 0) {
            DB::table('gallery')->insert([
                [
                    'title' => 'Kegiatan Sekolah',
                    'description' => 'Dokumentasi kegiatan sekolah dan lingkungan belajar.',
                    'image_path' => 'uploads/gallery_images/galeri_68221a77b9b13.png',
                    'is_published' => 1,
                    'uploaded_at' => $now,
                ],
                [
                    'title' => 'Suasana Belajar',
                    'description' => 'Contoh data galeri awal yang bisa diubah melalui admin panel.',
                    'image_path' => 'uploads/gallery_images/galeri_68229f4ea3e6c.png',
                    'is_published' => 1,
                    'uploaded_at' => $now,
                ],
                [
                    'title' => 'Aktivitas Siswa',
                    'description' => 'Tambahkan foto lain melalui menu admin galeri.',
                    'image_path' => 'uploads/gallery_images/galeri_682582b8745cf.jpg',
                    'is_published' => 1,
                    'uploaded_at' => $now,
                ],
            ]);
        }

        if (Schema::hasTable('facilities') && DB::table('facilities')->count() === 0) {
            DB::table('facilities')->insert([
                [
                    'name' => 'Ruang Kelas Nyaman',
                    'short_description' => 'Ruang belajar yang nyaman untuk menunjang proses belajar mengajar.',
                    'icon_class' => 'bi bi-easel2',
                    'facility_key' => 'ruang-kelas-nyaman',
                    'modal_title' => 'Ruang Kelas Nyaman',
                    'modal_description' => 'Fasilitas awal ini dibuat otomatis agar halaman website tidak kosong setelah migrasi. Admin dapat mengubah judul, deskripsi, ikon, dan gambar kapan saja.',
                    'modal_image_path' => 'uploads/facilities_images/facility_6827ca7eb69c79.43326030.png',
                    'is_published' => 1,
                    'display_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Lingkungan Sekolah',
                    'short_description' => 'Area sekolah yang mendukung kegiatan akademik dan pembinaan karakter.',
                    'icon_class' => 'bi bi-building',
                    'facility_key' => 'lingkungan-sekolah',
                    'modal_title' => 'Lingkungan Sekolah',
                    'modal_description' => 'Gunakan data ini sebagai placeholder awal sambil melengkapi data fasilitas yang sebenarnya.',
                    'modal_image_path' => 'uploads/facilities_images/facility_6827cb56be7df7.68368965.jpg',
                    'is_published' => 1,
                    'display_order' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Fasilitas Penunjang',
                    'short_description' => 'Fasilitas penunjang kegiatan siswa dan tenaga pendidik.',
                    'icon_class' => 'bi bi-stars',
                    'facility_key' => 'fasilitas-penunjang',
                    'modal_title' => 'Fasilitas Penunjang',
                    'modal_description' => 'Silakan ganti dengan fasilitas riil sekolah seperti laboratorium, perpustakaan, masjid, lapangan, dan lainnya.',
                    'modal_image_path' => 'uploads/facilities_images/facility_6834b676e2a9c3.18610085.jpg',
                    'is_published' => 1,
                    'display_order' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news')) {
            DB::table('news')->whereIn('title', [
                'Selamat Datang di Website SMA Ibnu\'Aqil',
                'Portal PPDB Online Sudah Tersedia',
            ])->delete();
        }

        if (Schema::hasTable('gallery')) {
            DB::table('gallery')->whereIn('image_path', [
                'uploads/gallery_images/galeri_68221a77b9b13.png',
                'uploads/gallery_images/galeri_68229f4ea3e6c.png',
                'uploads/gallery_images/galeri_682582b8745cf.jpg',
            ])->delete();
        }

        if (Schema::hasTable('facilities')) {
            DB::table('facilities')->whereIn('facility_key', [
                'ruang-kelas-nyaman',
                'lingkungan-sekolah',
                'fasilitas-penunjang',
            ])->delete();
        }
    }
};
