<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Auth;

// 1. Web main view
Route::get('/', function () {
    return view('index');
})->middleware('auth');

// 2. Authentication routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/logout', [SocialAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/api/settings', [AdminController::class, 'getSettings']);
    Route::post('/api/settings', [AdminController::class, 'storeSettings']);
    Route::get('/api/employees', [AdminController::class, 'getEmployees']);
    Route::post('/api/employees', [AdminController::class, 'storeEmployee']);
    Route::put('/api/employees/{id}', [AdminController::class, 'updateEmployee']);
    Route::delete('/api/employees/{id}', [AdminController::class, 'deleteEmployee']);
    Route::get('/api/attendance', [AdminController::class, 'getAttendance']);
});

// 3. Attendance & Network utility APIs
Route::get('/api/my-ip', [AttendanceController::class, 'myIp']);
Route::post('/api/attendance', [AttendanceController::class, 'store']);
Route::delete('/api/attendance/today', [AdminController::class, 'deleteTodayAttendance']);
