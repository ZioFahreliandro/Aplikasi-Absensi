<?php

// Boot Laravel App programmatically
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\Setting;
use App\Models\Attendance;

echo "=== Memulai Verifikasi & Integrasi Laravel DB ===\n";

// 1. Verify Seeded Employees
$employees = Employee::all();
echo "[PASS] Berhasil membaca Karyawan. Jumlah terdaftar: " . $employees->count() . "\n";
if ($employees->count() < 3) {
    echo "[FAIL] Jumlah karyawan kurang dari 3. Seeding gagal.\n";
    exit(1);
}

foreach ($employees as $emp) {
    echo "       - Karyawan: {$emp->name} | NIP: {$emp->nip} | PIN: {$emp->pin}\n";
}

// 2. Verify Seeded Settings
$settings = Setting::first();
if ($settings) {
    echo "[PASS] Berhasil membaca Pengaturan Kantor: \"{$settings->office_name}\"\n";
    echo "       - GPS: Lat: {$settings->office_lat}, Lng: {$settings->office_lng} | Radius: {$settings->office_radius}m\n";
    echo "       - IP Jaringan: {$settings->office_ip}\n";
    echo "       - Batasan GPS: " . ($settings->enable_gps ? 'Aktif' : 'Nonaktif') . "\n";
    echo "       - Batasan IP: " . ($settings->enable_ip ? 'Aktif' : 'Nonaktif') . "\n";
} else {
    echo "[FAIL] Pengaturan kantor tidak ditemukan.\n";
    exit(1);
}

// 3. Test Creating Employee
$testEmp = Employee::create([
    'name' => 'Ahmad Testing',
    'nip' => '20268888',
    'pin' => '9999'
]);
if ($testEmp && $testEmp->id) {
    echo "[PASS] Berhasil membuat Karyawan Baru untuk Pengujian: {$testEmp->name}\n";
} else {
    echo "[FAIL] Gagal membuat karyawan baru.\n";
    exit(1);
}

// 4. Test Creating Attendance Log via Eloquent
$attendance = Attendance::create([
    'employee_id' => $testEmp->id,
    'employee_name' => $testEmp->name,
    'date' => '2026-08-08',
    'time' => '08:30:00',
    'type' => 'masuk',
    'selfie_url' => 'uploads/selfies/test-laravel.jpg',
    'latitude' => -6.200000,
    'longitude' => 106.816666,
    'distance' => 0,
    'ip_address' => '127.0.0.1',
    'status' => 'Success'
]);

if ($attendance && $attendance->id) {
    echo "[PASS] Berhasil mencatat Log Absensi Uji Coba melalui Eloquent.\n";
} else {
    echo "[FAIL] Gagal mencatat absensi.\n";
    exit(1);
}

// Verify relationship works
$attCheck = Attendance::with('employee')->find($attendance->id);
if ($attCheck && $attCheck->employee && $attCheck->employee->name === 'Ahmad Testing') {
    echo "[PASS] Relasi Eloquent (Attendance -> Employee) terverifikasi berjalan sempurna.\n";
} else {
    echo "[FAIL] Relasi Eloquent bermasalah.\n";
    exit(1);
}

// Cleanup Test Data
$attCheck->delete();
$testEmp->delete();
echo "[PASS] Berhasil membersihkan data uji coba.\n";

echo "\n=== Verifikasi Sukses 100%! Aplikasi Laravel Siap Dijalankan. ===\n";
exit(0);
