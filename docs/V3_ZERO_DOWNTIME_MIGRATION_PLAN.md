# V3 Zero-Downtime Migration Plan

Paket ini menambahkan schema v3 secara additive agar sistem lama tetap berjalan.

## Yang ditambahkan
- auth profesional: `accounts`, `roles`, `permissions`, `account_roles`, `role_permissions`
- PPDB ter-normalisasi: `student_applications`, `student_profiles`, `application_guardians`, `application_documents`, `application_interviews`, `application_results`, `notifications`
- CMS profesional: `media_assets`, `news_categories`, `news_posts`, `homepage_banners`
- audit profesional: `audit_logs`
- sinkronisasi legacy -> v3 lewat `App\Support\V3Sync`
- artisan backfill: `php artisan v3:backfill-all`

## Urutan aman
1. Backup project & database.
2. Jalankan migration additive.
3. Jalankan `php artisan v3:backfill-all`.
4. Uji dashboard user/admin.
5. Untuk konten berita baru, admin panel legacy tetap dipakai tetapi akan sinkron ke `news_posts`.
6. Setelah stabil, frontend publik bisa dipindah penuh membaca tabel v3.

## Catatan konflik nama tabel
Tabel `application_status_histories` lama dipertahankan dan diperluas dengan `application_id` + `changed_by_account_id` agar zero-downtime.
