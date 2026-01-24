<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
});

// Export Routes
Route::get('/export/rekap', [ExportController::class, 'rekap']);
Route::get('/export/unpaid', [ExportController::class, 'unpaidList']);

// Broadcast Route (Admin Only)
Route::post('/broadcast', [\App\Http\Controllers\BroadcastController::class, 'send'])->middleware('auth:admin');

// Admin Auth Routes
Route::get('/admin/login', [\App\Http\Controllers\Admin\LoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [\App\Http\Controllers\Admin\LoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [\App\Http\Controllers\Admin\LoginController::class, 'logout'])->name('admin.logout');

// Dashboard Routes (Protected)
Route::middleware('auth:admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/wargas', [\App\Http\Controllers\DashboardController::class, 'wargas'])->name('dashboard.wargas');
    Route::get('/dashboard/transaksi', [\App\Http\Controllers\DashboardController::class, 'transaksi'])->name('dashboard.transaksi');
    Route::get('/dashboard/whatsapp', [\App\Http\Controllers\DashboardController::class, 'whatsapp'])->name('dashboard.whatsapp');
    Route::get('/api/stats', [\App\Http\Controllers\DashboardController::class, 'apiStats']);

    // WA Gateway Routes
    Route::get('/admin/wa/status', [\App\Http\Controllers\Admin\WhatsAppController::class, 'getStatus']);
    Route::get('/admin/wa/qr', [\App\Http\Controllers\Admin\WhatsAppController::class, 'getQr']);
    Route::post('/admin/wa/logout', [\App\Http\Controllers\Admin\WhatsAppController::class, 'logout']);
});
