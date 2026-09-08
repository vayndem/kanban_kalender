<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Penjadwalan E-ling')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="bg-base-200 text-base-content min-h-screen flex flex-col">

    <header
        class="sticky top-0 z-50 w-full border-b border-base-300 bg-base-100/80 shadow-sm backdrop-blur-xl">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between gap-2 h-16">
                <div class="flex items-center">
                    <a href="{{ url('/') }}"
                        class="group flex items-center gap-2 text-2xl font-black tracking-tighter">
                        <div
                            class="rounded-btn bg-gradient-to-br from-primary to-accent p-1.5 text-white transition-transform group-hover:rotate-6">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="hidden sm:inline text-gradient-brand"
                            >
                            E-ling Schedule
                        </span>
                    </a>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('jadwal.kalender') }}"
                        class="flex items-center gap-2 rounded-btn px-2 py-2 text-sm font-bold text-base-content/70 transition-all hover:bg-base-200 hover:text-base-content sm:px-4">
                        <i class="fas fa-columns text-primary"></i>
                        <span class="hidden sm:inline">Kalender</span>
                    </a>

                    <div class="h-6 w-px bg-base-300 mx-1 sm:mx-2"></div>

                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}"
                                class="btn btn-primary btn-sm gap-2">
                                <i class="fas fa-th-large"></i>
                                <span class="hidden sm:inline">Dashboard</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="btn btn-ghost btn-sm">
                                Log in
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow min-h-screen">
        @yield('content')
    </main>

    <footer class="bg-base-100 border-t border-base-300 pt-12 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 items-center">
                <div class="text-center md:text-left">
                    <div class="flex items-center justify-center md:justify-start gap-2 mb-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-btn bg-gradient-to-br from-primary to-accent text-xs text-white">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="text-lg font-bold text-base-content tracking-tight">E-ling
                            Schedule</span>
                    </div>
                    <p class="text-base-content/60 text-sm max-w-xs mx-auto md:mx-0">
                        Sistem manajemen penjadwalan dan pembayaran otomatis terintegrasi.
                    </p>
                </div>
                <div class="flex justify-center md:justify-end gap-6">
                    <a href="#" class="text-base-content/50 transition-colors hover:text-primary"><i
                            class="fab fa-whatsapp fa-lg"></i></a>
                    <a href="#" class="text-base-content/50 transition-colors hover:text-primary"><i
                            class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-base-content/50 transition-colors hover:text-primary"><i
                            class="fas fa-envelope fa-lg"></i></a>
                </div>
            </div>
            <div
                class="border-t border-base-300 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-center">
                <div class="text-base-content/60 text-xs font-medium uppercase tracking-widest">
                    &copy; {{ date('Y') }} • Build with Passion
                </div>
                <div class="text-base-content/60 text-sm">
                    Made by <span class="font-bold text-base-content">Vayndem</span>
                    with <span class="inline-block animate-pulse ml-1 text-red-500">❤</span>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
