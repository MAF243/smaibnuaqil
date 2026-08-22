<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pages')) {
            return;
        }

        $pages = [
            ['page_key' => 'school_profile', 'title' => 'Profil Sekolah', 'excerpt' => 'Mengenal SMA Ibnu Aqil lebih dekat.', 'content' => "<p>SMA Ibnu Aqil berkomitmen membentuk generasi berilmu, berakhlak, dan siap menghadapi masa depan.</p>"],
            ['page_key' => 'vision_mission', 'title' => 'Visi & Misi', 'excerpt' => 'Arah pengembangan sekolah dan nilai-nilai utama.', 'content' => "<h3>Visi</h3><p>Menjadi sekolah Islam modern yang unggul dalam akademik dan karakter.</p><h3>Misi</h3><ul><li>Menguatkan akhlak dan adab.</li><li>Meningkatkan mutu akademik.</li><li>Mengembangkan literasi digital.</li></ul>"],
            ['page_key' => 'principal_welcome', 'title' => 'Sambutan Kepala Sekolah', 'excerpt' => 'Pesan hangat dari kepala sekolah.', 'content' => "<p>Selamat datang di website resmi SMA Ibnu Aqil. Kami menyambut calon siswa dan orang tua untuk bertumbuh bersama kami.</p>"],
            ['page_key' => 'faq_ppdb', 'title' => 'FAQ PPDB', 'excerpt' => 'Pertanyaan yang sering diajukan seputar PPDB.', 'content' => "<h3>Kapan jadwal PPDB?</h3><p>Silakan cek pengumuman terbaru di portal PPDB.</p><h3>Bagaimana jika berkas belum lengkap?</h3><p>Anda bisa melengkapi berkas sebelum diverifikasi panitia.</p>"],
            ['page_key' => 'contact_social', 'title' => 'Kontak & Sosial Media', 'excerpt' => 'Hubungi sekolah melalui kontak resmi.', 'content' => "<p>Alamat: [Isi alamat sekolah]</p><p>Telepon: [Isi nomor sekolah]</p><p>Email: [Isi email sekolah]</p><p>Instagram: [Isi akun Instagram]</p>"],
            ['page_key' => 'homepage_announcement', 'title' => 'Pengumuman Utama Homepage', 'excerpt' => 'Banner singkat untuk halaman depan.', 'content' => "<p>Pendaftaran peserta didik baru telah dibuka. Klik Portal PPDB untuk memulai pendaftaran.</p>"],
        ];

        foreach ($pages as $page) {
            $exists = DB::table('pages')->where('page_key', $page['page_key'])->exists();
            if (!$exists) {
                DB::table('pages')->insert([
                    'page_key' => $page['page_key'],
                    'title' => $page['title'],
                    'excerpt' => $page['excerpt'],
                    'content' => $page['content'],
                    'is_published' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // no-op seed rollback
    }
};
