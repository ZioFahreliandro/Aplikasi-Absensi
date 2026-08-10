<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Absensi</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <main class="app-container" style="display:flex; justify-content:center; align-items:center; min-height:100vh;">
        <div class="card" style="max-width:480px; width:100%; text-align:center;">
            <h2 style="margin-bottom:0.5rem;">Login Absensi</h2>
            <p style="margin-bottom:1.5rem; color:#666;">Masukkan NIP dan password yang telah dibuat admin.</p>

            @if ($errors->any())
                <div style="margin-bottom:1rem; color:#b91c1c;">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="app-form">
                @csrf
                <div class="form-group" style="text-align:left;">
                    <label for="nip">NIP</label>
                    <input type="text" id="nip" name="nip" class="input-field" required>
                </div>
                <div class="form-group" style="text-align:left;">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="input-field" required>
                </div>
                <button type="submit" class="btn-primary" style="width:100%;">Login</button>
            </form>
        </div>
    </main>
</body>
</html>
