<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic')->group(function () {
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/trial-balance', [AccountController::class, 'trialBalance']);
    Route::get('/accounts/{account}/balance', [AccountController::class, 'balance']);
});

Route::middleware('moonshine')->group(function () {
    Route::get('/trial-balance/export', [AccountController::class, 'trialBalance'])
        ->name('admin.trial-balance.export');
});