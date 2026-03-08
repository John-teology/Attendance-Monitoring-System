<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\LicenseController;

use App\Http\Controllers\AdminLookupController;
use App\Http\Controllers\AdminTermsController;

require __DIR__.'/kiosk.php';

// Public Routes
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('debug-user/{code}', function($code) {
    $user = \App\Models\User::where('qr_code', $code)
                ->orWhere('id_number', $code)
                ->orWhere('id', $code)
                ->first();
    return $user ? $user : 'User Not Found';
});

Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Protected Admin Routes
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    
    // Viewer-accessible Routes (Dashboard + Reports)
    Route::middleware(['role:viewer', 'license'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('reports', [AdminLogController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [AdminLogController::class, 'export'])->name('reports.export');
    });

    // Admin Only Routes
    Route::middleware(['role:admin', 'license'])->group(function () {
        Route::post('reports/purge', [AdminLogController::class, 'purge'])->name('reports.purge');
        Route::delete('reports/{id}', [AdminLogController::class, 'destroy'])->name('reports.destroy');

        // Settings (Admin Management)
        Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [AdminSettingsController::class, 'store'])->name('settings.store');
        Route::put('settings/branding', [AdminSettingsController::class, 'updateBranding'])->name('settings.branding');
        Route::post('settings/dummy-data', [AdminSettingsController::class, 'generateDummyData'])->name('settings.dummy-data');
        Route::get('settings/backup-database', [AdminSettingsController::class, 'downloadDatabaseBackup'])->name('settings.backup-database');
        Route::post('settings/restore-database', [AdminSettingsController::class, 'restoreDatabase'])->name('settings.restore-database');
        Route::put('settings/{id}', [AdminSettingsController::class, 'update'])->name('settings.update');
        Route::put('settings/{id}/password', [AdminSettingsController::class, 'updatePassword'])->name('settings.update-password');
        Route::delete('settings/{id}', [AdminSettingsController::class, 'destroy'])->name('settings.destroy');
        
        // Redirect legacy 'logs' to 'reports'
        Route::get('logs', function() {
            return redirect()->route('admin.reports.index');
        });
    });

    // Super Admin Only Routes (License)
    Route::middleware('super_admin')->group(function () {
        Route::get('/license', [LicenseController::class, 'index'])->name('license.index');
        Route::post('/license/bind', [LicenseController::class, 'bind'])->name('license.bind');
        Route::post('/license/rebind', [LicenseController::class, 'rebind'])->name('license.rebind');
        Route::post('/license/unbind', [LicenseController::class, 'unbind'])->name('license.unbind');
        
        // Restore Database (Super Admin Only) - MOVED TO ADMIN ROLE
        // Route::post('settings/restore-database', [AdminSettingsController::class, 'restoreDatabase'])->name('settings.restore-database');
    });

    // Editor & Admin Routes (User Management)
    Route::middleware(['role:editor', 'license'])->group(function () {
        // Data Management (Lookups)
        Route::get('data-management', [AdminLookupController::class, 'index'])->name('lookups.index');
        Route::post('data-management/{type}', [AdminLookupController::class, 'store'])->name('lookups.store');
        Route::put('data-management/{type}/{id}', [AdminLookupController::class, 'update'])->name('lookups.update');
        Route::delete('data-management/{type}/{id}', [AdminLookupController::class, 'destroy'])->name('lookups.destroy');
        Route::post('data-management/{type}/bulk-destroy', [AdminLookupController::class, 'bulkDestroy'])->name('lookups.bulk-destroy');
        Route::post('data-management/{type}/import', [AdminLookupController::class, 'import'])->name('lookups.import');
        Route::get('data-management/{type}/template', [AdminLookupController::class, 'downloadTemplate'])->name('lookups.template');

        Route::get('users/template', [AdminUserController::class, 'downloadTemplate'])->name('users.template');
        Route::post('users/upload-photos', [AdminUserController::class, 'uploadPhotos'])->name('users.upload-photos');
        Route::get('users/export-qr-pdf', [AdminUserController::class, 'exportQrPdf'])->name('users.export-qr-pdf');
        Route::get('users/export', [AdminUserController::class, 'export'])->name('users.export');
        Route::post('users/import', [AdminUserController::class, 'import'])->name('users.import');
        Route::post('users/bulk-destroy', [AdminUserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
Route::get('users/{id}/download-qr', [AdminUserController::class, 'downloadQr'])->name('users.download-qr');
Route::resource('users', AdminUserController::class);

        // Terms Management
        Route::get('terms', [AdminTermsController::class, 'index'])->name('terms.index');
        Route::post('terms', [AdminTermsController::class, 'store'])->name('terms.store');
        Route::put('terms/{id}', [AdminTermsController::class, 'update'])->name('terms.update');
        Route::delete('terms/{id}', [AdminTermsController::class, 'destroy'])->name('terms.destroy');
        Route::post('terms/bulk-destroy', [AdminTermsController::class, 'bulkDestroy'])->name('terms.bulk-destroy');
        Route::get('terms/template', [AdminTermsController::class, 'downloadTemplate'])->name('terms.template');
        Route::post('terms/import', [AdminTermsController::class, 'import'])->name('terms.import');
    });
});
