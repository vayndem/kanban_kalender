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
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center p-4">
        <div
            class="max-w-md w-full rounded-2xl border border-amber-200 dark:border-amber-900 bg-white dark:bg-slate-900 p-6 text-center">
            <i class="fas fa-link-slash text-amber-500 text-3xl"></i>
            <h1 class="mt-3 text-lg font-bold text-slate-900 dark:text-white">Akun belum tertaut ke data guru</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                Akun <span class="font-semibold">{{ $user->email }}</span> sudah bisa masuk, tetapi belum
                dihubungkan dengan data guru mana pun, sehingga jadwalnya belum bisa ditampilkan.
            </p>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
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
