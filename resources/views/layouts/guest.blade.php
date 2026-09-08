<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Penjadwalan E-ling') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="app-canvas flex min-h-screen flex-col items-center justify-center px-4 py-10 text-base-content">
        <div class="w-full max-w-md animate-rise">
            <div class="mb-6 flex flex-col items-center text-center">
                <a href="/" class="transition hover:scale-105">
                    <x-application-logo class="h-16 w-16 drop-shadow-lg" />
                </a>
                <h1 class="mt-4 text-2xl font-black tracking-tight text-base-content">E-Ling Course</h1>
                <p class="mt-1 text-sm text-base-content/60">Sistem penjadwalan &amp; administrasi kursus</p>
            </div>

            <div class="app-card overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-primary to-accent"></div>
                <div class="p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-base-content/60">
                Butuh bantuan? Hubungi admin E-Ling Course.
            </p>
        </div>
    </div>
</body>

</html>
