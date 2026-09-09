<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Gaji Saya — {{ config('app.name', 'E-Ling Course') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $rupiah = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
@endphp

<body class="font-sans antialiased">
    <div class="app-canvas min-h-screen text-base-content">

        <x-portal-nav icon="fa-money-check-dollar" title="Gaji Saya" :subtitle="$gaji['nama']">
            <a href="{{ route('guru.jadwal') }}" class="btn btn-ghost btn-sm text-xs">
                <i class="fas fa-arrow-left"></i><span class="hidden sm:inline"> Jadwal Saya</span>
            </a>
        </x-portal-nav>

        <main class="py-6 sm:py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

                <div class="app-card app-card-pad">
                    <h2 class="app-section-head">Perkiraan Gaji Berjalan</h2>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="app-stat">
                            <div class="app-stat-value">{{ $gaji['kehadiran_belum_dibayar'] }}</div>
                            <div class="app-stat-label">Kehadiran Belum Digaji</div>
                        </div>
                        <div class="app-stat">
                            <div class="app-stat-value text-base-content">{{ $rupiah($gaji['gaji_per_kehadiran']) }}</div>
                            <div class="app-stat-label">Per Kehadiran</div>
                        </div>
                        <div class="app-stat">
                            <div class="app-stat-value text-primary">{{ $rupiah($gaji['perkiraan_total']) }}</div>
                            <div class="app-stat-label">Perkiraan Total</div>
                        </div>
                    </div>

                    <div class="mt-4 flex items-start gap-2.5 rounded-box border border-base-300 bg-base-200/60 p-3">
                        <i class="fas fa-circle-info mt-0.5 text-info"></i>
                        <p class="text-xs leading-relaxed text-base-content/70">
                            Angka ini <span class="font-bold">perkiraan</span>, dihitung dari gaji bawaan
                            {{ $rupiah($gaji['gaji_bawaan']) }} ditambah kehadiran yang belum pernah digaji.
                            Yang berlaku adalah struk resmi yang diterbitkan admin di bawah ini.
                        </p>
                    </div>
                </div>

                <div class="app-card app-card-pad">
                    <h2 class="app-section-head">Riwayat Struk Saya</h2>

                    @if (count($gaji['riwayat']) === 0)
                        <div class="app-empty">
                            <div class="app-empty-icon"><i class="fas fa-receipt"></i></div>
                            <p class="app-empty-title">Belum ada struk penggajian.</p>
                            <p class="app-empty-text">Struk akan muncul di sini setelah admin menjalankan penggajian.</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($gaji['riwayat'] as $struk)
                                <div class="flex flex-col gap-3 rounded-box border border-base-300 p-4 sm:flex-row sm:items-center sm:justify-between {{ $struk['dibatalkan'] ? 'opacity-60' : '' }}">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-black text-base-content">{{ $rupiah($struk['total']) }}</span>
                                            @if ($struk['dibatalkan'])
                                                <span class="badge badge-ghost badge-sm font-bold">Dibatalkan</span>
                                            @else
                                                <span class="badge badge-success badge-sm font-bold">Diterbitkan</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs text-base-content/70">
                                            {{ $struk['jumlah_kehadiran'] }} kehadiran ×
                                            {{ $rupiah($struk['gaji_per_kehadiran']) }} + bawaan
                                            {{ $rupiah($struk['gaji_bawaan']) }}
                                        </p>
                                        <p class="text-xs text-base-content/60">{{ $struk['dijalankan_pada'] }}</p>
                                    </div>

                                    <a href="{{ route('penggajian.strukPdf', $struk['id']) }}"
                                        class="btn btn-export btn-sm shrink-0">
                                        <i class="fas fa-file-pdf"></i> Unduh Struk
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>
        </main>
    </div>
</body>

</html>
