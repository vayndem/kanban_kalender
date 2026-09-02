<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Jadwal Saya — {{ config('app.name', 'E-Ling Course') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">

        <nav class="border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-chalkboard-user text-emerald-500 text-lg"></i>
                        <div class="min-w-0">
                            <p class="text-sm font-bold truncate">{{ $guru->name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Portal Guru — E-Ling Course</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="text-xs font-bold text-slate-500 hover:text-red-500 transition-colors px-3 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30">
                            <i class="fas fa-right-from-bracket mr-1"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <main class="py-6 sm:py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl border border-emerald-200/70 dark:border-emerald-900/50 bg-emerald-50/70 dark:bg-emerald-950/20 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Kelas Diampu</p>
                        <p class="mt-1.5 text-2xl font-black text-emerald-800 dark:text-emerald-200">{{ $totalKelas }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-200/70 dark:border-blue-900/50 bg-blue-50/70 dark:bg-blue-950/20 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">Siswa Diajar</p>
                        <p class="mt-1.5 text-2xl font-black text-blue-800 dark:text-blue-200">{{ $totalSiswa }}</p>
                    </div>
                    <div class="col-span-2 sm:col-span-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 flex items-center gap-2.5">
                        <i class="fas fa-eye text-slate-400"></i>
                        <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-snug">
                            Halaman ini <span class="font-bold">hanya menampilkan</span> jadwal Anda.
                            Perubahan jadwal dilakukan oleh admin.
                        </p>
                    </div>
                </div>

                @forelse ($haris as $hari)
                    @php
                        $kelasHariIni = $kelas->where('hari_id', $hari->id);
                    @endphp

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 flex items-center justify-between">
                            <h2 class="font-bold text-sm">{{ $hari->name }}</h2>
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                {{ $kelasHariIni->count() }} kelas
                            </span>
                        </div>

                        @if ($kelasHariIni->isEmpty())
                            <p class="px-4 py-6 text-center text-xs text-slate-400 italic">Tidak ada kelas pada hari ini.</p>
                        @else
                            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($sesis as $sesi)
                                    @php
                                        $kelasSesi = $kelasHariIni->where('sesi_id', $sesi->id);
                                    @endphp
                                    @continue($kelasSesi->isEmpty())

                                    @foreach ($kelasSesi as $k)
                                        <div class="p-4 flex flex-col sm:flex-row sm:items-start gap-3">
                                            <div class="sm:w-40 shrink-0">
                                                <p class="text-xs font-black text-emerald-600 dark:text-emerald-400">{{ $sesi->name }}</p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                                                    {{ \Illuminate\Support\Str::of($sesi->start_time)->substr(0, 5) }}
                                                    –
                                                    {{ \Illuminate\Support\Str::of($sesi->end_time)->substr(0, 5) }}
                                                </p>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-bold text-sm">{{ $k['mata_pelajaran'] }}</span>
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                        <i class="fas fa-door-open mr-1"></i>{{ $k['ruang'] }}
                                                    </span>
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                                                        {{ $k['siswa']->count() }} siswa
                                                    </span>
                                                </div>

                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @foreach ($k['siswa'] as $s)
                                                        <span class="text-[11px] px-2 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/70 border border-slate-100 dark:border-slate-700">
                                                            {{ $s['panggilan'] ?: $s['nama'] }}
                                                            @if ($s['kelas'])
                                                                <span class="text-slate-400">· {{ $s['kelas'] }}</span>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-sm text-slate-400 py-10">Belum ada hari yang terdaftar.</p>
                @endforelse

            </div>
        </main>
    </div>
</body>

</html>
