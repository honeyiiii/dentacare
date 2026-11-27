<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
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
        Route::get('/login/{role?}', 'login')->where(['role' => 'admin|patient'])->name('login');
        Route::post('/login/{role?}', 'login')->where(['role' => 'admin|patient'])->middleware('throttle:6,1')->name('login.submit');
        Route::get('/signup/{role?}', 'signup')->where(['role' => 'admin|patient'])->name('signup');
        Route::post('/signup/{role?}', 'signup')->where(['role' => 'admin|patient'])->middleware('throttle:6,1')->name('signup.submit');
        Route::get('/forgot/{role?}', [PasswordResetLinkController::class, 'create'])->where(['role' => 'admin|patient'])->name('forgot');
        Route::post('/forgot/{role?}', [PasswordResetLinkController::class, 'store'])->where(['role' => 'admin|patient'])->middleware('throttle:6,1')->name('password.email');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.store');
        Route::get('/verify-email', EmailVerificationPromptController::class)->middleware('auth')->name('verification.notice');
        Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware(['auth', 'throttle:6,1'])->name('verification.send');
        Route::post('/logout', 'logout')->name('logout');
    });
    Route::get('/patient/avatar', [AvatarController::class, 'patient'])->middleware([EnsureRole::class . ':patient', 'verified'])->name('patient.avatar');
    Route::get('/admin/avatar', [AvatarController::class, 'staff'])->middleware([EnsureRole::class . ':admin', 'verified'])->name('admin.avatar');

    Route::prefix('patient')->name('patient.')->middleware([EnsureRole::class . ':patient', 'verified'])->controller(PatientController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::match(['get', 'post'], 'appointments', 'appointments')->name('appointments');
        Route::match(['get', 'post'], 'book', 'book')->name('book');
        Route::match(['get', 'post'], 'symptoms', 'symptoms')->name('symptoms');
        Route::match(['get', 'post'], 'profile', 'profile')->name('profile');
        Route::post('privacy-requests', 'createPrivacyRequest')->name('privacy-requests.create');
    });

    Route::prefix('admin')->name('admin.')->middleware([EnsureRole::class . ':admin', 'verified'])->controller(AdminController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::match(['get', 'post'], 'calendar', 'calendar')->name('calendar');
        Route::match(['get', 'post'], 'queue', 'queue')->name('queue');
        Route::match(['get', 'post'], 'reminders', 'reminders')->name('reminders');
        Route::get('reports', 'reports')->name('reports');
        Route::get('reports/export', [AdminController::class, 'exportReports'])->name('reports.export');
        Route::get('patients', 'patients')->name('patients');
        Route::get('privacy-requests', 'privacyRequests')->name('privacy-requests');
        Route::post('privacy-requests/{id}', 'updatePrivacyRequest')->name('privacy-requests.update');
        Route::get('staff', 'staff')->name('staff');
        Route::post('staff', 'createStaff')->middleware('throttle:6,1')->name('staff.create');
        Route::patch('staff/{id}/status', 'updateStaffStatus')->name('staff.status');
        Route::match(['get', 'post'], 'profile', 'profile')->name('profile');
        Route::view('privacy', 'admin.privacy')->name('privacy');
    });
});
