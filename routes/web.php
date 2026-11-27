<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Middleware\BootClinic;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::middleware([BootClinic::class])->group(function () {
    Route::view('/', 'landing')->name('welcome');
    Route::view('/home', 'home')->name('home');
    Route::view('/welcome', 'welcome')->name('welcome.page');

    Route::controller(AuthController::class)->group(function () {
        Route::match(['get', 'post'], '/login/{role?}', 'login')->where(['role' => 'admin|patient'])->name('login');
        Route::match(['get', 'post'], '/signup/{role?}', 'signup')->where(['role' => 'admin|patient'])->name('signup');
        Route::get('/forgot/{role?}', [PasswordResetLinkController::class, 'create'])->where(['role' => 'admin|patient'])->name('forgot');
        Route::post('/forgot/{role?}', [PasswordResetLinkController::class, 'store'])->where(['role' => 'admin|patient'])->middleware('throttle:6,1')->name('password.email');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.store');
        Route::post('/logout', 'logout')->name('logout');
    });

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
        Route::get('patients', 'patients')->name('patients');
        Route::match(['get', 'post'], 'profile', 'profile')->name('profile');
        Route::view('privacy', 'admin.privacy')->name('privacy');
    });
});
