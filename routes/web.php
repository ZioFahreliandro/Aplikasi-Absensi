<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;

// 1. Web main view
Route::get('/', function () {
    return view('index');
});

// 2. Attendance & Network utility APIs
Route::get('/api/my-ip', [AttendanceController::class, 'myIp']);
Route::post('/api/attendance', [AttendanceController::class, 'store']);

// 3. Admin / Settings APIs
Route::get('/api/settings', [AdminController::class, 'getSettings']);
Route::post('/api/settings', [AdminController::class, 'storeSettings']);

// 4. Admin / Employee CRUD APIs
Route::get('/api/employees', [AdminController::class, 'getEmployees']);
Route::post('/api/employees', [AdminController::class, 'storeEmployee']);
Route::put('/api/employees/{id}', [AdminController::class, 'updateEmployee']);
Route::delete('/api/employees/{id}', [AdminController::class, 'deleteEmployee']);

// 5. Admin / Attendance Logs API
Route::get('/api/attendance', [AdminController::class, 'getAttendance']);
Route::delete('/api/attendance/today', [AdminController::class, 'deleteTodayAttendance']);
