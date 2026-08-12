<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="bg-[#F4F5F7] min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-xl rounded-3xl bg-white p-10 shadow-xl">
        <h1 class="text-3xl font-bold mb-3">Halo, {{ Auth::user()->name }}</h1>
        <p class="text-sm text-slate-600 mb-6">Anda masuk menggunakan akun Google. Panel admin sekarang dapat diakses.</p>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700">Kembali ke Beranda</a>
            <form action="{{ route('logout') }}" method="POST" class="inline-flex">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>
