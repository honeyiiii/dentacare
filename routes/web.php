<?php
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PatientController;

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ActivityLogController;

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

Route::middleware(['auth', 'role:owner'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::post('/', [StaffController::class, 'store'])->name('store');
    Route::patch('{user}/role', [StaffController::class, 'updateRole'])->name('role');
    Route::patch('{user}/toggle', [StaffController::class, 'toggleActive'])->name('toggle');
});

Route::middleware(['auth', 'role:owner'])->get('audit-logs', [ActivityLogController::class, 'index'])->name('audit.index');

