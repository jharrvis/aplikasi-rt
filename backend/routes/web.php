<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
});

// Export Routes
Route::get('/export/rekap', [ExportController::class, 'rekap']);
Route::get('/export/unpaid', [ExportController::class, 'unpaidList']);

// Broadcast Route (Admin Only - add auth middleware in production)
Route::post('/broadcast', [\App\Http\Controllers\BroadcastController::class, 'send']);

// Dashboard Routes
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
Route::get('/dashboard/wargas', [\App\Http\Controllers\DashboardController::class, 'wargas']);
Route::get('/dashboard/transaksi', [\App\Http\Controllers\DashboardController::class, 'transaksi']);
Route::get('/api/stats', [\App\Http\Controllers\DashboardController::class, 'apiStats']);
