<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="auth-page" style="--auth-bg-image: url('{{ asset('uploads/selfies/Bg-image News Detail.png') }}');">
    <main class="auth-shell">
        <section class="auth-card">
            <header class="auth-header">
                <div class="auth-badge">Verifikasi Akun</div>
                <h1 class="auth-title">Lupa Password</h1>
                <p class="auth-copy">Masukkan nomor telepon yang terdaftar untuk menerima kode verifikasi SMS atau WhatsApp.</p>
            </header>

            <div class="auth-body">
                @php
                    $forgotPasswordOtp = session('forgot_password_otp');
                    $forgotPasswordOtpVerified = session('forgot_password_otp_verified');
                @endphp

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('verification_code'))
                    <div class="alert alert-success">
                        Kode verifikasi Anda: <strong>{{ session('verification_code') }}</strong>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($forgotPasswordOtpVerified)
                    <div class="alert alert-success" style="margin-bottom: 1rem;">
                        Kode verifikasi sudah valid. Silakan ubah password Anda.
                    </div>

                    <form method="POST" action="{{ route('password.update-by-phone') }}" class="app-form auth-form">
                        @csrf
                        <input type="hidden" name="phone" value="{{ old('phone', $forgotPasswordOtpVerified['phone'] ?? '') }}">

                        <div class="form-group">
                            <label for="password">Password Baru</label>
                            <div class="password-field">
                                <input type="password" id="password" name="password" class="input-field" placeholder="Password baru" minlength="6" required>
                                <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password Baru</label>
                            <div class="password-field">
                                <input type="password" id="password_confirmation" name="password_confirmation" class="input-field" placeholder="Ulangi password baru" minlength="6" required>
                                <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary auth-submit">
                            Simpan Password Baru
                        </button>
                    </form>
                @elseif ($forgotPasswordOtp)
                    <div class="alert alert-success" style="margin-bottom: 1rem;">
                        Kode verifikasi sudah dibuat dan berlaku sampai {{ \Illuminate\Support\Carbon::createFromTimestamp($forgotPasswordOtp['expires_at'])->timezone(config('app.timezone'))->format('d M Y H:i') }}.
                    </div>

                    <form method="POST" action="{{ route('password.verify-code') }}" class="app-form auth-form">
                        @csrf
                        <input type="hidden" name="phone" value="{{ old('phone', $forgotPasswordOtp['phone'] ?? '') }}">

                        <div class="form-group">
                            <label for="otp">Kode Verifikasi</label>
                            <input type="text" id="otp" name="otp" class="input-field" placeholder="Masukkan 6 digit kode" minlength="6" maxlength="6" inputmode="numeric" required autofocus>
                            <p class="field-hint">Masukkan kode verifikasi yang sudah dikirim ke nomor telepon Anda lewat SMS atau WhatsApp.</p>
                        </div>

                        <button type="submit" class="btn-primary auth-submit">
                            Verifikasi Kode
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('password.send-code') }}" class="app-form auth-form" style="margin-bottom: 1.5rem;">
                        @csrf
                        <div class="form-group">
                            <label for="phone">No Telepon</label>
                            <input type="tel" id="phone" name="phone" class="input-field" placeholder="Masukkan nomor telepon" value="{{ old('phone') }}" required autofocus>
                            <p class="field-hint">Masukkan angka saja tanpa spasi atau tanda baca.</p>
                        </div>

                        <button type="submit" class="btn-primary auth-submit">
                            Kirim Kode Verifikasi
                        </button>
                    </form>
                @endif

                <div class="auth-links">
                    <p class="field-hint" style="margin-bottom: 0.75rem;">
                        Langkah 1: masukkan nomor telepon terdaftar untuk meminta kode verifikasi.
                        Langkah 2: masukkan kode verifikasi yang diterima.
                        Langkah 3: setelah valid, form ubah password akan muncul.
                    </p>
                    <a href="{{ route('login') }}">Kembali ke login</a>
                </div>
            </div>
        </section>
    </main>
    <script src="{{ asset('js/password-toggle.js') }}?v={{ filemtime(public_path('js/password-toggle.js')) }}"></script>
</body>
</html>
