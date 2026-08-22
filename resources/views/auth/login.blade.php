<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Absensi</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="auth-page" style="--auth-bg-image: url('{{ asset('uploads/selfies/Bg-image News Detail.png') }}');">
    <main class="auth-shell">
        <section class="auth-card">
            <header class="auth-header auth-header-center">
                <h1 class="auth-title">Login Absensi</h1>
                <p class="auth-copy">Masukkan NIP dan password yang telah dibuat admin untuk masuk ke sistem.</p>
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

                <form method="POST" action="{{ route('login.post') }}" class="app-form auth-form">
                    @csrf
                    <div class="form-group">
                        <label for="nip">NIP</label>
                        <input type="text" id="nip" name="nip" class="input-field" placeholder="Masukkan NIP Anda" autocomplete="username" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="input-field" placeholder="Masukkan password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary auth-submit">
                        Login
                    </button>
                </form>

                <div class="auth-links">
                    <a href="{{ route('password.request') }}">Lupa password?</a>
                </div>

                <div class="auth-footer">
                    Gunakan akun karyawan yang sudah terdaftar.
                </div>
            </div>
        </section>
    </main>
    <script src="{{ asset('js/password-toggle.js') }}?v={{ filemtime(public_path('js/password-toggle.js')) }}"></script>
</body>
</html>
