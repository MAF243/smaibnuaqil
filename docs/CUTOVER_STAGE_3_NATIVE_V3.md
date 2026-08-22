# Cutover Stage 3 — Native v3 Gallery, Facilities, Media, Audit, dan PPDB Legacy Freeze

Tahap ini memindahkan modul CMS media ke fondasi v3 yang lebih bersih, sekaligus menjadikan `audit_logs` sebagai sumber utama audit.

## Perubahan Utama

1. **Gallery native-v3**
   - Admin galeri sekarang memakai tabel `gallery_items`.
   - Gambar galeri disimpan sebagai record `media_assets`.
   - Tabel legacy `gallery` tidak lagi menjadi target write utama.

2. **Facilities native-v3 media**
   - Fasilitas tetap memakai tabel `facilities` yang sudah diperluas v3.
   - Gambar fasilitas sekarang direferensikan melalui `image_asset_id -> media_assets.id`.
   - `modal_image_path` tetap dipertahankan sementara sebagai fallback kompatibilitas.

3. **Media Library v3**
   - Admin mendapat halaman `/admin/media`.
   - Media bisa diupload, dicari berdasarkan koleksi, dan dihapus.

4. **Audit Log primary v3**
   - `AdminActivity::log()` menulis ke `audit_logs` sebagai primary.
   - Legacy `admin_activity_logs` hanya dipakai sebagai mirror opsional atau fallback jika audit v3 gagal.

5. **PPDB legacy mirror OFF secara default**
   - `CUTOVER_PPDB_MIRROR_LEGACY=false`.
   - Data PPDB baru tetap berjalan di tabel v3.
   - Aktifkan kembali sementara jika masih butuh rollback ke tabel `students`.

## Environment Toggle

```env
CUTOVER_PPDB_READ_FROM_V3=true
CUTOVER_PPDB_WRITE_TO_V3=true
CUTOVER_PPDB_MIRROR_LEGACY=false
CUTOVER_CMS_READ_FROM_V3=true
CUTOVER_CMS_WRITE_TO_V3=true
CUTOVER_CMS_MIRROR_LEGACY=false
CUTOVER_AUDIT_PRIMARY_V3=true
CUTOVER_AUDIT_MIRROR_LEGACY=false
```

## Urutan Apply

```bash
composer install
php artisan optimize:clear
php artisan migrate
php artisan v3:backfill-all
php artisan storage:link
php artisan serve
```

Jika tidak memakai migration, import manual:

```text
database/sql/04_cutover_stage3_native_v3.sql
```

## Checklist Uji

- `/admin/media` bisa upload dan hapus media.
- `/admin/gallery` create/update/delete memakai tabel `gallery_items`.
- `/admin/facilities` upload gambar menambah `media_assets` dan mengisi `image_asset_id`.
- `/admin/audit-logs` menampilkan aktivitas terbaru dari `audit_logs`.
- Form PPDB baru tidak lagi wajib membuat/update record legacy `students` ketika `CUTOVER_PPDB_MIRROR_LEGACY=false`.

## Rollback Aman

Jika perlu rollback sementara:

```env
CUTOVER_PPDB_MIRROR_LEGACY=true
CUTOVER_CMS_MIRROR_LEGACY=true
CUTOVER_AUDIT_MIRROR_LEGACY=true
```

Lalu jalankan:

```bash
php artisan optimize:clear
```
