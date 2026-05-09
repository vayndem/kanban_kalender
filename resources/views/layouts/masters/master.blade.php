<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Penjadwalan E-ling')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

    <header
        class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-sm w-full sticky top-0 z-50 border-b border-gray-100 dark:border-gray-800">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ url('/') }}"
                        class="group flex items-center gap-2 text-2xl font-black tracking-tighter">
                        <div
                            class="bg-emerald-500 text-white p-1.5 rounded-lg group-hover:rotate-6 transition-transform">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span
                            class="bg-clip-text text-transparent bg-gradient-to-r from-emerald-600 to-teal-500 dark:from-emerald-400 dark:to-teal-300">
                            E-ling Schedule
                        </span>
                    </a>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('jadwal.kalender') }}"
                        class="flex items-center gap-2 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 px-4 py-2 rounded-xl text-sm font-bold transition-all">
                        <i class="fas fa-columns text-emerald-500"></i>
                        Kanban
                    </a>

                    <div class="h-6 w-[1px] bg-gray-200 dark:bg-gray-700 mx-2"></div>

                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-lg shadow-emerald-200 dark:shadow-none transition-all flex items-center gap-2">
                                <i class="fas fa-th-large"></i>
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="text-gray-700 dark:text-gray-200 hover:text-emerald-600 dark:hover:text-emerald-400 px-4 py-2 rounded-xl text-sm font-bold transition-all">
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

    <footer class="bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 pt-12 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 items-center">
                <div class="text-center md:text-left">
                    <div class="flex items-center justify-center md:justify-start gap-2 mb-3">
                        <div
                            class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center text-white text-xs">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">E-ling
                            Schedule</span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm max-w-xs mx-auto md:mx-0">
                        Sistem manajemen penjadwalan dan pembayaran otomatis terintegrasi.
                    </p>
                </div>
                <div class="flex justify-center md:justify-end gap-6">
                    <a href="#" class="text-gray-400 hover:text-emerald-500 transition-colors"><i
                            class="fab fa-whatsapp fa-lg"></i></a>
                    <a href="#" class="text-gray-400 hover:text-emerald-500 transition-colors"><i
                            class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-gray-400 hover:text-emerald-500 transition-colors"><i
                            class="fas fa-envelope fa-lg"></i></a>
                </div>
            </div>
            <div
                class="border-t border-gray-100 dark:border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-center">
                <div class="text-gray-500 dark:text-gray-400 text-xs font-medium uppercase tracking-widest">
                    &copy; {{ date('Y') }} • Build with Passion
                </div>
                <div class="text-gray-500 dark:text-gray-400 text-sm">
                    Made by <span class="font-bold text-gray-900 dark:text-white">Vayndem</span>
                    with <span class="inline-block animate-pulse ml-1 text-red-500">❤</span>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
