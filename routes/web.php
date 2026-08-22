<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PPDBController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\AdminAuthController;

use App\Http\Controllers\Site\NewsController as SiteNewsController;
use App\Http\Controllers\Site\GalleryController as SiteGalleryController;
use App\Http\Controllers\Site\FacilityController as SiteFacilityController;
use App\Http\Controllers\Site\PageController as SitePageController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\FacilityController as AdminFacilityController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;

use App\Http\Middleware\EnsureAdminLoggedIn;
use App\Http\Middleware\EnsureUserLoggedIn;

Route::get('/', [HomeController::class, 'index'])->name('site.home');
Route::get('/portal', [PortalController::class, 'index'])->name('portal');

Route::get('/berita', [SiteNewsController::class, 'index'])->name('site.news.index');
Route::get('/berita/{id}', [SiteNewsController::class, 'show'])->whereNumber('id')->name('site.news.show');
Route::get('/galeri', [SiteGalleryController::class, 'index'])->name('site.gallery.index');
Route::get('/fasilitas', [SiteFacilityController::class, 'index'])->name('site.facilities.index');
Route::get('/profil-sekolah', [SitePageController::class, 'show'])->defaults('pageKey', 'school_profile')->name('site.pages.profile');
Route::get('/visi-misi', [SitePageController::class, 'show'])->defaults('pageKey', 'vision_mission')->name('site.pages.vision');
Route::get('/sambutan-kepala-sekolah', [SitePageController::class, 'show'])->defaults('pageKey', 'principal_welcome')->name('site.pages.principal');
Route::get('/faq-ppdb', [SitePageController::class, 'show'])->defaults('pageKey', 'faq_ppdb')->name('site.pages.faq');
Route::get('/kontak', [SitePageController::class, 'show'])->defaults('pageKey', 'contact_social')->name('site.pages.contact');

Route::get('/login', [UserAuthController::class, 'showLogin'])->name('auth.login.form');
Route::post('/login', [UserAuthController::class, 'login'])->name('auth.login');
Route::get('/register', [UserAuthController::class, 'showRegister'])->name('auth.register.form');
Route::post('/register', [UserAuthController::class, 'register'])->name('auth.register');
Route::post('/logout', [UserAuthController::class, 'logout'])->name('auth.logout');

Route::middleware([EnsureUserLoggedIn::class])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
    Route::post('/dashboard/notifications/{id}/read', [StudentDashboardController::class, 'readNotification'])->whereNumber('id')->name('student.notifications.read');

    Route::get('/ppdb/step-1', [PPDBController::class, 'step1'])->name('ppdb.step1');
    Route::post('/ppdb/step-1', [PPDBController::class, 'step1Store'])->name('ppdb.step1.store');
    Route::get('/ppdb/step-2', [PPDBController::class, 'step2'])->name('ppdb.step2');
    Route::post('/ppdb/step-2', [PPDBController::class, 'step2Store'])->name('ppdb.step2.store');
    Route::get('/ppdb/step-3', [PPDBController::class, 'step3'])->name('ppdb.step3');
    Route::post('/ppdb/step-3', [PPDBController::class, 'step3Store'])->name('ppdb.step3.store');
    Route::get('/ppdb/confirm', [PPDBController::class, 'confirm'])->name('ppdb.confirm');
    Route::post('/ppdb/submit', [PPDBController::class, 'submit'])->name('ppdb.submit');
    Route::get('/ppdb/success', [PPDBController::class, 'success'])->name('ppdb.success');
    Route::get('/ppdb/download/{studentId}/{column}', [PPDBController::class, 'download'])->whereNumber('studentId')->name('ppdb.download');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('auth.login.form');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('auth.login');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('auth.logout');

    Route::middleware([EnsureAdminLoggedIn::class])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->middleware('admin.role:superadmin')->name('audit.index');
        Route::post('/notifications/{id}/read', [AdminNotificationController::class, 'read'])->whereNumber('id')->name('notifications.read');

        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/students/export/csv', [AdminStudentController::class, 'exportCsv'])->name('students.export.csv');
        Route::get('/students/{id}', [AdminStudentController::class, 'show'])->whereNumber('id')->name('students.show');
        Route::post('/students/{id}/status', [AdminStudentController::class, 'updateStatus'])->whereNumber('id')->name('students.status.update');
        Route::post('/students/{id}/verify', [AdminStudentController::class, 'verify'])->whereNumber('id')->name('students.verify');
        Route::post('/students/{id}/reject', [AdminStudentController::class, 'reject'])->whereNumber('id')->name('students.reject');
        Route::post('/students/{id}/interview', [AdminStudentController::class, 'scheduleInterview'])->whereNumber('id')->name('students.interview.schedule');
        Route::post('/students/{id}/interview-attendance', [AdminStudentController::class, 'updateInterviewAttendance'])->whereNumber('id')->name('students.interview.attendance');
        Route::get('/students/{id}/download/{column}', [AdminStudentController::class, 'download'])->whereNumber('id')->name('students.download');

        Route::middleware('admin.role:superadmin,editor,panitia')->group(function () {
            Route::resource('news', AdminNewsController::class)->except(['show']);
            Route::resource('gallery', AdminGalleryController::class)->except(['show']);
            Route::resource('facilities', AdminFacilityController::class)->except(['show']);
            Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
            Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
            Route::delete('/media/{id}', [AdminMediaController::class, 'destroy'])->whereNumber('id')->name('media.destroy');
            Route::get('/pages', [AdminPageController::class, 'index'])->name('pages.index');
            Route::get('/pages/{id}/edit', [AdminPageController::class, 'edit'])->whereNumber('id')->name('pages.edit');
            Route::put('/pages/{id}', [AdminPageController::class, 'update'])->whereNumber('id')->name('pages.update');
        });

        Route::middleware('admin.role:superadmin,editor')->group(function () {
            Route::get('/settings/hero', [SiteSettingController::class, 'hero'])->name('settings.hero');
            Route::post('/settings/hero', [SiteSettingController::class, 'heroUpdate'])->name('settings.hero.update');
            Route::get('/settings/map', [SiteSettingController::class, 'map'])->name('settings.map');
            Route::post('/settings/map', [SiteSettingController::class, 'mapUpdate'])->name('settings.map.update');
        });
    });
});
