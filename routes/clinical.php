<?php

use App\Http\Controllers\DentalHistoryController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\MedicalHistoryController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProgressNoteController;
use App\Http\Controllers\ToothChartController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Support\Facades\Route;

/*
| Clinical record endpoints (Tasks 1-3). Included from routes/web.php with:
|     require __DIR__.'/clinical.php';
| JSON clients must send the headers:  Accept: application/json  and  X-CSRF-TOKEN: <token>
*/

// Task 1 (UC-SH-08): receptionist, dental assistant, dentist and clinic owner.
// Only the dentist can flag alerts (enforced in MedicalHistoryController).
Route::middleware(['auth', 'role:owner,dentist,assistant,receptionist'])->prefix('api/patients/{patient}')->group(function () {
    Route::get('medical-alerts', [MedicalHistoryController::class, 'alerts']);

    Route::get('medical-history', [MedicalHistoryController::class, 'index']);
    Route::get('medical-history/{record}', [MedicalHistoryController::class, 'show']);
    Route::post('medical-history', [MedicalHistoryController::class, 'store']);
    Route::patch('medical-history/{record}', [MedicalHistoryController::class, 'update']);

    Route::get('dental-history', [DentalHistoryController::class, 'index']);
    Route::get('dental-history/{record}', [DentalHistoryController::class, 'show']);
    Route::post('dental-history', [DentalHistoryController::class, 'store']);
    Route::patch('dental-history/{record}', [DentalHistoryController::class, 'update']);
});

// Task 2 (UC-SH-09/10/11): dental assistant and dentist. No delete routes: records are preserved.
Route::middleware(['auth', 'role:dentist,assistant'])->group(function () {
    Route::prefix('api/patients/{patient}')->group(function () {
        Route::get('tooth-charts', [ToothChartController::class, 'index']);
        Route::get('tooth-charts/{toothChart}', [ToothChartController::class, 'show']);
        Route::post('tooth-charts', [ToothChartController::class, 'store']);
        Route::patch('tooth-charts/{toothChart}', [ToothChartController::class, 'update']);

        Route::get('diagnoses', [DiagnosisController::class, 'index']);
        Route::get('diagnoses/{diagnosis}', [DiagnosisController::class, 'show']);
        Route::post('diagnoses', [DiagnosisController::class, 'store']);
        Route::patch('diagnoses/{diagnosis}', [DiagnosisController::class, 'update']);

        Route::get('treatments', [TreatmentController::class, 'index']);
        Route::get('treatments/{treatment}', [TreatmentController::class, 'show']);
        Route::post('treatments', [TreatmentController::class, 'store']);
        Route::patch('treatments/{treatment}', [TreatmentController::class, 'update']);
    });

    Route::get('api/treatments/{treatment}/progress-notes', [ProgressNoteController::class, 'index']);
    Route::post('api/treatments/{treatment}/progress-notes', [ProgressNoteController::class, 'store']);
    Route::get('api/progress-notes/{progressNote}', [ProgressNoteController::class, 'show']);
    Route::patch('api/progress-notes/{progressNote}', [ProgressNoteController::class, 'update']);
});

// Task 3 (UC-DN-02, UC-IN-01): licensed dentist only.
Route::middleware(['auth', 'role:dentist'])->group(function () {
    Route::get('api/patients/{patient}/predictions', [PredictionController::class, 'index']);
    Route::post('api/patients/{patient}/predictions', [PredictionController::class, 'store']);
    Route::post('api/predictions/{prediction}/decision', [PredictionController::class, 'decide']);
});
