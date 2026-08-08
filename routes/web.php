<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Verification Portal — no authentication
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicVerificationController::class, 'index'])->name('home');
Route::get('/verify', [PublicVerificationController::class, 'index'])->name('verify');
Route::post('/verify', [PublicVerificationController::class, 'verify'])
    ->middleware('throttle:30,1')
    ->name('verify.submit');
Route::get('/scan', [PublicVerificationController::class, 'scanner'])->name('verify.scanner');
Route::get('/verify/{token}', [PublicVerificationController::class, 'token'])
    ->middleware('throttle:60,1')
    ->name('verify.token');

/*
|--------------------------------------------------------------------------
| Authentication Module
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Registrar — generation, management, analytics
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:registrar'])->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/', [DashboardController::class, 'registrar'])->name('dashboard');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates');
    Route::get('/certificates/generate', [CertificateController::class, 'create'])->name('certificates.generate');
    Route::get('/certificates/batch', [CertificateController::class, 'batch'])->name('certificates.batch');
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');

    Route::get('/students', [CertificateController::class, 'students'])->name('students');
    Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');
    Route::get('/logs', [DashboardController::class, 'logs'])->name('logs');
});

/*
|--------------------------------------------------------------------------
| Students — their own documents
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:student'])->prefix('my')->name('student.')->group(function () {
    Route::get('/', [DashboardController::class, 'student'])->name('dashboard');
    Route::get('/documents', [DashboardController::class, 'documents'])->name('documents');
});

/*
|--------------------------------------------------------------------------
| Document delivery — owner or registrar only
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->name('certificates.download');
    Route::get('/certificates/{certificate}/print', [CertificateController::class, 'print'])
        ->name('certificates.print');
    Route::get('/certificates/{certificate}/qr', [CertificateController::class, 'qr'])
        ->name('certificates.qr');
});