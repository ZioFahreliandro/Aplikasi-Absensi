<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Get the client's current IP address normalized.
     */
    public function myIp(Request $request)
    {
        return response()->json([
            'ip' => $this->getNormalizedIp($request)
        ]);
    }

    /**
     * Store a new attendance transaction.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $type = $request->input('type');
        $selfie = $request->input('selfie');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        if (!$type || !$selfie) {
            return response()->json([
                'error' => 'Data absensi tidak lengkap (type dan selfie wajib ada)'
            ], 400);
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return response()->json([
                'error' => 'Aktifkan lokasi perangkat dan izinkan akses lokasi terlebih dahulu sebelum absen.'
            ], 400);
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;
        $distance = null;
        $settings = Setting::first();

        if ($settings && $settings->office_lat !== null && $settings->office_lng !== null) {
            $distance = $this->calculateDistance(
                $lat,
                $lng,
                (float) $settings->office_lat,
                (float) $settings->office_lng
            );

            if ($settings->enable_gps && $distance > (int) $settings->office_radius) {
                return response()->json([
                    'error' => 'Anda berada di luar radius kantor. Aktifkan lokasi dan pastikan posisi berada dalam jangkauan yang diizinkan.'
                ], 403);
            }
        }

        $clientIp = $this->getNormalizedIp($request);
        $employee = $this->resolveEmployeeFromUser($user);

        // Process Selfie Image (Base64 data URL to file)
        $selfieUrl = '';
        try {
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $selfie, $matches)) {
                $imageType = $matches[1];
                $imageData = base64_decode($matches[2]);

                $filename = 'selfie-kiosk-' . time() . '.' . $imageType;
                $directory = public_path('uploads/selfies');

                if (!File::isDirectory($directory)) {
                    File::makeDirectory($directory, 0755, true, true);
                }

                $filepath = $directory . '/' . $filename;
                File::put($filepath, $imageData);
                $selfieUrl = 'uploads/selfies/' . $filename;
            } else {
                return response()->json(['error' => 'Format gambar tidak valid'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memproses gambar selfie di server: ' . $e->getMessage()], 500);
        }

        // Samakan waktu rekap dengan jam/tanggal yang tampil di kiosk (WIB).
        $now = Carbon::now('Asia/Jakarta');
        $dateStr = $now->toDateString(); // YYYY-MM-DD
        $timeStr = $now->toTimeString(); // HH:MM:SS
        $attendanceNote = $this->resolveAttendanceNote($type, $settings ?? null, $timeStr);

        $record = Attendance::create([
            'employee_id' => $employee?->id,
            'employee_name' => $employee?->name ?? $user?->name ?? 'Kiosk',
            'date' => $dateStr,
            'time' => $timeStr,
            'type' => $type,
            'selfie_url' => $selfieUrl,
            'latitude' => $lat,
            'longitude' => $lng,
            'distance' => $distance !== null ? round($distance) : null,
            'ip_address' => $clientIp,
            'status' => 'Success',
            'attendance_note' => $attendanceNote
        ]);

        return response()->json([
            'message' => 'Absen ' . ($type === 'masuk' ? 'masuk' : 'pulang') . ' berhasil dicatat!',
            'record' => $record
        ], 201);
    }

    /**
     * Normalize client IP address.
     */
    private function getNormalizedIp(Request $request)
    {
        $ip = $request->ip();
        if ($ip === '::1' || $ip === '::ffff:127.0.0.1') {
            return '127.0.0.1';
        }
        return $ip;
    }

    /**
     * Resolve the current authenticated user to an employee record.
     */
    private function resolveEmployeeFromUser($user): ?Employee
    {
        if (!$user) {
            return null;
        }

        if (($user->role ?? null) === 'employee' && !empty($user->email) && str_contains($user->email, '@local')) {
            $nipFromEmail = explode('@', $user->email, 2)[0];
            $employee = Employee::where('nip', $nipFromEmail)->first();
            if ($employee) {
                return $employee;
            }
        }

        if (!empty($user->name)) {
            $employee = Employee::where('name', $user->name)->first();
            if ($employee) {
                return $employee;
            }
        }

        return null;
    }

    /**
     * Calculate GPS distance using Haversine formula.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // in meters
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    /**
     * Determine whether the attendance should be marked on time or late.
     */
    private function resolveAttendanceNote(string $type, $settings = null, ?string $currentTime = null): string
    {
        $currentTime = $currentTime ?: Carbon::now('Asia/Jakarta')->toTimeString();
        $current = Carbon::createFromFormat('H:i:s', $currentTime, 'Asia/Jakarta');

        $checkinTime = $this->normalizeTimeString($settings?->office_checkin_time ?? '08:00:00');
        $checkoutTime = $this->normalizeTimeString($settings?->office_checkout_time ?? '17:00:00');

        $checkin = Carbon::createFromFormat('H:i:s', $checkinTime, 'Asia/Jakarta');
        $checkout = Carbon::createFromFormat('H:i:s', $checkoutTime, 'Asia/Jakarta');

        if ($type === 'masuk') {
            return $current->gt($checkin) ? 'Telat Masuk' : 'Tepat Waktu';
        }

        if ($type === 'pulang') {
            return $current->lt($checkout) ? 'Pulang Cepat' : 'Tepat Waktu';
        }

        return 'Tepat Waktu';
    }

    /**
     * Normalize time input from settings to H:i:s.
     */
    private function normalizeTimeString(?string $time): string
    {
        $time = trim((string) $time);

        if ($time === '') {
            return '00:00:00';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        return $time;
    }
}
