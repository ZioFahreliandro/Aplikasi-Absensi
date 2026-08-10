<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Get list of employees.
     */
    public function getEmployees()
    {
        return response()->json(Employee::orderBy('name')->get());
    }

    /**
     * Store a new employee.
     */
    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'required|string|unique:employees,nip',
            'password' => 'required|string|min:6'
        ]);

        $employee = Employee::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'password' => Hash::make($request->password)
        ]);

        return response()->json($employee, 201);
    }

    /**
     * Update employee.
     */
    public function updateEmployee(Request $request, $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Karyawan tidak ditemukan'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'required|string|unique:employees,nip,' . $id,
            'password' => 'required|string|min:6'
        ]);

        $employee->update([
            'name' => $request->name,
            'nip' => $request->nip,
            'password' => Hash::make($request->password)
        ]);

        return response()->json($employee);
    }

    /**
     * Delete employee.
     */
    public function deleteEmployee($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Karyawan tidak ditemukan'], 404);
        }

        $employee->delete();
        return response()->json(['message' => 'Karyawan berhasil dihapus']);
    }

    /**
     * Get settings.
     */
    public function getSettings()
    {
        $settings = Setting::first();
        if (!$settings) {
            // Re-create default if somehow missing
            $settings = Setting::create([
                'office_name' => 'Kantor Pusat',
                'office_lat' => -6.200000,
                'office_lng' => 106.816666,
                'office_radius' => 100,
                'office_ip' => '127.0.0.1',
                'enable_gps' => false,
                'enable_ip' => false
            ]);
        }
        return response()->json($settings);
    }

    /**
     * Store settings.
     */
    public function storeSettings(Request $request)
    {
        $settings = Setting::first();
        if (!$settings) {
            $settings = new Setting();
        }

        $settings->office_name = $request->input('officeName', 'Kantor Pusat');
        $settings->office_lat = (double) $request->input('officeLat', -6.200000);
        $settings->office_lng = (double) $request->input('officeLng', 106.816666);
        $settings->office_radius = (int) $request->input('officeRadius', 100);
        $settings->office_ip = $request->input('officeIp', '127.0.0.1');
        $settings->enable_gps = (bool) $request->input('enableGps', false);
        $settings->enable_ip = (bool) $request->input('enableIp', false);
        $settings->save();

        return response()->json($settings);
    }

    /**
     * Get attendance records (with monthly filtering).
     */
    public function getAttendance(Request $request)
    {
        $month = $request->query('month'); // YYYY-MM
        
        $query = Attendance::query();

        if ($month) {
            $query->where('date', 'like', $month . '-%');
        }

        // Sort by date desc, then time desc
        $logs = $query->orderBy('date', 'desc')
                      ->orderBy('time', 'desc')
                      ->get();

        return response()->json($logs);
    }

    /**
     * Delete attendance records for today.
     */
    public function deleteTodayAttendance()
    {
        $today = now()->toDateString();
        $deleted = Attendance::where('date', $today)->delete();

        return response()->json(['message' => 'Rekapan absensi hari ini berhasil dihapus', 'deleted' => $deleted]);
    }
}
