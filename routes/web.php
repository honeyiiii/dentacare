<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SymptomCheckController;

Route::view('privacy', 'privacy')->middleware('auth')->name('privacy');

Route::get('/', function () {
    return view('home');
});
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Staff only: patient records
Route::middleware(['auth', 'role:receptionist,dentist,assistant,owner'])->group(function () {
    Route::resource('patients', PatientController::class)->only(['index', 'create', 'store']);
});

// Staff and patients: appointments (the controller limits patients to their own)
Route::middleware(['auth', 'role:receptionist,dentist,assistant,owner,patient'])->group(function () {
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
});

Route::middleware(['auth', 'role:owner'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::post('/', [StaffController::class, 'store'])->name('store');
    Route::patch('{user}/role', [StaffController::class, 'updateRole'])->name('role');
    Route::patch('{user}/toggle', [StaffController::class, 'toggleActive'])->name('toggle');
});

Route::middleware(['auth', 'role:owner'])->get('audit-logs', [ActivityLogController::class, 'index'])->name('audit.index');
Route::middleware(['auth', 'role:owner,receptionist,dentist'])->get('reports', [ReportController::class, 'index'])->name('reports.index');

Route::middleware('auth')->group(function () {
    Route::get('symptoms', [SymptomCheckController::class, 'index'])->name('symptoms.index');
    Route::post('symptoms', [SymptomCheckController::class, 'store'])->name('symptoms.store');
});