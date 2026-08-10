<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk dengan Google | AbsenGo</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="auth-header">
                <span class="auth-badge">Aplikasi Absensi</span>
                <h1 class="auth-title">Masuk dengan Google</h1>
                <p class="auth-copy">Gunakan akun Google kantor atau akun kerja yang terdaftar di sistem ini.</p>
            </div>

            <div class="auth-body">
                <div class="auth-info">
                    <p>Jika belum tahu akun mana, gunakan email resmi perusahaan atau tanyakan admin untuk pendaftaran.</p>
                </div>

                @if(session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                <a href="{{ route('auth.google.redirect') }}" class="google-button">
                    <span class="google-icon-wrapper" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 46 46" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23 9.5c3.88 0 7.14 1.31 9.6 3.47l7.14-7.14C37.15 2.7 30.5 0 23 0 14.08 0 6.65 4.58 2.77 11.26l8.21 6.36C12.8 13.03 17.46 9.5 23 9.5Z" fill="#4285F4"/>
                            <path d="M42.23 20.98c0-1.53-.14-2.99-.4-4.4H23v8.35h11.58c-.5 2.7-1.93 4.98-4.13 6.53l6.38 4.96c3.72-3.43 5.9-8.5 5.9-15.44Z" fill="#34A853"/>
                            <path d="M10.98 28.05c-.5-1.5-.8-3.09-.8-4.74 0-1.65.3-3.24.8-4.74L2.77 12.21C1.01 15.76 0 19.79 0 23.96c0 4.17 1.01 8.2 2.77 11.75l8.21-6.66Z" fill="#FBBC05"/>
                            <path d="M23 45.9c6.5 0 12.04-2.14 16.07-5.8l-7.65-5.95c-2.03 1.36-4.64 2.15-7.14 2.15-5.54 0-10.2-3.53-11.84-8.28L2.77 34.9C6.65 41.58 14.08 46 23 46v-.1Z" fill="#EA4335"/>
                        </svg>
                    </span>
                    <span>Masuk dengan Google</span>
                </a>

                <div class="auth-footer">
                    <p>Tidak ingin login sekarang? <a href="{{ url('/') }}">Kembali ke Beranda</a></p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
