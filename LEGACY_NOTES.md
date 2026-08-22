# Catatan Migrasi (Legacy → Laravel)

Patch ini dibangun dari SQL dump (PPDB). Saat ZIP PHP native sudah diupload ulang, bagian yang bisa dipindahkan:

## Mapping fitur

### User (calon siswa)
- `register.php`, `login_register.php`, `logout_register.php`, `dashboard_register.php`
  → `UserAuthController` + `StudentDashboardController` + views `resources/views/auth/*`, `resources/views/student/dashboard.blade.php`

### PPDB (multi-step)
- `step1.php` → `PPDBController@step1` + `ppdb/step1.blade.php`
- `step2.php` → `PPDBController@step2` + `ppdb/step2.blade.php`
- `step3.php` → `PPDBController@step3` + `ppdb/step3.blade.php`
- `confirm.php` → `PPDBController@confirm`
- `process.php` → `PPDBController@submit` (bisa ditambah logic validasi kelengkapan)
- `success.php` → `PPDBController@success`

### Admin panel
- `login.php`, `logout.php` → `AdminAuthController`
- `admin_ajax_search_students.php` → filter query di `Admin\StudentController@index`
- `Workspace_student_details.php`/`admin_get_student_details.php` → `Admin\StudentController@show`

## Perubahan penting (best practice)
- Upload file dipindah ke `storage/app/public/ppdb/{student_id}` (akses via `php artisan storage:link`).
- Password legacy: support bcrypt + md5 untuk kompatibilitas.
- Enum legacy yang kadang kosong (`''`) ditangani dengan tipe `string` di migration agar import data tidak error.
