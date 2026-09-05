@php
    $hariIni = $ringkasanData['hari_ini'];
    $kelasHariIni = $ringkasanData['kelas_hari_ini'];
    $okupansi = $ringkasanData['okupansi_ruang'];
    $beban = $ringkasanData['beban_guru'];
    $periode = $ringkasanData['periode'];
    $finansial = $ringkasanData['finansial'];
    $kebersihan = $ringkasanData['kebersihan_data'];
    $bentrok = $ringkasanData['bentrok_tersembunyi'];
    $pengingatWa = $ringkasanData['pengingat_wa'];

    $periodeUrl = function (string $target) {
        return route('dashboard', array_merge(request()->query(), ['tab' => 'ringkasan', 'periode' => $target]));
    };
@endphp

<div class="space-y-6">

    {{-- Peringatan bentrok tersembunyi --}}
    @if ($bentrok->isNotEmpty())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/40">
            <h3 class="flex items-center gap-2 text-sm font-black text-red-700 dark:text-red-300">
                <i class="fas fa-triangle-exclamation"></i> Bentrok Tersembunyi Terdeteksi ({{ $bentrok->count() }})
            </h3>
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">Biasanya muncul akibat restore data (Stash) yang
                melewati validasi bentrok normal.</p>
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
        <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Ringkasan Hari Ini
        </h3>
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
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $hariIni['ruang_terpakai'] }}<span
                        class="text-base text-slate-400">/{{ $hariIni['total_ruang'] }}</span></div>
                <div class="text-xs font-bold text-slate-500 dark:text-slate-400">Ruang Terpakai</div>
            </div>
        </div>
    </div>

    {{-- Jadwal hari ini: kotak besar per kelas, klik untuk detail siswa --}}
    <div x-data="{ cards: @js($kelasHariIni), selectedCard: null }">
        <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Jadwal Hari Ini
        </h3>

        <template x-if="cards.length === 0">
            <div
                class="rounded-xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm italic text-slate-400 dark:border-gray-700 dark:bg-gray-800">
                Tidak ada kelas yang terjadwal hari ini.
            </div>
        </template>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="(card, idx) in cards" :key="idx">
                <button type="button" @click="selectedCard = card"
                    class="group flex flex-col rounded-2xl border border-gray-100 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:border-emerald-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-emerald-700">
                    <div class="flex items-center justify-between">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                            <i class="fas fa-clock"></i> <span x-text="card.sesi_name"></span>
                        </span>
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                            <i class="fas fa-user-group"></i> <span x-text="card.siswa_list.length"></span>
                        </span>
                    </div>

                    <div class="mt-3 text-base font-black text-slate-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400"
                        x-text="card.mapel_name"></div>

                    <div class="mt-2 space-y-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                        <div class="flex items-center gap-1.5"><i
                                class="fas fa-chalkboard-user w-3.5 text-slate-400"></i> <span
                                x-text="card.guru_name"></span></div>
                        <div class="flex items-center gap-1.5"><i class="fas fa-door-open w-3.5 text-slate-400"></i>
                            <span x-text="card.ruang_name"></span>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <template x-for="siswa in card.siswa_list.slice(0, 4)" :key="siswa.name">
                            <span
                                class="rounded-md bg-slate-50 px-1.5 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-700/60 dark:text-slate-300"
                                x-text="siswa.panggilan || siswa.name"></span>
                        </template>
                        <span x-show="card.siswa_list.length > 4"
                            class="rounded-md bg-slate-50 px-1.5 py-0.5 text-[10px] font-bold text-slate-400 dark:bg-slate-700/60"
                            x-text="'+' + (card.siswa_list.length - 4) + ' lainnya'"></span>
                    </div>
                </button>
            </template>
        </div>

        {{-- Modal detail kelas --}}
        <template x-if="selectedCard">
            <div x-show="selectedCard" x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                @click="selectedCard = null">
                <div @click.stop x-transition
                    class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                    <div
                        class="flex items-start justify-between gap-4 bg-gradient-to-r from-emerald-600 to-teal-600 p-5 text-white">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-100"
                                x-text="selectedCard.sesi_name"></p>
                            <h3 class="mt-1 text-xl font-black" x-text="selectedCard.mapel_name"></h3>
                            <p class="mt-1 text-sm text-emerald-50/90">
                                <i class="fas fa-chalkboard-user"></i> <span x-text="selectedCard.guru_name"></span>
                                &middot; <i class="fas fa-door-open"></i> <span x-text="selectedCard.ruang_name"></span>
                            </p>
                        </div>
                        <button type="button" @click="selectedCard = null"
                            class="rounded-full bg-white/15 px-3 py-2 text-sm font-bold hover:bg-white/25">Tutup</button>
                    </div>
                    <div class="max-h-[50vh] overflow-y-auto p-5">
                        <p class="mb-3 text-xs font-black uppercase tracking-wider text-slate-400">Daftar Siswa (<span
                                x-text="selectedCard.siswa_list.length"></span>)</p>
                        <div class="space-y-2">
                            <template x-for="siswa in selectedCard.siswa_list" :key="siswa.name">
                                <div
                                    class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/40">
                                    <span class="text-sm font-bold text-slate-800 dark:text-slate-100"
                                        x-text="siswa.name"></span>
                                    <span class="text-xs font-semibold text-slate-400"
                                        x-text="siswa.kelas || '-'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Pengingat copy jadwal WA harian --}}
    <div x-data="{
        isLoading: false,
        sudahHariIni: @js($pengingatWa['sudah_hari_ini']),
        terakhirJam: @js($pengingatWa['terakhir_jam']),
        terakhirOleh: @js($pengingatWa['terakhir_oleh']),
        async copySekarang() {
            this.isLoading = true;
            try {
                await window.salinTeksJadwal(@js(route('admin.jadwal.generateText')));
                this.sudahHariIni = true;
                this.terakhirJam = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date());
                this.terakhirOleh = @js(auth()->user()->name);
                AppSwal.toast('Teks jadwal disalin ke clipboard!');
            } catch (e) {
                AppSwal.error('Gagal menyalin teks jadwal.');
            } finally {
                this.isLoading = false;
            }
        },
    }">
        <div class="rounded-xl border p-4 sm:p-5"
            :class="sudahHariIni ?
                'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30' :
                'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30'">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <i class="mt-0.5 fas fa-lg"
                        :class="sudahHariIni ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-amber-600'"></i>
                    <div>
                        <p class="text-sm font-black" :class="sudahHariIni ?
                            'text-emerald-800 dark:text-emerald-300' :
                            'text-amber-800 dark:text-amber-300'"
                            x-text="sudahHariIni ? 'Jadwal WA untuk guru sudah di-copy hari ini' : 'Jadwal WA untuk guru belum di-copy hari ini'">
                        </p>
                        <p class="mt-0.5 text-xs" :class="sudahHariIni ?
                            'text-emerald-700 dark:text-emerald-400' :
                            'text-amber-700 dark:text-amber-400'">
                            <template x-if="sudahHariIni">
                                <span>Terakhir jam <span x-text="terakhirJam"></span><span
                                        x-show="terakhirOleh"> oleh <span x-text="terakhirOleh"></span></span>.</span>
                            </template>
                            <template x-if="!sudahHariIni">
                                <span>Supaya guru-guru dapat info jadwal mereka, salin dan kirim teks jadwal ke grup WA
                                    setiap hari.</span>
                            </template>
                        </p>
                    </div>
                </div>
                <button type="button" @click="copySekarang()" :disabled="isLoading"
                    class="shrink-0 rounded-lg px-4 py-2 text-xs font-bold text-white shadow-sm transition-all"
                    :class="sudahHariIni ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-600 hover:bg-amber-700'">
                    <i class="fas fa-copy"></i>
                    <span x-text="sudahHariIni ? 'Copy Ulang' : 'Copy Sekarang'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toggle periode: harian / mingguan --}}
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Statistik Operasional
        </h3>
        <div class="inline-flex overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <a href="{{ $periodeUrl('harian') }}"
                class="px-3 py-1.5 text-xs font-black transition {{ $periode === 'harian' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-50 dark:bg-gray-800 dark:text-slate-400 dark:hover:bg-gray-700' }}">
                Harian
            </a>
            <a href="{{ $periodeUrl('mingguan') }}"
                class="px-3 py-1.5 text-xs font-black transition {{ $periode === 'mingguan' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-50 dark:bg-gray-800 dark:text-slate-400 dark:hover:bg-gray-700' }}">
                Mingguan
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Okupansi ruang --}}
        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">
                Okupansi Ruang ({{ $periode === 'harian' ? 'Hari Ini' : 'Mingguan' }})
            </h3>
            <p class="text-xs text-slate-400 mb-3">Rata-rata pemakaian: {{ $okupansi['rata_rata'] }}%</p>
            <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                @forelse ($okupansi['data'] as $ruang)
                    <div>
                        <div class="flex justify-between text-xs font-bold text-slate-600 dark:text-slate-300">
                            <span>{{ $ruang['name'] }}
                                @if ($okupansi['ramai']->contains('id', $ruang['id']))
                                    <span
                                        class="ml-1 rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-600 dark:bg-orange-900/40 dark:text-orange-300">Ramai</span>
                                @elseif ($okupansi['sepi']->contains('id', $ruang['id']))
                                    <span
                                        class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-slate-700 dark:text-slate-300">Sepi</span>
                                @endif
                            </span>
                            <span>{{ $ruang['terpakai'] }}/{{ $ruang['total_slot'] }}
                                ({{ $ruang['persentase'] }}%)
                            </span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-1.5 rounded-full bg-emerald-500"
                                style="width: {{ min(100, $ruang['persentase']) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs italic text-slate-400">Belum ada data ruang.</p>
                @endforelse
            </div>
        </div>

        {{-- Beban guru --}}
        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">
                Beban Mengajar Guru ({{ $periode === 'harian' ? 'Hari Ini' : 'Mingguan' }})
            </h3>

            @if ($beban['back_to_back']->isNotEmpty())
                <div class="mb-4">
                    <p class="mb-2 flex items-center gap-1.5 text-xs font-bold text-amber-700 dark:text-amber-300">
                        <i class="fas fa-triangle-exclamation"></i> Mengajar {{ $beban['ambang_beruntun'] }}+ sesi
                        berturut tanpa jeda, sekadar pengingat:
                    </p>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach ($beban['back_to_back'] as $b)
                            <div
                                class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/40">
                                <p class="text-xs font-black text-amber-800 dark:text-amber-200">
                                    {{ $b['nama'] }} &middot; {{ $b['hari'] }}
                                    <span class="font-semibold text-amber-500 dark:text-amber-400">({{ $b['jumlah_beruntun'] }} sesi beruntun)</span>
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($b['sesi_list'] as $s)
                                        <span
                                            class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-white px-2 py-1 text-[11px] font-bold text-amber-700 shadow-sm dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                            <i class="fas fa-clock text-[10px]"></i> {{ $s['name'] }}
                                            ({{ $s['start'] }}&ndash;{{ $s['end'] }})
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                @forelse ($beban['beban'] as $g)
                    <div class="flex justify-between text-xs font-semibold text-slate-600 dark:text-slate-300">
                        <span>{{ $g['nama'] }}</span>
                        <span>{{ $g['jumlah_sesi'] }} sesi &middot; {{ intdiv($g['total_menit'], 60) }}j
                            {{ $g['total_menit'] % 60 }}m</span>
                    </div>
                @empty
                    <p class="text-xs italic text-slate-400">Belum ada data jadwal guru.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Pengingat finansial --}}
    <div>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">Pengingat
                Finansial</h3>
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 text-xs">
                <input type="hidden" name="tab" value="ringkasan">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <label class="font-bold text-slate-500 dark:text-slate-400">Piutang lebih dari</label>
                <select name="piutang_bulan" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white p-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @foreach ([1, 2, 3, 4, 6, 12] as $m)
                        <option value="{{ $m }}"
                            {{ (int) $finansial['piutang_bulan'] === $m ? 'selected' : '' }}>{{ $m }} bulan
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div
                class="overflow-hidden rounded-2xl border border-t-4 border-t-rose-400 border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:border-t-rose-500 dark:bg-gray-800">
                <div
                    class="flex items-center gap-3 border-b border-gray-100 bg-rose-50/60 px-4 py-3 dark:border-gray-700 dark:bg-rose-950/20">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500 text-white shadow-sm"><i
                            class="fas fa-file-invoice"></i></span>
                    <div>
                        <p class="text-xs font-black text-rose-700 dark:text-rose-300">Belum Ditagih Bulan Ini</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $finansial['belum_ditagih']->count() }}</p>
                    </div>
                </div>
                <div class="max-h-52 space-y-1.5 overflow-y-auto p-3">
                    @forelse ($finansial['belum_ditagih'] as $item)
                        <div
                            class="flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs dark:bg-gray-900/40">
                            <i class="fas fa-user text-[10px] text-rose-400"></i>
                            <span
                                class="font-bold text-slate-700 dark:text-slate-200">{{ $item['siswa_name'] }}</span>
                            <span class="ml-auto text-slate-400">{{ $item['paket'] }}</span>
                        </div>
                    @empty
                        <p class="p-2 text-xs italic text-slate-400">Semua siswa sudah ditagih bulan ini.</p>
                    @endforelse
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-t-4 border-t-amber-400 border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:border-t-amber-500 dark:bg-gray-800">
                <div
                    class="flex items-center gap-3 border-b border-gray-100 bg-amber-50/60 px-4 py-3 dark:border-gray-700 dark:bg-amber-950/20">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm"><i
                            class="fas fa-hourglass-half"></i></span>
                    <div>
                        <p class="text-xs font-black text-amber-700 dark:text-amber-300">Piutang Lama &gt;
                            {{ $finansial['piutang_bulan'] }} Bulan</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $finansial['piutang_lama']->count() }}</p>
                    </div>
                </div>
                <div class="max-h-52 space-y-1.5 overflow-y-auto p-3">
                    @forelse ($finansial['piutang_lama'] as $item)
                        <div
                            class="flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs dark:bg-gray-900/40">
                            <i class="fas fa-user text-[10px] text-amber-400"></i>
                            <span
                                class="font-bold text-slate-700 dark:text-slate-200">{{ $item['siswa_names'] ?: $item['no_hp'] }}</span>
                            <span class="ml-auto font-black text-amber-600 dark:text-amber-400">Rp {{ number_format($item['total_sisa'], 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="p-2 text-xs italic text-slate-400">Tidak ada piutang lama.</p>
                    @endforelse
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-t-4 border-t-indigo-400 border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:border-t-indigo-500 dark:bg-gray-800">
                <div
                    class="flex items-center gap-3 border-b border-gray-100 bg-indigo-50/60 px-4 py-3 dark:border-gray-700 dark:bg-indigo-950/20">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500 text-white shadow-sm"><i
                            class="fas fa-tags"></i></span>
                    <div>
                        <p class="text-xs font-black text-indigo-700 dark:text-indigo-300">Diskon Menggantung</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $finansial['diskon_menggantung']->count() }}</p>
                    </div>
                </div>
                <div class="max-h-52 space-y-1.5 overflow-y-auto p-3">
                    @forelse ($finansial['diskon_menggantung'] as $d)
                        <div
                            class="flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs dark:bg-gray-900/40">
                            <i class="fas fa-tag text-[10px] text-indigo-400"></i>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $d->no_hp }}</span>
                            <span class="ml-auto text-slate-400">Rp {{ number_format($d->diskon, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="p-2 text-xs italic text-slate-400">Tidak ada diskon menggantung.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Kebersihan data --}}
    <div>
        <h3 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">Kebersihan Data
        </h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div
                class="overflow-hidden rounded-2xl border border-t-4 border-t-sky-400 border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:border-t-sky-500 dark:bg-gray-800">
                <div
                    class="flex items-center gap-3 border-b border-gray-100 bg-sky-50/60 px-4 py-3 dark:border-gray-700 dark:bg-sky-950/20">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500 text-white shadow-sm"><i
                            class="fas fa-calendar-xmark"></i></span>
                    <div>
                        <p class="text-xs font-black text-sky-700 dark:text-sky-300">Siswa Tanpa Jadwal</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $kebersihan['siswa_tanpa_jadwal']->count() }}</p>
                    </div>
                </div>
                <div class="max-h-40 space-y-1.5 overflow-y-auto p-3">
                    @forelse ($kebersihan['siswa_tanpa_jadwal'] as $s)
                        <div
                            class="flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs dark:bg-gray-900/40">
                            <i class="fas fa-user text-[10px] text-sky-400"></i>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $s->name }}</span>
                            @if ($s->kelas)
                                <span class="ml-auto text-slate-400">{{ $s->kelas }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="p-2 text-xs italic text-slate-400">Semua siswa sudah terjadwal.</p>
                    @endforelse
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-t-4 border-t-fuchsia-400 border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:border-t-fuchsia-500 dark:bg-gray-800">
                <div
                    class="flex items-center gap-3 border-b border-gray-100 bg-fuchsia-50/60 px-4 py-3 dark:border-gray-700 dark:bg-fuchsia-950/20">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-fuchsia-500 text-white shadow-sm"><i
                            class="fas fa-phone-slash"></i></span>
                    <div>
                        <p class="text-xs font-black text-fuchsia-700 dark:text-fuchsia-300">Siswa Tanpa No. HP</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $kebersihan['siswa_tanpa_hp']->count() }}</p>
                    </div>
                </div>
                <div class="max-h-40 space-y-1.5 overflow-y-auto p-3">
                    @forelse ($kebersihan['siswa_tanpa_hp'] as $s)
                        <div
                            class="flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs dark:bg-gray-900/40">
                            <i class="fas fa-user text-[10px] text-fuchsia-400"></i>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $s->name }}</span>
                        </div>
                    @empty
                        <p class="p-2 text-xs italic text-slate-400">Semua siswa punya no. HP.</p>
                    @endforelse
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-t-4 border-t-stone-400 border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:border-t-stone-500 dark:bg-gray-800">
                <div
                    class="flex items-center gap-3 border-b border-gray-100 bg-stone-50/60 px-4 py-3 dark:border-gray-700 dark:bg-stone-950/20">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-stone-500 text-white shadow-sm"><i
                            class="fas fa-box-archive"></i></span>
                    <div>
                        <p class="text-xs font-black text-stone-700 dark:text-stone-300">Arsip Mengendap &gt;
                            {{ $kebersihan['arsip_bulan'] }} Bulan</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $kebersihan['arsip_mengendap']->count() }}</p>
                    </div>
                </div>
                <div class="max-h-40 space-y-1.5 overflow-y-auto p-3">
                    @forelse ($kebersihan['arsip_mengendap'] as $a)
                        <div
                            class="flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs dark:bg-gray-900/40">
                            <i class="fas fa-box text-[10px] text-stone-400"></i>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $a->name }}</span>
                            <span class="ml-auto text-slate-400">sejak
                                {{ \Carbon\Carbon::parse($a->created_at)->translatedFormat('d M Y') }}</span>
                        </div>
                    @empty
                        <p class="p-2 text-xs italic text-slate-400">Tidak ada arsip yang mengendap lama.</p>
                    @endforelse
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-t-4 border-t-amber-400 border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:border-t-amber-500 dark:bg-gray-800">
                <div
                    class="flex items-center gap-3 border-b border-gray-100 bg-amber-50/60 px-4 py-3 dark:border-gray-700 dark:bg-amber-950/20">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm"><i
                            class="fas fa-note-sticky"></i></span>
                    <div>
                        <p class="text-xs font-black text-amber-700 dark:text-amber-300">Catatan &gt;
                            {{ $kebersihan['tanda_lama_hari'] }} Hari</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $kebersihan['tanda_lama']->count() }}</p>
                    </div>
                </div>
                <div class="max-h-40 space-y-1.5 overflow-y-auto p-3">
                    @forelse ($kebersihan['tanda_lama'] as $t)
                        <div
                            class="flex items-center gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs dark:bg-gray-900/40">
                            <i class="fas fa-note-sticky text-[10px] text-amber-400"></i>
                            <span class="min-w-0 flex-1 truncate font-bold text-slate-700 dark:text-slate-200"
                                title="{{ $t->keterangan }}">
                                {{ $t->siswa->name ?? 'Siswa dihapus' }}
                            </span>
                            <span class="ml-auto shrink-0 text-slate-400">sejak
                                {{ \Carbon\Carbon::parse($t->created_at)->translatedFormat('d M Y') }}</span>
                        </div>
                    @empty
                        <p class="p-2 text-xs italic text-slate-400">Tidak ada catatan yang mengendap lama.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if (
            $kebersihan['guru_tidak_terpakai']->isNotEmpty() ||
                $kebersihan['ruang_tidak_terpakai']->isNotEmpty() ||
                $kebersihan['mapel_tidak_terpakai']->isNotEmpty())
            <div
                class="mt-4 grid grid-cols-1 gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:grid-cols-3">
                <div>
                    <p class="mb-2 flex items-center gap-1.5 text-xs font-black text-slate-500 dark:text-slate-400"><i
                            class="fas fa-chalkboard-user text-slate-400"></i> Guru Tidak Terpakai
                        ({{ $kebersihan['guru_tidak_terpakai']->count() }})</p>
                    <div class="flex flex-wrap gap-1">
                        @forelse ($kebersihan['guru_tidak_terpakai'] as $g)
                            <span
                                class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ $g->name }}</span>
                        @empty
                            <span class="text-xs italic text-slate-400">-</span>
                        @endforelse
                    </div>
                </div>
                <div>
                    <p class="mb-2 flex items-center gap-1.5 text-xs font-black text-slate-500 dark:text-slate-400"><i
                            class="fas fa-door-open text-slate-400"></i> Ruang Tidak Terpakai
                        ({{ $kebersihan['ruang_tidak_terpakai']->count() }})</p>
                    <div class="flex flex-wrap gap-1">
                        @forelse ($kebersihan['ruang_tidak_terpakai'] as $r)
                            <span
                                class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ $r->name }}</span>
                        @empty
                            <span class="text-xs italic text-slate-400">-</span>
                        @endforelse
                    </div>
                </div>
                <div>
                    <p class="mb-2 flex items-center gap-1.5 text-xs font-black text-slate-500 dark:text-slate-400"><i
                            class="fas fa-book text-slate-400"></i> Mapel Tidak Terpakai
                        ({{ $kebersihan['mapel_tidak_terpakai']->count() }})</p>
                    <div class="flex flex-wrap gap-1">
                        @forelse ($kebersihan['mapel_tidak_terpakai'] as $m)
                            <span
                                class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ $m->name }}</span>
                        @empty
                            <span class="text-xs italic text-slate-400">-</span>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
