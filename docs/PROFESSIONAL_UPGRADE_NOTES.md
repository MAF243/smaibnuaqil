# Professional Upgrade Notes

Patch ini menambahkan fondasi jangka panjang tanpa merusak struktur lama:

## Fitur baru
- Status PPDB bertahap: `draft`, `submitted`, `under_review`, `interview`, `accepted`, `rejected`, `completed`, `incomplete`
- Timeline status pendaftar
- Nomor pendaftaran otomatis `PPDB-YYYY-XXXX`
- Notifikasi dashboard untuk admin dan pendaftar
- Dashboard admin dengan ringkasan statistik
- Export CSV data pendaftar
- Jadwal interview / tes dan status kehadiran
- Modul pengumuman hasil di dashboard siswa
- Halaman website dinamis: profil, visi misi, sambutan, FAQ, kontak, pengumuman homepage
- Audit log aktivitas admin
- Role admin lebih rapi: `superadmin`, `panitia`, `editor`, `ppdb_operator`

## Cara apply ke database lama
### Pilihan 1 — lewat Laravel migration
1. `php artisan migrate:install`
2. `php artisan migrate`

### Pilihan 2 — lewat SQL manual
Import file:
- `database/sql/03_professional_upgrade_ppdb.sql`

## Catatan kompatibilitas
- Struktur lama tetap dipertahankan agar data native tidak rusak.
- Tabel `students` masih dipakai sebagai tabel inti, tetapi sekarang didukung layer workflow baru.
- Kolom ganda lama pada `news` belum dihapus agar tetap kompatibel dengan source lama.
