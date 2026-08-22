# Shadcn/Tailwind UI Patch

Patch ini memperbarui tampilan Laravel SMA Ibnu Aqil agar menggunakan desain berbasis Tailwind CSS dengan visual language bergaya shadcn/ui.

## Catatan teknis

`shadcn/ui` resmi adalah komponen React. Karena project ini adalah Laravel Blade dan akan dihosting di shared hosting/cPanel, patch ini memakai pendekatan yang lebih aman:

- Tailwind CSS via CDN, tanpa proses build Vite/NPM.
- CSS variables gaya shadcn/ui di `public/ui/shadcn.css`.
- Blade components reusable untuk alert, empty-state, status badge, field error, dan loading overlay.
- Lucide icons via CDN.
- Bootstrap tetap dipertahankan hanya sebagai compatibility bridge untuk modal/form lama yang belum seluruhnya dipindah ke native Tailwind.

## File utama yang dipatch

- `resources/views/layouts/site.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/portal.blade.php`
- `resources/views/student/dashboard.blade.php`
- `resources/views/admin/dashboard/index.blade.php`
- `resources/views/ppdb/_stepper.blade.php`
- `resources/views/components/*.blade.php`
- `public/ui/shadcn.css`

## Hosting

Karena Tailwind dipakai via CDN, setelah upload ke hosting tidak perlu menjalankan `npm install` atau `npm run build`.

Command yang disarankan di hosting:

```bash
cd /home/USERNAME/smaibnuaqil
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

Jika server mendukung ekstensi PHP DOM, boleh jalankan:

```bash
php artisan view:cache
```

Jika tidak, skip `view:cache`.
