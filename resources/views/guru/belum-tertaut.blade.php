<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akun Belum Tertaut — {{ config('app.name', 'E-Ling Course') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="app-canvas flex min-h-screen items-center justify-center p-4 text-base-content">
        <div class="app-card w-full max-w-md animate-rise p-6 text-center">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-box bg-warning/15 text-2xl text-warning">
                <i class="fas fa-link-slash"></i>
            </div>
            <h1 class="text-lg font-black text-base-content">Akun belum tertaut ke data guru</h1>
            <p class="mt-2 text-sm text-base-content/70 leading-relaxed">
                Akun <span class="font-semibold">{{ $user->email }}</span> sudah bisa masuk, tetapi belum
                dihubungkan dengan data guru mana pun, sehingga jadwalnya belum bisa ditampilkan.
            </p>
            <p class="mt-3 text-xs text-base-content/60">
                Hubungi admin untuk menautkan akun ini melalui menu Master Data.
            </p>
            <form method="POST" action="{{ route('logout') }}" class="mt-5">
                @csrf
                <button type="submit" class="btn btn-neutral w-full text-sm">
                    <i class="fas fa-right-from-bracket"></i> Keluar
                </button>
            </form>
        </div>
    </div>
</body>

</html>
