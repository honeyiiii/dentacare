# DentaCare for Laravel (Blade front end)

> This Laravel app includes a Blade front end and demo clinic workflows. Account credentials and patient/staff roles are stored in the database; clinic appointment data remains demo session data.

Copy these into a Laravel 10/11/12 project (PHP 8.2+ for Laravel 12) (merge folders, keep your own composer.json / .env):

- `app/Support/Clinic.php`
- `app/Http/Controllers/{AuthController,PatientController,AdminController}.php`
- `app/Http/Middleware/{BootClinic,EnsureRole}.php` (used directly in routes; no Kernel or `bootstrap/app.php` changes needed)
- `routes/web.php` (replace the default; it defines the `welcome` route)
- `resources/views/*` (layouts, partials, auth, patient, admin, welcome)
- `public/css/dentacare.css`, `public/js/dentacare.js`, `public/uploads/`

Run: `php artisan serve` then open http://127.0.0.1:8000

Notes
- Run `php artisan migrate` to create the account and patient tables.
- Patient and clinic staff use the same sign-in page. The account role determines which dashboard opens.
- Patient self-registration is open. Staff registration requires setting `CLINIC_STAFF_INVITE_CODE` in `.env`; share that code only with invited staff.
- Routes contain no closures, so `php artisan route:cache` / `optimize` work.
- `public/uploads` must be writable (profile photos).
- Appointment, queue, and reminder data is sample data stored in the session.
- Forms use `@csrf`; validation errors show in a red box at the top of each form.

## Frontend files

The Laravel Blade UI is organized in `resources/views/` (welcome, auth, admin, patient, layouts, and partials). Shared styling is in `public/css/dentacare.css`; the symptom-checker interactions and shared toast behavior are in `public/js/dentacare.js`. These are the frontend files to customize without changing controller logic or routes.
