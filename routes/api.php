<?php

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic')->group(function () {
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::get('/accounts/trial-balance', [AccountController::class, 'trialBalance']);
    Route::get('/accounts/{account}', [AccountController::class, 'show']);
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy']);
    Route::put('/accounts/{account}', [AccountController::class, 'update']);
    Route::get('/accounts/{account}/balance', [AccountController::class, 'balance']);
});