# Cutover Stage 2 - PPDB & CMS

Paket ini melanjutkan migrasi bertahap dari tabel legacy ke schema v3.

## Yang sekarang menjadi primary read
- PPDB student dashboard
- PPDB step form, confirm, success
- Admin dashboard PPDB
- Admin daftar/detail/export pendaftar
- Notifikasi siswa & admin
- CMS berita admin

## Yang sekarang menjadi primary write
- PPDB step 1/2/3
- Submit pendaftaran
- Update status PPDB oleh admin
- Jadwal interview / tes
- CMS berita admin (`news_posts`)

## Yang masih dipertahankan selama masa transisi
- Mirror ke tabel legacy `students`, `documents`, `interviews`, `news`
- Route lama tetap dipakai
- Fallback ke data legacy tetap tersedia bila data v3 belum lengkap

## Toggle penting di `.env`
- `CUTOVER_PPDB_READ_FROM_V3`
- `CUTOVER_PPDB_WRITE_TO_V3`
- `CUTOVER_PPDB_MIRROR_LEGACY`
- `CUTOVER_CMS_READ_FROM_V3`
- `CUTOVER_CMS_WRITE_TO_V3`
- `CUTOVER_CMS_MIRROR_LEGACY`

## Rekomendasi urutan produksi
1. Jalankan migration v3
2. Jalankan backfill `php artisan v3:backfill-all`
3. Aktifkan READ+WRITE v3, mirror legacy tetap ON
4. Verifikasi dashboard/admin/news dari data nyata
5. Setelah stabil, matikan write ke legacy di tahap berikutnya
