<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        if (!config('services.google.client_id') || !config('services.google.client_secret') || !config('services.google.redirect')) {
            return redirect()->route('login')->with('error', 'Kredensial Google OAuth belum disetel. Isi GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_REDIRECT_URL di file .env lalu jalankan php artisan config:clear.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $googleUser = Socialite::driver('google')->user();

        if (! $googleUser || ! $googleUser->getEmail()) {
            return redirect()->route('login')->with('error', 'Tidak dapat mengambil data akun Google Anda.');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->google_id = $googleUser->getId();
            $user->name = $googleUser->getName() ?? $user->name;
            $user->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?? $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'password' => Str::random(40),
                'google_id' => $googleUser->getId(),
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
