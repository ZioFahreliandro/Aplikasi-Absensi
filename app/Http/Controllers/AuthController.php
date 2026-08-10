<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
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
            $user = User::firstOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'Admin',
                    'password' => bcrypt($adminPassword),
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

        $user = User::firstOrCreate(
            ['email' => $employee->nip . '@local'],
            [
                'name' => $employee->name,
                'password' => bcrypt($request->password),
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
}
