# SQL Files

## Urutan import yang disarankan

### Opsi A — Memakai dump lama + menambah tabel CMS
1. Import `00_original_legacy_dump.sql`
2. Import `01_add_missing_cms_tables_and_seed.sql`
3. Jalankan `02_normalize_legacy_data.sql`

### Opsi B — Buat database kosong lalu pakai Laravel migration
1. Atur `.env`
2. Jalankan `php artisan migrate`
3. Jalankan `php artisan storage:link`

## Catatan
- Dump asli hanya berisi tabel PPDB legacy: `admin`, `users`, `students`, `documents`.
- Tabel CMS website yang ditambahkan di project Laravel ini adalah:
  - `site_settings`
  - `news`
  - `gallery`
  - `facilities`
- File `02_normalize_legacy_data.sql` berguna karena dump lama masih punya beberapa nilai kosong pada kolom enum, misalnya `admin.role` dan `students.status`.
