# DentaCare for Laravel (Blade front end)

> **This repository is the front end only** (Blade views, CSS, JS) plus small demo controllers so every screen works. It is not a full Laravel app: copy the files into an existing Laravel 10, 11 or 12 project. Sign-in and data are demo-only.

Copy these into a Laravel 10/11/12 project (PHP 8.2+ for Laravel 12) (merge folders, keep your own composer.json / .env):

- `app/Support/Clinic.php`
- `app/Http/Controllers/{AuthController,PatientController,AdminController}.php`
- `app/Http/Middleware/{BootClinic,EnsureRole}.php` (used directly in routes; no Kernel or `bootstrap/app.php` changes needed)
- `routes/web.php` (replace the default; it defines the `welcome` route)
- `resources/views/*` (layouts, partials, auth, patient, admin, welcome)
- `public/css/dentacare.css`, `public/js/dentacare.js`, `public/uploads/`

Run: `php artisan serve` then open http://127.0.0.1:8000

Notes
- Demo data lives in the session (no database tables needed). Laravel 11/12 default to the `database` session driver, so run `php artisan migrate` once (or set `SESSION_DRIVER=file` in `.env`).
- Routes contain no closures, so `php artisan route:cache` / `optimize` work.
- `public/uploads` must be writable (profile photos).
- Sign-in accepts any valid email and any password (demo only).
- Forms use `@csrf`; validation errors show in a red box at the top of each form.

## Frontend files

The Laravel Blade UI is organized in `resources/views/` (welcome, auth, admin, patient, layouts, and partials). Shared styling is in `public/css/dentacare.css`; the symptom-checker interactions and shared toast behavior are in `public/js/dentacare.js`. These are the frontend files to customize without changing controller logic or routes.
