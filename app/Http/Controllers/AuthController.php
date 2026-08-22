<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Services\OtpDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showForgotPasswordForm()
    {
        $session = request()->session();
        $otpSession = $session->get('forgot_password_otp');
        $verifiedSession = $session->get('forgot_password_otp_verified');

        if (($otpSession['expires_at'] ?? 0) < now()->timestamp) {
            $session->forget(['forgot_password_otp', 'forgot_password_otp_verified']);
        }

        if (($verifiedSession['verified_until'] ?? 0) < now()->timestamp) {
            $session->forget('forgot_password_otp_verified');
        }

        return view('auth.forgot-password');
    }

    public function sendForgotPasswordCode(Request $request, OtpDeliveryService $otpDeliveryService): RedirectResponse
    {
        $request->merge([
            'phone' => $this->normalizePhone($request->input('phone')),
        ]);

        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $employee = Employee::where('phone', trim($request->phone))
            ->first();

        if (!$employee) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'Nomor telepon tidak terdaftar.']);
        }

        $code = (string) random_int(100000, 999999);

        if ($this->isTwilioConfigured()) {
            try {
                $otpDeliveryService->send($employee->phone, $code);
            } catch (Throwable $throwable) {
                return back()
                    ->withInput($request->only('phone'))
                    ->withErrors(['phone' => $throwable->getMessage()]);
            }
        }

        $request->session()->put('forgot_password_otp', [
            'phone' => $employee->phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
        ]);
        $request->session()->forget('forgot_password_otp_verified');

        return back()
            ->withInput($request->only('phone'))
            ->with('status', $this->isTwilioConfigured()
                ? 'Kode verifikasi berhasil dikirim ke nomor terdaftar.'
                : 'Mode lokal aktif. Kode verifikasi ditampilkan di halaman untuk testing.')
            ->with('verification_code', $code);
    }

    public function verifyForgotPasswordCode(Request $request): RedirectResponse
    {
        $request->merge([
            'otp' => trim((string) $request->input('otp')),
            'phone' => $this->normalizePhone($request->input('phone')),
        ]);

        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string', 'digits:6'],
        ], [
            'otp.digits' => 'Kode verifikasi harus terdiri dari 6 digit angka.',
        ]);

        $otpSession = $request->session()->get('forgot_password_otp');

        if (!$otpSession || ($otpSession['phone'] ?? null) !== trim($request->phone)) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['otp' => 'Silakan kirim kode verifikasi terlebih dahulu.']);
        }

        if (($otpSession['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget(['forgot_password_otp', 'forgot_password_otp_verified']);

            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['otp' => 'Kode verifikasi sudah kedaluwarsa. Silakan kirim ulang kode.']);
        }

        if (($otpSession['attempts'] ?? 0) >= 5) {
            $request->session()->forget(['forgot_password_otp', 'forgot_password_otp_verified']);

            return back()
                ->withInput($request->only('nip', 'phone'))
                ->withErrors(['otp' => 'Terlalu banyak percobaan. Silakan kirim ulang kode verifikasi.']);
        }

        if (!Hash::check($request->otp, $otpSession['code_hash'] ?? '')) {
            $request->session()->put('forgot_password_otp', array_merge($otpSession, [
                'attempts' => ($otpSession['attempts'] ?? 0) + 1,
            ]));

            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['otp' => 'Kode verifikasi salah.']);
        }

        $request->session()->put('forgot_password_otp_verified', [
            'phone' => $otpSession['phone'],
            'verified_until' => now()->addMinutes(10)->timestamp,
        ]);

        return back()
            ->withInput($request->only('phone'))
            ->with('status', 'Kode verifikasi berhasil. Sekarang kamu bisa mengubah password.');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required|string',
        ]);

        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');

        if ($request->nip === 'admin' && $request->password === $adminPassword) {
            $user = User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'Admin',
                    'password' => $adminPassword,
                    'role' => 'admin',
                ]
            );

            Auth::login($user, true);
            return redirect('/admin');
        }

        $employee = Employee::where('nip', $request->nip)->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return back()->withErrors(['nip' => 'NIP atau password salah.']);
        }

        $user = User::updateOrCreate(
            ['email' => $employee->nip . '@local'],
            [
                'name' => $employee->name,
                'password' => $request->password,
                'role' => 'employee',
            ]
        );

        Auth::login($user, true);

        return redirect('/attendance');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }

    public function resetForgotPassword(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => $this->normalizePhone($request->input('phone')),
        ]);

        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $verifiedSession = $request->session()->get('forgot_password_otp_verified');

        if (!$verifiedSession || ($verifiedSession['phone'] ?? null) !== trim($request->phone)) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'Silakan verifikasi kode terlebih dahulu.']);
        }

        if (($verifiedSession['verified_until'] ?? 0) < now()->timestamp) {
            $request->session()->forget(['forgot_password_otp', 'forgot_password_otp_verified']);

            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'Sesi verifikasi sudah kedaluwarsa. Silakan kirim kode ulang.']);
        }

        $employee = Employee::where('phone', trim($request->phone))
            ->first();

        if (!$employee) {
            $request->session()->forget(['forgot_password_otp', 'forgot_password_otp_verified']);

            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'Nomor telepon tidak terdaftar.']);
        }

        $this->syncEmployeePassword($employee, $request->password);
        $request->session()->forget(['forgot_password_otp', 'forgot_password_otp_verified']);

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil direset. Silakan login kembali.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.current_password' => 'Password lama tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user->password = $request->password;
        $user->save();

        $employee = $this->resolveEmployeeFromUser($user);
        if ($employee) {
            $this->syncEmployeePassword($employee, $request->password);
        }

        $request->session()->regenerate();

        return back()->with('status', 'Password berhasil diperbarui.');
    }

    private function resolveEmployeeFromUser(User $user): ?Employee
    {
        if (!empty($user->email) && str_contains($user->email, '@local')) {
            $nipFromEmail = explode('@', $user->email, 2)[0];
            $employee = Employee::where('nip', $nipFromEmail)->first();

            if ($employee) {
                return $employee;
            }
        }

        if (!empty($user->name)) {
            return Employee::where('name', $user->name)->first();
        }

        return null;
    }

    private function syncEmployeePassword(Employee $employee, string $password): void
    {
        $hashedPassword = Hash::make($password);

        $employee->password = $hashedPassword;
        $employee->save();

        User::updateOrCreate(
            ['email' => $employee->nip . '@local'],
            [
                'name' => $employee->name,
                'password' => $password,
                'role' => 'employee',
            ]
        );
    }

    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    private function isTwilioConfigured(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.from'));
    }
}
