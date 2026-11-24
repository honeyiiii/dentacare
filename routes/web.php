<?php
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ClinicalAuditController;
use App\Http\Controllers\StaffController;
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

Route::middleware(['auth', 'verified', 'role:receptionist,dentist,assistant,owner', 'can:viewAny,App\\Models\\Patient'])->group(function () {
    Route::resource('patients', PatientController::class)->except(['destroy']);
    Route::delete('patients/{patient}', [PatientController::class, 'destroy'])
        ->name('patients.destroy');
});

Route::prefix('api/appointments')->middleware(['auth', 'verified', 'role:owner,dentist,assistant,receptionist,patient'])->group(function () {
    Route::get('/', [AppointmentController::class, 'index'])
        ->middleware('can:viewAny,App\\Models\\Appointment');
    Route::get('/{appointment}', [AppointmentController::class, 'show'])
        ->middleware('can:view,appointment');
    Route::post('/', [AppointmentController::class, 'store'])
        ->middleware('can:create,App\\Models\\Appointment');
    Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel'])
        ->middleware('can:cancel,appointment');
});

Route::get('api/audit-logs', [ClinicalAuditController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:owner'])
    ->name('audit.index');

Route::prefix('api/me')->middleware(['auth', 'verified', 'role:patient'])->group(function () {
    Route::get('patient-profile', [PatientController::class, 'myProfile']);
    Route::patch('patient-profile', [PatientController::class, 'updateMyProfile']);
});

Route::prefix('api/staff')->middleware(['auth', 'verified', 'role:owner'])->group(function () {
    Route::get('/', [StaffController::class, 'index']);
    Route::post('/', [StaffController::class, 'store']);
    Route::patch('/{user}/role', [StaffController::class, 'updateRole']);
    Route::patch('/{user}/toggle-active', [StaffController::class, 'toggleActive']);
});


require __DIR__.'/clinical.php';
