# 🎓 SMA Ibnu Aqil – PPDB & Website CMS (Laravel)

> **Modern PPDB Portal + School Website CMS** — Sistem PPDB multi-step dan website sekolah yang aman, rapi, dan mudah dirawat (migrasi dari PHP native ke Laravel).

[![License](https://img.shields.io/badge/license-Proprietary-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.2-8892bf.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-12-ff2d20.svg)](https://laravel.com/)
[![MySQL](https://img.shields.io/badge/mysql-%3E%3D8.0-orange.svg)](https://www.mysql.com/)

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Unggulan](#-fitur-unggulan)
- [Teknologi](#-teknologi)
- [Struktur Folder](#-struktur-folder)
- [Arsitektur Database](#-arsitektur-database)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Penggunaan](#-penggunaan)
- [Panduan Admin](#-panduan-admin)
- [UI Kit & Komponen Standar](#-ui-kit--komponen-standar)
- [Troubleshooting](#-troubleshooting)
- [Lisensi](#-lisensi)

---

## 🎯 Tentang Proyek

Project ini adalah hasil **migrasi penuh** dari aplikasi **PHP native** menjadi aplikasi **Laravel** dengan tujuan:

- ✅ Arsitektur lebih rapi (Controller/Model/View)
- ✅ Keamanan meningkat (CSRF, validasi server-side, session handling)
- ✅ Upload file lebih aman & terstruktur
- ✅ UI/UX lebih profesional (public site, PPDB portal, admin panel)
- ✅ Maintenance lebih mudah (komponen standar, helper class, konsistensi icon & button)

> *“Sistem PPDB itu bukan hanya form — tapi alur pendaftaran yang harus jelas, minim error, dan mudah dipantau panitia.”*

---

## ✨ Fitur Unggulan

### 🌐 Website Publik
- Homepage modern (Hero, fasilitas, berita terbaru, galeri, map)
- Halaman Berita (list + detail)
- Galeri (grid + modal preview)
- Fasilitas (grid + modal detail)
- Portal PPDB sebagai entry point

### 🧾 Portal PPDB (User)
- Register/Login user
- Dashboard user modern (ringkasan status + quick action)
- PPDB multi-step:
  - Step 1: Data siswa
  - Step 2: Data orang tua/wali (UX lebih ringkas)
  - Step 3: Upload berkas (KK/Akta/Rapor/KTP) dengan indikator kelengkapan
  - Confirm & Submit
- Upload file aman + terstruktur per pendaftar

### 🛡️ Admin Panel
- Login admin
- Manajemen pendaftar:
  - list + search/filter
  - detail pendaftar
  - verify / reject (dengan alasan)
  - download berkas
- CMS:
  - Berita (CRUD + upload cover)
  - Galeri (CRUD + upload image)
  - Fasilitas (CRUD + upload image)
- Pengaturan:
  - Hero section
  - Google map settings

### 🧩 Micro UX (Detail UI/UX)
- Empty state yang konsisten (data kosong tetap “enak dilihat”)
- Loading overlay & tombol auto-disable saat submit
- Button hierarchy konsisten (primary/secondary/danger)
- Icon konsisten (Bootstrap Icons)
- Flash message & error per field yang rapi

---

## 🛠 Teknologi

### Backend
- **PHP** >= 8.2 (recommended 8.3)
- **Laravel** 12
- **MySQL** >= 8.0
- **Blade** Templates

### Frontend
- **Bootstrap 5**
- **Bootstrap Icons**
- Custom UI Theme:
  - `public/ui/theme.css`
  - `public/ui/app.js`

---

## 📁 Struktur Folder

```text
app/
  Http/
    Controllers/
      Admin/...
      Auth/...
      Ppdb/...
    Middleware/...
  Models/...

resources/
  views/
    layouts/
      site.blade.php
      app.blade.php
      admin.blade.php
    components/
      alert.blade.php
      field-error.blade.php
      status-badge.blade.php
      empty-state.blade.php
      loading-overlay.blade.php
      flash.blade.php
    site/
    portal/
    admin/

public/
  ui/
    theme.css
    app.js
  uploads/          (legacy copy - opsional)

storage/
  app/public/       (upload Laravel)
```

---

## 🗄 Arsitektur Database

### 📐 Entitas Utama

- `users` → akun pendaftar (portal)
- `admin` → akun admin/panitia
- `students` → data pendaftaran (inti PPDB)
- `documents` → daftar berkas upload per pendaftar
- `news` → konten berita
- `gallery` → konten galeri
- `facilities` → konten fasilitas
- `site_settings` → hero + map + setting website

### 🧭 Sketsa Relasi (ringkas)

```text
users (1) ──── (many) students
students (1) ── (many) documents
admin → manage students/news/gallery/facilities/site_settings
```

### 🧠 Filosofi: Upload harus “terstruktur & bisa diaudit”
Upload disimpan rapi di:

```text
storage/app/public/ppdb/{student_id}/...
```

Dengan begitu:
- 1 pendaftar = 1 folder berkas
- mudah backup/restore
- mudah verifikasi oleh admin

---

## 🚀 Instalasi

### Prasyarat
- PHP >= 8.2 (disarankan 8.3)
- Composer terbaru
- MySQL >= 8.0
- (Opsional) Laragon / XAMPP untuk Windows

### Langkah Instalasi

1) **Install dependencies**
```bash
composer install
```

2) **Copy environment file**
```bash
cp .env.example .env
php artisan key:generate
```

3) **Konfigurasi database** di `.env` (contoh)
```env
APP_NAME="SMA Ibnu Aqil"
APP_ENV=local
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=goaa7147_sma_ibnu_aqil
DB_USERNAME=root
DB_PASSWORD=
```

4) **Migrasi database**
```bash
php artisan migrate
```

5) **Aktifkan storage link untuk akses file upload**
```bash
php artisan storage:link
```

6) **Jalankan server**
```bash
php artisan serve
```

---

## ⚙️ Konfigurasi

### Storage & Upload
Pastikan folder storage bisa ditulis (Linux/VPS):
```bash
chmod -R 775 storage bootstrap/cache
```

### Import SQL lama (opsional)
Jika kamu ingin impor data dari dump lama:
```bash
mysql -u root -p goaa7147_sma_ibnu_aqil < goaa7147_sma_ibnu_aqil.sql
```

> Setelah import SQL, jalankan kembali `php artisan migrate` (aman jika migration sudah dibuat cek tabel).

---

## 💻 Penggunaan

### URL Penting
- **Website**: `/`
- **Portal PPDB**: `/portal`
- **Login User**: `/login`
- **Dashboard User**: `/dashboard`
- **PPDB Step 1**: `/ppdb/step-1`

### Admin
- **Admin Login**: `/admin/login`
- **Pendaftar**: `/admin/students`
- **Berita**: `/admin/news`
- **Galeri**: `/admin/gallery`
- **Fasilitas**: `/admin/facilities`
- **Settings Hero**: `/admin/settings/hero`
- **Settings Map**: `/admin/settings/map`

---

## 🧑‍💼 Panduan Admin

### Workflow Verifikasi PPDB
1. Login admin → `/admin/login`
2. Buka daftar pendaftar → `/admin/students`
3. Klik detail pendaftar
4. Cek kelengkapan berkas
5. Pilih tindakan:
   - **Verify** → status diset *verified*
   - **Reject** → isi alasan → status diset *rejected*
6. Download berkas bila perlu untuk arsip internal

> Status badge dibuat konsisten dan mudah terbaca.

---

## 🧩 UI Kit & Komponen Standar

Project ini memakai standar komponen Blade supaya **layout konsisten dan hemat waktu**.

### 1) Flash Message
Gunakan:
```blade
<x-flash />
```

### 2) Alert
```blade
<x-alert type="success" title="Berhasil" message="Data tersimpan." />
<x-alert type="danger" title="Gagal" message="Terjadi kesalahan." />
```

### 3) Error per Field (Form)
```blade
<input name="nama" class="form-control @error('nama') is-invalid @enderror" />
<x-field-error name="nama" />
```

### 4) Status Badge
```blade
<x-status-badge :status="$student->status" />
```

### 5) Empty State
```blade
<x-empty-state
  title="Belum ada data"
  message="Data akan muncul setelah ada pendaftar."
  icon="bi-inbox"
/>
```

### 6) Loading Overlay & Button Disable
Untuk form submit:
- Tambahkan attribute:
```html
<form method="POST" data-loading="true">
```
Atau tombol:
```html
<button class="btn btn-brand" data-loading="true">Simpan</button>
```

JS akan:
- disable tombol
- tampilkan overlay loading
- cegah double submit

### Theme & Helper Class
- CSS: `public/ui/theme.css`
- JS: `public/ui/app.js`

---

## 🔧 Troubleshooting

### 1) Composer / vendor corrupt (Windows)
Gejala: class hilang (mis. `sebastian/version`), artisan error.

Solusi:
```bash
rm -rf vendor composer.lock
composer clear-cache
composer install
```

### 2) Upload tidak tampil
Pastikan:
```bash
php artisan storage:link
```
Lalu akses file via `/storage/...` bukan langsung `storage/app/...`.

### 3) Error 419 (Page Expired / CSRF)
Pastikan form Blade punya:
```blade
@csrf
```

### 4) CSS/JS tidak kebaca
Pastikan file ada:
- `/public/ui/theme.css`
- `/public/ui/app.js`

Cek langsung:
- `http://localhost/ui/theme.css`

### 5) Permission issue (VPS)
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 📄 Lisensi

Proyek ini bersifat **private/internal**. Penggunaan komersial atau redistribusi mengikuti kebijakan pemilik proyek.


---

## 🧱 Upgrade V3 (Profesional Jangka Panjang)

Project ini sekarang sudah memiliki fondasi migrasi v3 additive untuk skema yang lebih profesional tanpa mematikan sistem legacy yang sudah jalan.

### Tabel v3 baru
- `accounts`, `roles`, `permissions`, `account_roles`, `role_permissions`
- `student_applications`, `student_profiles`, `application_guardians`, `application_documents`, `application_interviews`, `application_results`, `notifications`
- `media_assets`, `news_categories`, `news_posts`, `homepage_banners`
- `audit_logs`

### Perintah backfill
```bash
php artisan migrate
php artisan v3:backfill-all
```

### Dokumen referensi
- `docs/ERD_V3_PROFESSIONAL.md`
- `docs/V3_ZERO_DOWNTIME_MIGRATION_PLAN.md`

---

## 🎨 UI Patch Terbaru: Laravel Blade Tailwind UI Kit

Versi terbaru memakai **Tailwind CSS berbasis Laravel Blade components**, bukan React/shadcn dan bukan Bootstrap UI framework. Komponen reusable tersedia di:

```text
resources/views/components/ui/
public/ui/blade-tailwind.css
```

Dokumentasi lengkap ada di:

```text
docs/BLADE_TAILWIND_UI_KIT.md
docs/UI_PATCH_CHANGELOG_BLADE_TAILWIND.md
```

Untuk shared hosting, UI ini tetap aman karena Tailwind dijalankan lewat CDN dan tidak wajib `npm run build`.
