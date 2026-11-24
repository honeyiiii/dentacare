# DentaCare

Laravel 12 application with patient registration and clinical record APIs. Patients register with their profile details and can access only their own profile and appointments. Clinic endpoints use the roles `dentist`, `assistant`, `receptionist`, and `owner`. Receptionists, assistants, dentists, and owners can view patient records and medical/dental history; dentists and assistants manage tooth charts, diagnoses, treatments, and progress notes. Only dentists can generate predictions and record prediction decisions.

## Clinical API

After signing in, use `/api/patients/{patient}` endpoints:

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET, POST, PATCH | `/medical-history[/{record}]` | Medical history and dentist-only alert flags |
| GET, POST, PATCH | `/dental-history[/{record}]` | Dental history |
| GET, POST, PATCH | `/tooth-charts[/{tooth_chart}]` | Tooth chart entries (FDI/Universal tooth numbers 1â€“32) |
| GET, POST, PATCH | `/diagnoses[/{diagnosis}]` | Diagnosis records |
| GET, POST, PATCH | `/treatments[/{treatment}]` | Treatment plans and status |
| POST | `/api/treatments/{treatment}/progress-notes` | Add an authored progress note |
| PATCH | `/api/progress-notes/{progressNote}` | Edit a progress note |
| GET, POST | `/predictions` | List or request prediction support (dentist) |
| POST | `/api/predictions/{prediction}/decision` | Record `confirmed` or `overridden` (dentist) |

Entries assign the current user as recorder/author and Laravel timestamps them. Send JSON with an `Accept: application/json` header. Clinical alert flags can only be changed by a dentist. Prediction results always include the `supporting information only` label.

## Prediction service

See [prediction_service/README.md](prediction_service/README.md) for training a scikit-learn model from an approved labeled CSV and running the FastAPI service. No training data or model artifact is bundled. Set `PREDICTION_SERVICE_URL` in Laravel to the service URL; the default is `http://127.0.0.1:8001`. Set `MODEL_PATH` for the Python service to the generated versioned `.joblib` artifact. Laravel records the result, input, probability scores, model version, and dentist review decision.

Run database migrations with `php artisan migrate` after configuring `.env` and installing the Laravel dependencies.

The immutable clinical audit trail can be reviewed by an owner at `GET /api/audit-logs`. Optional filters are `patient_id`, `record_type`, and `event`. Audit change details and clinical free-text fields are encrypted with Laravel's application key. Generate `APP_KEY` before running migrations and back it up securely; rotating it without re-encrypting the records makes those fields unreadable. Patient identity/contact columns remain plaintext so the directory can search and display them; use encrypted database storage and restrict database access before storing real patient records.

Owners manage staff through `/api/staff` (GET/POST), `/api/staff/{user}/role` (PATCH), and `/api/staff/{user}/toggle-active` (PATCH). The last active owner cannot be demoted or deactivated, and dentists with upcoming bookings cannot be deactivated or changed to another role. Inactive users are blocked at login and have existing web sessions revoked on their next request. After migrations, create the first owner interactively with `php artisan clinic:create-owner`; the database seeder does not create a known test account.

Patients register through the normal `/register` screen. Their linked patient profile is created in the same transaction as their account; demographic/contact details can be completed later through `PATCH /api/me/patient-profile`. Appointment/profile API access requires a verified email.

## Appointments API

Appointments are available at `/api/appointments` (send `Accept: application/json` and a valid CSRF token). Owners and receptionists can book for patients; a patient can book only for their own linked profile. Dentists can view and cancel their own appointments, assistants can view schedules, and patients can list or cancel only their own upcoming bookings. A unique database slot claim prevents concurrent bookings for the same dentist, date, and time. Cancelling releases the slot while retaining the appointment and its encrypted audit history.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/appointments` | List appointments (dentists see only their own) |
| GET | `/api/appointments/{appointment}` | View an appointment if permitted |
| POST | `/api/appointments` | Create a booking (owner/receptionist for any patient; patient for self) |
| POST | `/api/appointments/{appointment}/cancel` | Cancel a scheduled booking and release its slot |

Staff create requests require `patient_id`, `dentist_id`, `service`, `appointment_date` (`YYYY-MM-DD`), and `appointment_time` (`HH:MM`). Patient requests omit `patient_id`; the server assigns the authenticated patient profile. `notes` is optional and encrypted at rest. Times use the application timezone. The backend currently does not define clinic opening hours or appointment durations, so configure those rules before using it for live scheduling.
