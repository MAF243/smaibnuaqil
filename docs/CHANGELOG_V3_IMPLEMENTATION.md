# Changelog V3 Implementation

## Added
- additive schema v3 for auth, PPDB, CMS, audit
- `App\Support\V3Sync` for legacy to v3 synchronization
- `php artisan v3:backfill-all`
- audit log admin page
- dual-write notifications and audit logs
- dual-sync for PPDB form, status transition, interview schedule, news CRUD
- frontend news pages can read `news_posts` if available

## Kept compatible
- legacy auth (`users`, `admin`)
- legacy PPDB (`students`, `documents`)
- legacy CMS (`news`, `gallery`, `facilities`, `pages`, `site_settings`)

## Notes
- upgrade ini bersifat additive, bukan destructive migration
- cutover penuh ke tabel v3 tetap sebaiknya dilakukan bertahap setelah backfill dan testing

## Stage 3 — Native v3 Cutover

- Admin gallery dipindahkan dari tabel legacy `gallery` ke tabel v3 `gallery_items`.
- Upload gambar gallery sekarang membuat record `media_assets` dengan `collection=gallery`.
- Admin facilities sekarang menyimpan gambar melalui `media_assets` dan `image_asset_id`.
- Ditambahkan halaman admin `/admin/media` sebagai Media Library v3.
- Audit log sekarang menulis ke `audit_logs` sebagai primary melalui `CUTOVER_AUDIT_PRIMARY_V3=true`.
- Mirror legacy untuk PPDB dan CMS dimatikan secara default:
  - `CUTOVER_PPDB_MIRROR_LEGACY=false`
  - `CUTOVER_CMS_MIRROR_LEGACY=false`
- Legacy mirror tetap bisa diaktifkan kembali untuk rollback sementara.
