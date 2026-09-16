<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Rapor Perkembangan Anak &middot; E-Ling Course</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="app-canvas min-h-screen">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-12">

        <header class="mb-6 text-center">
            <span class="brand-chip mx-auto mb-3 flex h-12 w-12 items-center justify-center text-xl">
                <i class="fas fa-medal"></i>
            </span>
            <h1 class="text-2xl font-black tracking-tight text-base-content sm:text-3xl">Rapor Perkembangan Anak</h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-base-content/70">
                Khusus orang tua siswa E-Ling Course. Isi nama lengkap anak dan 4 angka terakhir nomor HP yang
                terdaftar di kami.
            </p>
        </header>

        <form method="POST" action="{{ route('rapor.publik.cari') }}" class="app-card app-card-pad mb-6">
            @csrf

            @if ($errors->any())
                <div class="mb-4 flex items-start gap-3 rounded-box border border-error/40 bg-error/10 p-3">
                    <i class="fas fa-circle-exclamation mt-0.5 text-error"></i>
                    <p class="text-sm text-base-content">{{ $errors->first() }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <label class="block sm:col-span-2">
                    <span class="app-label">Nama Lengkap Anak</span>
                    <input type="text" name="nama" value="{{ old('nama') }}" required autocomplete="off"
                        placeholder="Tulis persis seperti saat mendaftar" class="app-input">
                </label>

                <label class="block">
                    <span class="app-label">4 Angka Terakhir HP</span>
                    <input type="text" name="empat_digit" inputmode="numeric" pattern="\d{4}" maxlength="4"
                        required autocomplete="off" placeholder="1234" class="app-input tracking-[0.4em]">
                </label>
            </div>

            <button type="submit" class="btn btn-primary mt-4 w-full sm:w-auto">
                <i class="fas fa-magnifying-glass"></i> Lihat Rapor
            </button>
        </form>

        @if ($rapor && $siswa)
            @php
                $ringkasan = $rapor['ringkasan'];
                $warna = fn ($persen) => $persen === null ? 'text-base-content/60'
                    : ($persen > 80 ? 'text-success' : ($persen > 60 ? 'text-primary'
                    : ($persen > 40 ? 'text-warning' : 'text-error')));
                $bar = fn ($persen) => $persen > 80 ? 'bg-success' : ($persen > 60 ? 'bg-primary'
                    : ($persen > 40 ? 'bg-warning' : 'bg-error'));
            @endphp

            <div class="app-card overflow-hidden">
                <div class="bg-gradient-to-r from-primary to-accent px-5 py-5 text-white">
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-white/75">Progress Report</p>
                    <h2 class="mt-1 text-xl font-black sm:text-2xl">{{ $siswa->name }}</h2>
                    <p class="mt-1 text-sm text-white/85">
                        {{ $siswa->kelas ? 'Kelas '.$siswa->kelas : 'Kelas belum diisi' }}
                        &middot; {{ $rapor['periode']['label'] }}
                        &middot; Guru {{ $rapor['guru'] }}
                    </p>
                </div>

                @if ($ringkasan['total_pertemuan'] === 0)
                    <div class="app-empty border-0 p-8">
                        <div class="app-empty-icon"><i class="fas fa-clipboard-question"></i></div>
                        <p class="app-empty-title">Belum ada pertemuan yang dinilai.</p>
                        <p class="app-empty-text">Rapor akan muncul setelah guru menyelesaikan penilaian.</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 p-5 lg:grid-cols-4">
                        <div class="app-stat">
                            <div class="app-stat-value">{{ $ringkasan['total_pertemuan'] }}</div>
                            <div class="app-stat-label">Pertemuan</div>
                        </div>
                        <div class="app-stat">
                            <div class="app-stat-value text-success">{{ $ringkasan['persen_kehadiran'] }}%</div>
                            <div class="app-stat-label">Kehadiran</div>
                        </div>
                        <div class="app-stat">
                            <div class="app-stat-value {{ $warna($ringkasan['persen']) }}">
                                {{ $ringkasan['persen'] !== null ? $ringkasan['persen'].'%' : '-' }}
                            </div>
                            <div class="app-stat-label">Nilai Keseluruhan</div>
                        </div>
                        <div class="app-stat">
                            <div class="app-stat-value text-primary">{{ $ringkasan['predikat'] }}</div>
                            <div class="app-stat-label">Predikat</div>
                        </div>
                    </div>

                    @if (! empty($rapor['per_aspek']))
                        <div class="border-t border-base-300 p-5">
                            <p class="mb-3 text-xs font-black uppercase tracking-wider text-base-content/60">
                                Perkembangan Per Aspek
                            </p>
                            <div class="space-y-3">
                                @foreach ($rapor['per_aspek'] as $aspek)
                                    <div>
                                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                                            <span class="font-bold text-base-content">{{ $aspek['nama'] }}</span>
                                            <span class="text-sm font-black {{ $warna($aspek['persen']) }}">
                                                {{ $aspek['persen'] }}% &middot; {{ $aspek['predikat'] }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-xs leading-snug text-base-content/60">{{ $aspek['indikator'] }}</p>
                                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-base-200">
                                            <div class="h-full rounded-full {{ $bar($aspek['persen']) }}"
                                                style="width: {{ $aspek['persen'] }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($catatan)
                        <div class="border-t border-base-300 p-5">
                            <p class="mb-3 text-xs font-black uppercase tracking-wider text-base-content/60">
                                Catatan Guru
                            </p>
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                @foreach ([
                                    ['Kelebihan Anak', $catatan->kekuatan, 'fa-star', 'text-success'],
                                    ['Yang Perlu Dilatih', $catatan->perbaikan, 'fa-arrow-trend-up', 'text-warning'],
                                    ['Pesan Guru', $catatan->komentar, 'fa-comment-dots', 'text-primary'],
                                    ['Rencana Bulan Depan', $catatan->rencana, 'fa-calendar-check', 'text-accent'],
                                ] as [$judul, $isi, $ikon, $tone])
                                    @if (filled($isi))
                                        <div class="rounded-box border border-base-300 p-3">
                                            <p class="mb-1 text-xs font-black uppercase tracking-wider {{ $tone }}">
                                                <i class="fas {{ $ikon }}"></i> {{ $judul }}
                                            </p>
                                            <p class="whitespace-pre-line text-sm text-base-content/80">{{ $isi }}</p>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($rapor['materi']))
                        <div class="border-t border-base-300 p-5">
                            <p class="mb-3 text-xs font-black uppercase tracking-wider text-base-content/60">
                                Materi yang Dipelajari
                            </p>
                            <div class="app-table-wrap">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Topik</th>
                                            <th>Hasil</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rapor['materi'] as $materi)
                                            <tr>
                                                <td class="font-semibold text-base-content">{{ $materi['topik'] }}</td>
                                                <td class="text-base-content/70">{{ $materi['hasil'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <p class="mt-4 text-center text-xs text-base-content/60">
                Ada yang ingin ditanyakan? Hubungi admin E-Ling Course.
            </p>
        @endif

        <div class="mt-8 text-center">
            <a href="{{ route('welcome') }}" class="btn btn-neutral btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke halaman utama
            </a>
        </div>
    </div>
</body>

</html>
