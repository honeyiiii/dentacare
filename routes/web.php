<?php
use App\Http\Controllers\PatientController;
use App\Http\Controllers\MedicalHistoryController;
use App\Http\Controllers\DentalHistoryController;
use App\Http\Controllers\ToothChartController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\ProgressNoteController;
use App\Http\Controllers\PredictionController;

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'role:dentist'])->get('/dentist-test', function () {
    return 'Welcome dentist!';
});

Route::middleware(['auth', 'role:receptionist'])->get('/receptionist-test', function () {
    return 'Welcome receptionist!';
});

Route::middleware(['auth', 'role:receptionist,dentist,assistant,owner'])->group(function () {
    Route::resource('patients', PatientController::class)->only(['index', 'create', 'store']);
});

// Clinical records are authenticated JSON endpoints; changes retain their author.
Route::middleware(['auth', 'role:dentist,assistant'])->prefix('api/patients/{patient}')->group(function () {
    Route::get('medical-history', [MedicalHistoryController::class, 'index']);
    Route::post('medical-history', [MedicalHistoryController::class, 'store']);
    Route::patch('medical-history/{record}', [MedicalHistoryController::class, 'update']);
    Route::get('dental-history', [DentalHistoryController::class, 'index']);
    Route::post('dental-history', [DentalHistoryController::class, 'store']);
    Route::patch('dental-history/{record}', [DentalHistoryController::class, 'update']);

    Route::apiResource('tooth-charts', ToothChartController::class)->except(['show']);
    Route::get('diagnoses', [DiagnosisController::class, 'index']);
    Route::post('diagnoses', [DiagnosisController::class, 'store'])->middleware('role:dentist');
    Route::patch('diagnoses/{diagnosis}', [DiagnosisController::class, 'update'])->middleware('role:dentist');
    Route::get('treatments', [TreatmentController::class, 'index']);
    Route::post('treatments', [TreatmentController::class, 'store']);
    Route::patch('treatments/{treatment}', [TreatmentController::class, 'update']);
    Route::post('predictions', [PredictionController::class, 'store'])->middleware('role:dentist');
    Route::get('predictions', [PredictionController::class, 'index'])->middleware('role:dentist');
});
Route::middleware(['auth', 'role:dentist,assistant'])->group(function () {
    Route::post('api/treatments/{treatment}/progress-notes', [ProgressNoteController::class, 'store']);
    Route::patch('api/progress-notes/{progressNote}', [ProgressNoteController::class, 'update']);
    Route::post('api/predictions/{prediction}/decision', [PredictionController::class, 'decide'])->middleware('role:dentist');
});
