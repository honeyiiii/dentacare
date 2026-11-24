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


require __DIR__.'/clinical.php';
