# Migration Notes

Project ini digabungkan dari 3 sumber:

1. `smaibnuaqil-laravel.zip`
   - base Laravel project lengkap
2. `ppdb-ui-micro-ux-components-patch-with-readme.zip`
   - patch UI/UX, controllers, views, assets, dan README
3. `smaibnuaqil.zip`
   - source PHP native untuk referensi struktur modul lama dan asset/uploads

## Perbaikan penting yang sudah diterapkan
- route dan controller Laravel digabungkan ke base project
- migration `users` disesuaikan ke skema legacy (`user_id`, `username`, `password`)
- migration `admin` disesuaikan ke SQL asli (`id`, `username`, `password`, `role`)
- ditambahkan migration seed ringan untuk `site_settings`, `news`, `gallery`, `facilities`
- `.env` dan `.env.example` diubah ke default yang lebih aman:
  - `SESSION_DRIVER=file`
  - `CACHE_STORE=file`
  - `QUEUE_CONNECTION=sync`
  - `FILESYSTEM_DISK=public`
- vendor autoload dipatch agar `php artisan` bisa jalan walaupun package phpunit bawaan upload sebelumnya tidak lengkap

## Catatan vendor
Folder `vendor/` berasal dari project Laravel yang diupload, dan sebelumnya memang sempat mengalami masalah dependency PHPUnit. Patch ini sudah cukup agar perintah artisan dasar seperti `php artisan route:list` berjalan.

Jika di mesin lokal masih ada masalah dependency, jalankan ulang:

```bash
composer install
```
