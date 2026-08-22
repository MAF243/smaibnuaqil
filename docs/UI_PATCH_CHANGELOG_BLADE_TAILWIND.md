# Changelog UI Patch — Blade Tailwind Library

## Perubahan utama

1. Menambahkan library komponen Tailwind berbasis Laravel Blade.
2. Mengganti layout utama agar tidak lagi memanggil Bootstrap CSS/JS dan tidak memakai shadcn React.
3. Mengganti icon utama dari `data-lucide` menjadi `<x-ui.icon />`.
4. Menambahkan CSS compatibility bridge untuk markup legacy saat cutover bertahap.
5. Mengubah homepage, halaman galeri, fasilitas, admin login, dan PPDB step 2 ke pola UI kit baru.

## Alasan

Project ini adalah Laravel Blade, sehingga lebih tepat memakai komponen Blade reusable daripada membawa konsep React/shadcn secara langsung. Dengan UI kit ini, maintenance lebih mudah karena tombol, card, alert, badge, empty state, field, dan icon punya standar yang sama.
