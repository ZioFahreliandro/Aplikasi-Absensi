<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Absensi</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <header class="auth-header">
                <div class="auth-badge">Sistem Absensi</div>
                <h1 class="auth-title">Login Absensi</h1>
                <p class="auth-copy">Masukkan NIP dan password yang telah dibuat admin untuk masuk ke sistem.</p>
            </header>

            <div class="auth-body">
                @if ($errors->any())
                    <div class="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" class="app-form">
                    @csrf
                    <div class="form-group">
                        <label for="nip">NIP</label>
                        <input type="text" id="nip" name="nip" class="input-field" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="input-field" required>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">
                        Login
                    </button>
                </form>

                <div class="auth-footer">
                    Gunakan akun karyawan yang terdaftar atau akun admin yang disiapkan sistem.
                </div>
            </div>
        </section>
    </main>
</body>
</html>
