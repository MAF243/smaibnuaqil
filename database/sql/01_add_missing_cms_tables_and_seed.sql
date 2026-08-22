-- Tambahan tabel yang belum ada di dump legacy PPDB
-- Import file ini SETELAH 00_original_legacy_dump.sql bila Anda ingin menambahkan fitur CMS website.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `news` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `news_title` varchar(255) DEFAULT NULL,
  `news_content` longtext DEFAULT NULL,
  `news_image_path` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gallery` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `facilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `facility_key` varchar(100) NOT NULL,
  `modal_title` varchar(255) DEFAULT NULL,
  `modal_description` text DEFAULT NULL,
  `modal_image_path` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facilities_facility_key_unique` (`facility_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `site_settings` (`setting_name`, `setting_value`, `created_at`, `updated_at`) VALUES
('hero_title', 'SMA Ibnu\'Aqil', NOW(), NOW()),
('hero_subtitle', 'Serba Bisa, Pasti Bisa SUKSES!', NOW(), NOW()),
('hero_image_path', 'asset/gedunghd.png', NOW(), NOW()),
('map_title', 'Lokasi Sekolah', NOW(), NOW()),
('map_address', 'Jl. Raya Pendidikan - silakan sesuaikan dengan alamat resmi sekolah.', NOW(), NOW()),
('map_iframe_src', '', NOW(), NOW())
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = NOW();

INSERT INTO `news` (`title`, `content`, `image_path`, `news_title`, `news_content`, `news_image_path`, `is_published`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Selamat Datang di Website SMA Ibnu\'Aqil',
         'Website sekolah dan portal PPDB kini telah dimigrasikan ke Laravel. Konten ini adalah data awal yang dapat diganti oleh admin kapan saja melalui panel CMS.',
         'uploads/news_images/news_6827bc0a9b9a6.jpeg',
         'Selamat Datang di Website SMA Ibnu\'Aqil',
         'Website sekolah dan portal PPDB kini telah dimigrasikan ke Laravel. Konten ini adalah data awal yang dapat diganti oleh admin kapan saja melalui panel CMS.',
         'uploads/news_images/news_6827bc0a9b9a6.jpeg',
         1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `news` LIMIT 1);

INSERT INTO `news` (`title`, `content`, `image_path`, `news_title`, `news_content`, `news_image_path`, `is_published`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Portal PPDB Online Sudah Tersedia',
         'Calon peserta didik dapat membuat akun, mengisi formulir bertahap, mengunggah dokumen, lalu memantau status pendaftaran langsung dari dashboard.',
         'uploads/news_images/news_6827c1d598e45.jpeg',
         'Portal PPDB Online Sudah Tersedia',
         'Calon peserta didik dapat membuat akun, mengisi formulir bertahap, mengunggah dokumen, lalu memantau status pendaftaran langsung dari dashboard.',
         'uploads/news_images/news_6827c1d598e45.jpeg',
         1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `news` WHERE `title` = 'Portal PPDB Online Sudah Tersedia');

INSERT INTO `gallery` (`title`, `description`, `image_path`, `is_published`, `uploaded_at`)
SELECT * FROM (
  SELECT 'Kegiatan Sekolah', 'Dokumentasi kegiatan sekolah dan lingkungan belajar.', 'uploads/gallery_images/galeri_68221a77b9b13.png', 1, NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `gallery` LIMIT 1);

INSERT INTO `gallery` (`title`, `description`, `image_path`, `is_published`, `uploaded_at`)
SELECT * FROM (
  SELECT 'Suasana Belajar', 'Contoh data galeri awal yang bisa diubah melalui admin panel.', 'uploads/gallery_images/galeri_68229f4ea3e6c.png', 1, NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `gallery` WHERE `title` = 'Suasana Belajar');

INSERT INTO `gallery` (`title`, `description`, `image_path`, `is_published`, `uploaded_at`)
SELECT * FROM (
  SELECT 'Aktivitas Siswa', 'Tambahkan foto lain melalui menu admin galeri.', 'uploads/gallery_images/galeri_682582b8745cf.jpg', 1, NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `gallery` WHERE `title` = 'Aktivitas Siswa');

INSERT INTO `facilities` (`name`, `short_description`, `icon_class`, `facility_key`, `modal_title`, `modal_description`, `modal_image_path`, `is_published`, `display_order`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Ruang Kelas Nyaman', 'Ruang belajar yang nyaman untuk menunjang proses belajar mengajar.', 'bi bi-easel2', 'ruang-kelas-nyaman', 'Ruang Kelas Nyaman', 'Fasilitas awal ini dibuat otomatis agar halaman website tidak kosong setelah migrasi. Admin dapat mengubah judul, deskripsi, ikon, dan gambar kapan saja.', 'uploads/facilities_images/facility_6827ca7eb69c79.43326030.png', 1, 1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `facilities` WHERE `facility_key` = 'ruang-kelas-nyaman');

INSERT INTO `facilities` (`name`, `short_description`, `icon_class`, `facility_key`, `modal_title`, `modal_description`, `modal_image_path`, `is_published`, `display_order`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Lingkungan Sekolah', 'Area sekolah yang mendukung kegiatan akademik dan pembinaan karakter.', 'bi bi-building', 'lingkungan-sekolah', 'Lingkungan Sekolah', 'Gunakan data ini sebagai placeholder awal sambil melengkapi data fasilitas yang sebenarnya.', 'uploads/facilities_images/facility_6827cb56be7df7.68368965.jpg', 1, 2, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `facilities` WHERE `facility_key` = 'lingkungan-sekolah');

INSERT INTO `facilities` (`name`, `short_description`, `icon_class`, `facility_key`, `modal_title`, `modal_description`, `modal_image_path`, `is_published`, `display_order`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Fasilitas Penunjang', 'Fasilitas penunjang kegiatan siswa dan tenaga pendidik.', 'bi bi-stars', 'fasilitas-penunjang', 'Fasilitas Penunjang', 'Silakan ganti dengan fasilitas riil sekolah seperti laboratorium, perpustakaan, masjid, lapangan, dan lainnya.', 'uploads/facilities_images/facility_6834b676e2a9c3.18610085.jpg', 1, 3, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `facilities` WHERE `facility_key` = 'fasilitas-penunjang');

SET FOREIGN_KEY_CHECKS=1;
