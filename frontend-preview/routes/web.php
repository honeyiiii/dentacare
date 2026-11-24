<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Middleware\BootClinic;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::middleware([BootClinic::class])->group(function () {
    Route::view('/', 'welcome')->name('welcome');

    Route::controller(AuthController::class)->where(['role' => 'admin|patient'])->group(function () {
        Route::match(['get', 'post'], '/login/{role}', 'login')->name('login');
        Route::match(['get', 'post'], '/signup/{role}', 'signup')->name('signup');
        Route::match(['get', 'post'], '/forgot/{role}', 'forgot')->name('forgot');
    });
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('patient')->name('patient.')->middleware([EnsureRole::class . ':patient'])->controller(PatientController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::match(['get', 'post'], 'appointments', 'appointments')->name('appointments');
        Route::match(['get', 'post'], 'book', 'book')->name('book');
        Route::get('symptoms', 'symptoms')->name('symptoms');
        Route::match(['get', 'post'], 'profile', 'profile')->name('profile');
    });

    Route::prefix('admin')->name('admin.')->middleware([EnsureRole::class . ':admin'])->controller(AdminController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::match(['get', 'post'], 'calendar', 'calendar')->name('calendar');
        Route::match(['get', 'post'], 'queue', 'queue')->name('queue');
        Route::match(['get', 'post'], 'reminders', 'reminders')->name('reminders');
        Route::get('reports', 'reports')->name('reports');
        Route::match(['get', 'post'], 'profile', 'profile')->name('profile');
        Route::view('privacy', 'admin.privacy')->name('privacy');
    });
});
