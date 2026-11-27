# DentaCare

DentaCare is a Laravel application with a Blade frontend and database-backed clinic workflows.

## Run locally

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open `http://127.0.0.1:8000`.

The seeder creates demo users with password `password123`:

- Staff: `owner@dentacare.test` (Front desk)
- Dentist: `dentist@dentacare.test`
- Patient: `patient@dentacare.test`

Public sign-up creates patient accounts only. An authorized front-desk staff member provisions staff accounts from **Staff**, and the new staff member sets their password using the reset link.

## Application behavior

- Appointments, queue entries, reminders, symptom checks, and privacy requests are persisted in the database.
- Patient routes use the authenticated patient's own record. Dentist views are limited to assigned patients/appointments; front-desk staff can access clinic-wide workflows.
- Booking slots use a unique dentist/date/time key to prevent double booking. Appointments can be cancelled only while booked and future; completion occurs through the queue.
- Symptom checks are validated and stored as JSON arrays. Results are predictions, not diagnoses.
- Avatar uploads accept JPG/PNG up to 2 MB and are stored on Laravel's private local disk. Authorized routes serve the images.
- Audit records store actor, action, and record identifiers, without copying patient names or contact details into the log.
- A completed deletion request removes the patient's login, anonymizes the patient record, removes symptom checks and photos, and clears free-text appointment notes and request text. Appointment aggregates remain for clinic history.
- Reports can be filtered by date and exported by staff. Dentists see only their own appointment records.

## Email and production configuration

The default local mailer is `log`; password-reset emails are written to `storage/logs/laravel.log`, and appointment reminders are marked **Logged only**. They are not delivered. Configure a real mail transport in `.env` (`MAIL_MAILER=smtp`, host, port, username, password, and sender) before relying on delivery. SMS reminders are not integrated.

For production, set `APP_ENV=production`, `APP_DEBUG=false`, use an HTTPS `APP_URL`, configure a real mail provider and trusted HTTPS proxy settings as needed, and set `SESSION_SECURE_COOKIE=true`. Keep credentials and application secrets in environment configuration, not source control. Back up the database and private storage securely.

## Tests

Run `php artisan test`. The focused clinic workflow tests are in `tests/Feature/ClinicWorkflowTest.php`.
