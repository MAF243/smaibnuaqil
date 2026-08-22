# ERD V3 Profesional (Ringkas)

## Domain Auth
- accounts
- roles
- permissions
- account_roles
- role_permissions

## Domain PPDB
- student_applications
- student_profiles
- application_guardians
- application_documents
- application_status_histories (extended with `application_id`)
- application_interviews
- application_results
- notifications

## Domain CMS
- media_assets
- news_categories
- news_posts
- pages (extended)
- facilities (extended)
- site_settings (extended)
- homepage_banners

## Domain Audit
- audit_logs

## Relasi inti
```text
accounts ──< account_roles >── roles ──< role_permissions >── permissions
accounts ──< student_applications ──1─ student_profiles
student_applications ──< application_guardians
student_applications ──< application_documents
student_applications ──< application_status_histories
student_applications ──< application_interviews
student_applications ──1─ application_results
accounts ──< notifications

media_assets ──< news_posts
news_categories ──< news_posts
accounts ──< news_posts
accounts ──< audit_logs
```

## Catatan transisi
- tabel legacy `users`, `admin`, `students`, `documents`, `news` tetap dipertahankan
- tabel v3 diisi via sinkronisasi `App\Support\V3Sync`
- command backfill: `php artisan v3:backfill-all`
