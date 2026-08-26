<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password/code', [AuthController::class, 'sendForgotPasswordCode'])->name('password.send-code');
Route::post('/forgot-password/verify', [AuthController::class, 'verifyForgotPasswordCode'])->name('password.verify-code');
Route::post('/forgot-password', [AuthController::class, 'resetForgotPassword'])->name('password.update-by-phone');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'employee.password.changed'])->group(function () {
    Route::get('/force-password-change', [AuthController::class, 'showForcePasswordChangeForm'])->name('password.force');
    Route::post('/force-password-change', [AuthController::class, 'forcePasswordChange'])->name('password.force.update');
    Route::get('/api/employee/password-status', [AuthController::class, 'employeePasswordStatus'])->name('employee.password.status');

    Route::post('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/attendance', function () {
        return view('index');
    })->name('attendance');

    Route::get('/admin', function () {
        return view('index');
    })->middleware('can:access-admin')->name('admin');

    Route::get('/api/settings', [AdminController::class, 'getSettings']);
    Route::post('/api/attendance', [AttendanceController::class, 'store']);
});

Route::middleware(['auth', 'can:access-admin'])->group(function () {
    Route::post('/api/settings', [AdminController::class, 'storeSettings']);
    Route::get('/api/employees', [AdminController::class, 'getEmployees']);
    Route::post('/api/employees', [AdminController::class, 'storeEmployee']);
    Route::put('/api/employees/{id}', [AdminController::class, 'updateEmployee']);
    Route::post('/api/employees/{id}/reset-password', [AdminController::class, 'resetEmployeePassword']);
    Route::delete('/api/employees/{id}', [AdminController::class, 'deleteEmployee']);
    Route::get('/api/attendance', [AdminController::class, 'getAttendance']);
    Route::delete('/api/attendance/today', [AdminController::class, 'deleteTodayAttendance']);
});
