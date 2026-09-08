<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Jadwal Saya — {{ config('app.name', 'E-Ling Course') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="app-canvas min-h-screen text-base-content">

        <x-portal-nav icon="fa-chalkboard-user" :title="$guru->name" subtitle="Portal Guru — E-Ling Course">
            <a href="{{ route('modulAjar.index') }}" class="btn btn-ghost btn-sm text-xs">
                <i class="fas fa-book-open-reader"></i><span class="hidden sm:inline"> Modul Ajar</span>
            </a>
            <a href="{{ route('absen.index') }}" class="btn btn-ghost btn-sm text-xs">
                <i class="fas fa-clipboard-user"></i><span class="hidden sm:inline"> Absen</span>
            </a>
        </x-portal-nav>

        <main class="py-6 sm:py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div
                        class="app-card border-success/40 bg-success/10 p-4">
                        <p
                            class="text-[11px] font-bold uppercase tracking-wider text-success">
                            Kelas Diampu</p>
                        <p class="mt-1.5 text-2xl font-black text-success">{{ $totalKelas }}
                        </p>
                    </div>
                    <div
                        class="app-card border-primary/40 bg-primary/10 p-4">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-primary">Siswa
                            Diajar</p>
                        <p class="mt-1.5 text-2xl font-black text-primary">{{ $totalSiswa }}</p>
                    </div>
                    <div
                        class="app-card col-span-2 flex items-center gap-2.5 p-4 sm:col-span-1">
                        <i class="fas fa-eye text-base-content/60"></i>
                        <p class="text-xs text-base-content/70 leading-snug">
                            Halaman ini <span class="font-bold">hanya menampilkan</span> jadwal Anda.
                            Perubahan jadwal dilakukan oleh admin.
                        </p>
                    </div>
                </div>

                @forelse ($haris as $hari)
                    @php
                        $kelasHariIni = $kelas->where('hari_id', $hari->id);
                    @endphp

                    <div
                        class="app-card overflow-hidden">
                        <div
                            class="px-4 py-3 border-b border-base-300 bg-base-200 flex items-center justify-between">
                            <h2 class="font-bold text-sm">{{ $hari->name }}</h2>
                            <span class="text-xs font-semibold text-base-content/60">
                                {{ $kelasHariIni->count() }} kelas
                            </span>
                        </div>

                        @if ($kelasHariIni->isEmpty())
                            <p class="px-4 py-6 text-center text-xs italic text-base-content/60">Tidak ada kelas pada hari ini.</p>
                        @else
                            <div class="divide-y divide-base-300">
                                @foreach ($sesis as $sesi)
                                    @php
                                        $kelasSesi = $kelasHariIni->where('sesi_id', $sesi->id);
                                    @endphp
                                    @continue($kelasSesi->isEmpty())

                                    @foreach ($kelasSesi as $k)
                                        <div class="p-4 flex flex-col sm:flex-row sm:items-start gap-3">
                                            <div class="sm:w-40 shrink-0">
                                                <p class="text-xs font-black text-success">
                                                    {{ $sesi->name }}</p>
                                                <p class="text-xs text-base-content/60 font-mono">
                                                    {{ \Illuminate\Support\Str::of($sesi->start_time)->substr(0, 5) }}
                                                    –
                                                    {{ \Illuminate\Support\Str::of($sesi->end_time)->substr(0, 5) }}
                                                </p>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-bold text-sm">{{ $k['mata_pelajaran'] }}</span>
                                                    <span
                                                        class="app-chip">
                                                        <i class="fas fa-door-open mr-1"></i>{{ $k['ruang'] }}
                                                    </span>
                                                    <span
                                                        class="badge badge-primary badge-sm font-bold">
                                                        {{ $k['siswa']->count() }} siswa
                                                    </span>
                                                </div>

                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    @foreach ($k['siswa'] as $s)
                                                        <span
                                                            class="rounded-field border border-base-300 bg-base-200 px-2 py-1 text-xs">
                                                            {{ $s['panggilan'] ?: $s['nama'] }}
                                                            @if ($s['kelas'])
                                                                <span class="text-base-content/60">·
                                                                    {{ $s['kelas'] }}</span>
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
                    <p class="text-center text-sm text-base-content/60 py-10">Belum ada hari yang terdaftar.</p>
                @endforelse

            </div>
        </main>
    </div>
</body>

</html>
