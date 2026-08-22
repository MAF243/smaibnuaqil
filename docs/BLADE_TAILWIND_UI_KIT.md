# Laravel Blade Tailwind UI Kit

Patch ini mengganti pendekatan sebelumnya yang memakai shadcn/ui-style React-like classes menjadi **Blade component library** yang berbasis Tailwind CSS.

## Prinsip

- Tidak memakai React/shadcn package.
- Tidak bergantung pada Bootstrap CSS/JS.
- Komponen UI dibuat sebagai Laravel Blade components di `resources/views/components/ui`.
- Tailwind dijalankan lewat CDN agar tetap aman untuk shared hosting tanpa build step.
- Disediakan compatibility bridge CSS untuk markup lama saat cutover bertahap.

## File utama

```text
resources/views/components/ui/assets.blade.php
resources/views/components/ui/button.blade.php
resources/views/components/ui/card.blade.php
resources/views/components/ui/alert.blade.php
resources/views/components/ui/badge.blade.php
resources/views/components/ui/empty-state.blade.php
resources/views/components/ui/field.blade.php
resources/views/components/ui/textarea.blade.php
resources/views/components/ui/icon.blade.php
resources/views/components/ui/page-header.blade.php
resources/views/components/ui/stat-card.blade.php
public/ui/blade-tailwind.css
```

## Contoh penggunaan

### Button

```blade
<x-ui.button href="/portal" icon="log-in">Portal PPDB</x-ui.button>
<x-ui.button type="submit" variant="secondary">Simpan</x-ui.button>
```

### Card

```blade
<x-ui.card title="Judul" description="Deskripsi singkat">
    Isi card
</x-ui.card>
```

### Form field

```blade
<x-ui.field label="Nama" name="name" :value="$student->name ?? ''" required />
<x-ui.textarea label="Alamat" name="address" :value="$student->address ?? ''" />
```

### Badge status

```blade
<x-ui.badge status="under_review" />
<x-status-badge :status="$student->status_normalized" />
```

## Layout yang sudah memakai UI kit

- `layouts/site.blade.php`
- `layouts/app.blade.php`
- `layouts/admin.blade.php`
- `layouts/guest.blade.php`
- halaman public utama, galeri, fasilitas
- PPDB step 2
- komponen global alert, empty state, badge, field error, loading overlay

## Catatan hosting

Karena Tailwind memakai CDN, tidak wajib menjalankan `npm install` atau `npm run build` di shared hosting.

Command hosting cukup:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

Jika `php artisan view:cache` error karena ekstensi `dom`, skip command tersebut.
