<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Penjadwalan E-ling') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen app-canvas text-base-content">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="border-b border-base-300 bg-base-100/80 backdrop-blur-xl">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>

        @include('layouts.admin-help')
    </div>

    <!-- TAMBAHKAN BARIS INI -->
    @stack('scripts')

    @if (session('success') || session('error') || session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const success = @js(session('success'));
                const error = @js(session('error'));
                const status = @js(session('status'));
                const statusMessages = {
                    'profile-updated': 'Profil berhasil diperbarui.',
                    'password-updated': 'Password berhasil diperbarui.',
                    'verification-link-sent': 'Tautan verifikasi telah dikirim.',
                };

                if (error) AppSwal.error(error);
                else if (success) AppSwal.toast(success);
                else if (status) AppSwal.toast(statusMessages[status] || status, 'info');
            });
        </script>
    @endif
</body>

</html>
