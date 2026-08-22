<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PPDBController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Middleware\EnsureAdminLoggedIn;
use App\Http\Middleware\EnsureUserLoggedIn;

/*
|--------------------------------------------------------------------------
| Paste this file's content into routes/web.php
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// User auth (legacy users table)
Route::get('/login', [UserAuthController::class, 'showLogin'])->name('auth.login.form');
Route::post('/login', [UserAuthController::class, 'login'])->name('auth.login');
Route::get('/register', [UserAuthController::class, 'showRegister'])->name('auth.register.form');
Route::post('/register', [UserAuthController::class, 'register'])->name('auth.register');
Route::post('/logout', [UserAuthController::class, 'logout'])->name('auth.logout');

Route::middleware([EnsureUserLoggedIn::class])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');

    // PPDB steps
    Route::get('/ppdb/step-1', [PPDBController::class, 'step1'])->name('ppdb.step1');
    Route::post('/ppdb/step-1', [PPDBController::class, 'step1Store'])->name('ppdb.step1.store');

    Route::get('/ppdb/step-2', [PPDBController::class, 'step2'])->name('ppdb.step2');
    Route::post('/ppdb/step-2', [PPDBController::class, 'step2Store'])->name('ppdb.step2.store');

    Route::get('/ppdb/step-3', [PPDBController::class, 'step3'])->name('ppdb.step3');
    Route::post('/ppdb/step-3', [PPDBController::class, 'step3Store'])->name('ppdb.step3.store');

    Route::get('/ppdb/confirm', [PPDBController::class, 'confirm'])->name('ppdb.confirm');
    Route::post('/ppdb/submit', [PPDBController::class, 'submit'])->name('ppdb.submit');
    Route::get('/ppdb/success', [PPDBController::class, 'success'])->name('ppdb.success');

    Route::get('/ppdb/download/{studentId}/{column}', [PPDBController::class, 'download'])
        ->whereNumber('studentId')
        ->name('ppdb.download');
});

// Admin auth (legacy admin table)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('auth.login.form');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('auth.login');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('auth.logout');

    Route::middleware([EnsureAdminLoggedIn::class])->group(function () {
        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/students/{id}', [AdminStudentController::class, 'show'])->whereNumber('id')->name('students.show');
        Route::post('/students/{id}/verify', [AdminStudentController::class, 'verify'])->whereNumber('id')->name('students.verify');
        Route::post('/students/{id}/reject', [AdminStudentController::class, 'reject'])->whereNumber('id')->name('students.reject');
        Route::get('/students/{id}/download/{column}', [AdminStudentController::class, 'download'])
            ->whereNumber('id')
            ->name('students.download');
    });
});
