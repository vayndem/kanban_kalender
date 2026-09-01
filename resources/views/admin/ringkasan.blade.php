@php
    $hariIni = $ringkasanData['hari_ini'];
    $okupansi = $ringkasanData['okupansi_ruang'];
    $beban = $ringkasanData['beban_guru'];
    $finansial = $ringkasanData['finansial'];
    $kebersihan = $ringkasanData['kebersihan_data'];
    $bentrok = $ringkasanData['bentrok_tersembunyi'];
@endphp

<div class="space-y-6">

    {{-- Peringatan bentrok tersembunyi --}}
    @if ($bentrok->isNotEmpty())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/40">
            <h3 class="flex items-center gap-2 text-sm font-black text-red-700 dark:text-red-300">
                <i class="fas fa-triangle-exclamation"></i> Bentrok Tersembunyi Terdeteksi ({{ $bentrok->count() }})
            </h3>
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">Biasanya muncul akibat restore data (Stash) yang melewati validasi bentrok normal.</p>
            <ul class="mt-2 space-y-1 text-xs text-red-700 dark:text-red-300 list-disc list-inside">
                @foreach ($bentrok->take(10) as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            @if ($bentrok->count() > 10)
                <p class="mt-1 text-[11px] text-red-500">...dan {{ $bentrok->count() - 10 }} lainnya.</p>
            @endif
        </div>
    @endif

    {{-- Ringkasan hari ini --}}
    <div>
        <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Ringkasan Hari Ini</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $hariIni['kelas_aktif'] }}</div>
                <div class="text-xs font-bold text-slate-500 dark:text-slate-400">Kelas Aktif</div>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $hariIni['siswa_terjadwal'] }}</div>
                <div class="text-xs font-bold text-slate-500 dark:text-slate-400">Siswa Terjadwal</div>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $hariIni['guru_mengajar'] }}</div>
                <div class="text-xs font-bold text-slate-500 dark:text-slate-400">Guru Mengajar</div>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $hariIni['ruang_terpakai'] }}<span class="text-base text-slate-400">/{{ $hariIni['total_ruang'] }}</span></div>
                <div class="text-xs font-bold text-slate-500 dark:text-slate-400">Ruang Terpakai</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Okupansi ruang --}}
        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Okupansi Ruang (Mingguan)</h3>
            <p class="text-xs text-slate-400 mb-3">Rata-rata pemakaian: {{ $okupansi['rata_rata'] }}%</p>
            <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                @forelse ($okupansi['data'] as $ruang)
                    <div>
                        <div class="flex justify-between text-xs font-bold text-slate-600 dark:text-slate-300">
                            <span>{{ $ruang['name'] }}
                                @if ($okupansi['ramai']->contains('id', $ruang['id']))
                                    <span class="ml-1 rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-600 dark:bg-orange-900/40 dark:text-orange-300">Ramai</span>
                                @elseif ($okupansi['sepi']->contains('id', $ruang['id']))
                                    <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-slate-700 dark:text-slate-300">Sepi</span>
                                @endif
                            </span>
                            <span>{{ $ruang['terpakai'] }}/{{ $ruang['total_slot'] }} ({{ $ruang['persentase'] }}%)</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ min(100, $ruang['persentase']) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs italic text-slate-400">Belum ada data ruang.</p>
                @endforelse
            </div>
        </div>

        {{-- Beban guru --}}
        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Beban Mengajar Guru (Mingguan)</h3>

            @if ($beban['back_to_back']->isNotEmpty())
                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/40">
                    <p class="text-xs font-bold text-amber-700 dark:text-amber-300">
                        <i class="fas fa-triangle-exclamation"></i> Mengajar {{ $beban['ambang_beruntun'] }}+ sesi berturut tanpa jeda:
                    </p>
                    <ul class="mt-1 space-y-0.5 text-xs text-amber-700 dark:text-amber-300">
                        @foreach ($beban['back_to_back'] as $b)
                            <li>{{ $b['nama'] }} &mdash; {{ $b['hari'] }} ({{ $b['jumlah_beruntun'] }} sesi beruntun)</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                @forelse ($beban['beban'] as $g)
                    <div class="flex justify-between text-xs font-semibold text-slate-600 dark:text-slate-300">
                        <span>{{ $g['nama'] }}</span>
                        <span>{{ $g['jumlah_sesi'] }} sesi &middot; {{ intdiv($g['total_menit'], 60) }}j {{ $g['total_menit'] % 60 }}m</span>
                    </div>
                @empty
                    <p class="text-xs italic text-slate-400">Belum ada data jadwal guru.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Pengingat finansial --}}
    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Pengingat Finansial</h3>
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 text-xs">
                <input type="hidden" name="tab" value="ringkasan">
                <label class="font-bold text-slate-500 dark:text-slate-400">Piutang lebih dari</label>
                <select name="piutang_bulan" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white p-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @foreach ([1, 2, 3, 4, 6, 12] as $m)
                        <option value="{{ $m }}" {{ (int) $finansial['piutang_bulan'] === $m ? 'selected' : '' }}>{{ $m }} bulan</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Belum Ditagih Bulan Ini ({{ $finansial['belum_ditagih']->count() }})</p>
                <div class="space-y-1 max-h-52 overflow-y-auto pr-1 text-xs text-slate-500 dark:text-slate-400">
                    @forelse ($finansial['belum_ditagih'] as $item)
                        <div>{{ $item['siswa_name'] }} &mdash; {{ $item['paket'] }}</div>
                    @empty
                        <p class="italic">Semua siswa sudah ditagih bulan ini.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Piutang Lama &gt; {{ $finansial['piutang_bulan'] }} Bulan ({{ $finansial['piutang_lama']->count() }})</p>
                <div class="space-y-1 max-h-52 overflow-y-auto pr-1 text-xs text-slate-500 dark:text-slate-400">
                    @forelse ($finansial['piutang_lama'] as $item)
                        <div>{{ $item['siswa_names'] ?: $item['no_hp'] }} &mdash; Rp {{ number_format($item['total_sisa'], 0, ',', '.') }}</div>
                    @empty
                        <p class="italic">Tidak ada piutang lama.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Diskon Menggantung ({{ $finansial['diskon_menggantung']->count() }})</p>
                <div class="space-y-1 max-h-52 overflow-y-auto pr-1 text-xs text-slate-500 dark:text-slate-400">
                    @forelse ($finansial['diskon_menggantung'] as $d)
                        <div>{{ $d->no_hp }} &mdash; Rp {{ number_format($d->diskon, 0, ',', '.') }} ({{ $d->keterangan ?: 'tanpa keterangan' }})</div>
                    @empty
                        <p class="italic">Tidak ada diskon menggantung.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Kebersihan data --}}
    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Kebersihan Data</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Siswa Tanpa Jadwal ({{ $kebersihan['siswa_tanpa_jadwal']->count() }})</p>
                <div class="space-y-1 max-h-40 overflow-y-auto pr-1 text-xs text-slate-500 dark:text-slate-400">
                    @forelse ($kebersihan['siswa_tanpa_jadwal'] as $s)
                        <div>{{ $s->name }} @if($s->kelas) &mdash; {{ $s->kelas }} @endif</div>
                    @empty
                        <p class="italic">Semua siswa sudah terjadwal.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Siswa Tanpa No. HP ({{ $kebersihan['siswa_tanpa_hp']->count() }})</p>
                <div class="space-y-1 max-h-40 overflow-y-auto pr-1 text-xs text-slate-500 dark:text-slate-400">
                    @forelse ($kebersihan['siswa_tanpa_hp'] as $s)
                        <div>{{ $s->name }}</div>
                    @empty
                        <p class="italic">Semua siswa punya no. HP.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Arsip Mengendap &gt; {{ $kebersihan['arsip_bulan'] }} Bulan ({{ $kebersihan['arsip_mengendap']->count() }})</p>
                <div class="space-y-1 max-h-40 overflow-y-auto pr-1 text-xs text-slate-500 dark:text-slate-400">
                    @forelse ($kebersihan['arsip_mengendap'] as $a)
                        <div>{{ $a->name }} &mdash; sejak {{ \Carbon\Carbon::parse($a->created_at)->translatedFormat('d M Y') }}</div>
                    @empty
                        <p class="italic">Tidak ada arsip yang mengendap lama.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($kebersihan['guru_tidak_terpakai']->isNotEmpty() || $kebersihan['ruang_tidak_terpakai']->isNotEmpty() || $kebersihan['mapel_tidak_terpakai']->isNotEmpty())
            <div class="mt-4 grid grid-cols-1 gap-4 border-t border-gray-100 pt-4 dark:border-gray-700 md:grid-cols-3">
                <div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Guru Tidak Terpakai ({{ $kebersihan['guru_tidak_terpakai']->count() }})</p>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $kebersihan['guru_tidak_terpakai']->pluck('name')->implode(', ') ?: '-' }}</div>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Ruang Tidak Terpakai ({{ $kebersihan['ruang_tidak_terpakai']->count() }})</p>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $kebersihan['ruang_tidak_terpakai']->pluck('name')->implode(', ') ?: '-' }}</div>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Mapel Tidak Terpakai ({{ $kebersihan['mapel_tidak_terpakai']->count() }})</p>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $kebersihan['mapel_tidak_terpakai']->pluck('name')->implode(', ') ?: '-' }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
