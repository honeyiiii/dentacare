# DentaCare

Laravel 12 application with patient registration and clinical record APIs. The authenticated clinical endpoints use JSON responses and the existing user roles (`dentist`, `assistant`, `receptionist`, `owner`). Clinical history, charts, diagnoses, treatment, notes, and prediction APIs require a dentist or assistant; prediction generation and dentist decisions are dentist-only.

## Clinical API

After signing in, use `/api/patients/{patient}` endpoints:

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET, POST, PATCH | `/medical-history[/{record}]` | Medical history and dentist-only alert flags |
| GET, POST, PATCH | `/dental-history[/{record}]` | Dental history |
| GET, POST, PATCH, DELETE | `/tooth-charts[/{tooth_chart}]` | Tooth chart entries (FDI/Universal tooth numbers 1–32) |
| GET, POST, PATCH | `/diagnoses[/{diagnosis}]` | Diagnosis records (write access: dentist) |
| GET, POST, PATCH | `/treatments[/{treatment}]` | Treatment plans and status |
| POST | `/api/treatments/{treatment}/progress-notes` | Add an authored progress note |
| PATCH | `/api/progress-notes/{progressNote}` | Edit a progress note |
| GET, POST | `/predictions` | List or request prediction support (dentist) |
| POST | `/api/predictions/{prediction}/decision` | Record `confirmed` or `overridden` (dentist) |

Entries assign the current user as recorder/author and Laravel timestamps them. Send JSON with an `Accept: application/json` header. Clinical alerts cannot be flagged by assistants. Prediction results always include the `supporting information only` label.

## Prediction service

See [prediction_service/README.md](prediction_service/README.md) for training a scikit-learn model from an approved labeled CSV and running the FastAPI service. No training data or model artifact is bundled. Set `PREDICTION_SERVICE_URL` in Laravel to the service URL; the default is `http://127.0.0.1:8001`. Set `MODEL_PATH` for the Python service to the generated versioned `.joblib` artifact. Laravel records the result, input, probability scores, model version, and dentist review decision.

Run database migrations with `php artisan migrate` after configuring `.env` and installing the Laravel dependencies.
