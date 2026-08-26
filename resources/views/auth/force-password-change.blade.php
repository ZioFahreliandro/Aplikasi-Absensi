<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Password Baru</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="auth-page" style="--auth-bg-image: url('{{ asset('uploads/selfies/Bg-image News Detail.png') }}');">
    <main class="auth-shell">
        <section class="auth-card">
            <header class="auth-header auth-header-center">
                <div class="auth-badge">Wajib Dilakukan</div>
                <h1 class="auth-title">Buat Password Baru</h1>
                <p class="auth-copy">Akun karyawan ini masih memakai password sementara. Silakan buat password baru untuk langsung masuk ke absensi.</p>
            </header>

            <div class="auth-body">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="auth-footer" style="text-align: left;">
                    @if ($employee)
                        Login sebagai <strong>{{ $employee->name }}</strong> ({{ $employee->nip }})
                    @else
                        Akun terdeteksi masih wajib membuat password baru.
                    @endif
                </div>

                <form method="POST" action="{{ route('password.force.update') }}" class="app-form auth-form">
                    @csrf

                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="input-field" placeholder="Password baru" minlength="6" autocomplete="new-password" required autofocus>
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password Baru</label>
                        <div class="password-field">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="input-field" placeholder="Ulangi password baru" minlength="6" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary auth-submit">
                        Simpan Password Baru
                    </button>
                </form>

                <div class="auth-footer">
                    Setelah password disimpan, kamu bisa langsung akses absensi dan fitur lain.
                </div>
            </div>
        </section>
    </main>
    <script src="{{ asset('js/password-toggle.js') }}?v={{ filemtime(public_path('js/password-toggle.js')) }}"></script>
</body>
</html>
