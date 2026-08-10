<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
        $type = $request->input('type');
        $selfie = $request->input('selfie');

        if (!$type || !$selfie) {
            return response()->json([
                'error' => 'Data absensi tidak lengkap (type dan selfie wajib ada)'
            ], 400);
        }

        $clientIp = $this->getNormalizedIp($request);
        $lat = null;
        $lng = null;
        $distance = null;

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

        $record = Attendance::create([
            'employee_id' => null,
            'employee_name' => 'Kiosk',
            'date' => $dateStr,
            'time' => $timeStr,
            'type' => $type,
            'selfie_url' => $selfieUrl,
            'latitude' => $lat,
            'longitude' => $lng,
            'distance' => $distance !== null ? round($distance) : null,
            'ip_address' => $clientIp,
            'status' => 'Success'
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
}
