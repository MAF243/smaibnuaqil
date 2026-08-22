-- Professional PPDB upgrade (additive / non-destructive)
-- Run AFTER:
-- 00_original_legacy_dump.sql
-- 01_add_missing_cms_tables_and_seed.sql
-- 02_normalize_legacy_data.sql

ALTER TABLE admin MODIFY role VARCHAR(30) NOT NULL DEFAULT 'superadmin';
UPDATE admin SET role='superadmin' WHERE role IS NULL OR role='';

ALTER TABLE students
  ADD COLUMN registration_number VARCHAR(30) NULL AFTER id,
  ADD COLUMN submitted_at TIMESTAMP NULL AFTER created_at,
  ADD COLUMN reviewed_at TIMESTAMP NULL AFTER submitted_at,
  ADD COLUMN reviewed_by_admin_id INT UNSIGNED NULL AFTER reviewed_at,
  ADD COLUMN result_note TEXT NULL AFTER rejection_reason,
  ADD COLUMN announcement_published_at TIMESTAMP NULL AFTER result_note,
  ADD COLUMN last_status_note TEXT NULL AFTER announcement_published_at;

ALTER TABLE students ADD UNIQUE KEY students_registration_number_unique (registration_number);
ALTER TABLE students ADD KEY students_reviewed_by_admin_id_index (reviewed_by_admin_id);

CREATE TABLE application_status_histories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  from_status VARCHAR(30) NULL,
  to_status VARCHAR(30) NOT NULL,
  actor_type VARCHAR(20) NOT NULL DEFAULT 'system',
  actor_id INT UNSIGNED NULL,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY application_status_histories_student_created_idx (student_id, created_at),
  CONSTRAINT application_status_histories_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE dashboard_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_type VARCHAR(20) NOT NULL,
  recipient_id INT UNSIGNED NOT NULL,
  type VARCHAR(30) NOT NULL DEFAULT 'info',
  title VARCHAR(150) NOT NULL,
  message TEXT NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,
  KEY dashboard_notifications_recipient_read_idx (recipient_type, recipient_id, is_read),
  KEY dashboard_notifications_recipient_created_idx (recipient_type, recipient_id, created_at)
);

CREATE TABLE interviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  scheduled_at DATETIME NOT NULL,
  location VARCHAR(255) NULL,
  meeting_link VARCHAR(255) NULL,
  attendance_status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
  notes TEXT NULL,
  created_by_admin_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  KEY interviews_student_idx (student_id),
  KEY interviews_scheduled_at_idx (scheduled_at),
  CONSTRAINT interviews_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_key VARCHAR(60) NOT NULL UNIQUE,
  title VARCHAR(150) NOT NULL,
  excerpt TEXT NULL,
  content LONGTEXT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL
);

CREATE TABLE admin_activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  subject_type VARCHAR(100) NULL,
  subject_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NULL,
  properties LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY admin_activity_logs_admin_created_idx (admin_id, created_at),
  KEY admin_activity_logs_subject_idx (subject_type, subject_id)
);

INSERT INTO pages (page_key, title, excerpt, content, is_published, created_at, updated_at)
SELECT * FROM (
  SELECT 'school_profile', 'Profil Sekolah', 'Mengenal SMA Ibnu Aqil lebih dekat.', '<p>SMA Ibnu Aqil berkomitmen membentuk generasi berilmu, berakhlak, dan siap menghadapi masa depan.</p>', 1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_key='school_profile');

INSERT INTO pages (page_key, title, excerpt, content, is_published, created_at, updated_at)
SELECT * FROM (
  SELECT 'vision_mission', 'Visi & Misi', 'Arah pengembangan sekolah dan nilai-nilai utama.', '<h3>Visi</h3><p>Menjadi sekolah Islam modern yang unggul dalam akademik dan karakter.</p><h3>Misi</h3><ul><li>Menguatkan akhlak dan adab.</li><li>Meningkatkan mutu akademik.</li><li>Mengembangkan literasi digital.</li></ul>', 1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_key='vision_mission');

INSERT INTO pages (page_key, title, excerpt, content, is_published, created_at, updated_at)
SELECT * FROM (
  SELECT 'principal_welcome', 'Sambutan Kepala Sekolah', 'Pesan hangat dari kepala sekolah.', '<p>Selamat datang di website resmi SMA Ibnu Aqil. Kami menyambut calon siswa dan orang tua untuk bertumbuh bersama kami.</p>', 1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_key='principal_welcome');

INSERT INTO pages (page_key, title, excerpt, content, is_published, created_at, updated_at)
SELECT * FROM (
  SELECT 'faq_ppdb', 'FAQ PPDB', 'Pertanyaan yang sering diajukan seputar PPDB.', '<h3>Kapan jadwal PPDB?</h3><p>Silakan cek pengumuman terbaru di portal PPDB.</p><h3>Bagaimana jika berkas belum lengkap?</h3><p>Anda bisa melengkapi berkas sebelum diverifikasi panitia.</p>', 1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_key='faq_ppdb');

INSERT INTO pages (page_key, title, excerpt, content, is_published, created_at, updated_at)
SELECT * FROM (
  SELECT 'contact_social', 'Kontak & Sosial Media', 'Hubungi sekolah melalui kontak resmi.', '<p>Alamat: [Isi alamat sekolah]</p><p>Telepon: [Isi nomor sekolah]</p><p>Email: [Isi email sekolah]</p><p>Instagram: [Isi akun Instagram]</p>', 1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_key='contact_social');

INSERT INTO pages (page_key, title, excerpt, content, is_published, created_at, updated_at)
SELECT * FROM (
  SELECT 'homepage_announcement', 'Pengumuman Utama Homepage', 'Banner singkat untuk halaman depan.', '<p>Pendaftaran peserta didik baru telah dibuka. Klik Portal PPDB untuk memulai pendaftaran.</p>', 1, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_key='homepage_announcement');
