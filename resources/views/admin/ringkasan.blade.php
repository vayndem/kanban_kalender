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

    $kartuHariIni = [
        ['label' => 'Kelas Aktif', 'icon' => 'fa-layer-group', 'nilai' => $hariIni['kelas_aktif'], 'sisa' => null],
        ['label' => 'Siswa Terjadwal', 'icon' => 'fa-user-graduate', 'nilai' => $hariIni['siswa_terjadwal'], 'sisa' => null],
        ['label' => 'Guru Mengajar', 'icon' => 'fa-chalkboard-user', 'nilai' => $hariIni['guru_mengajar'], 'sisa' => null],
        ['label' => 'Ruang Terpakai', 'icon' => 'fa-door-open', 'nilai' => $hariIni['ruang_terpakai'], 'sisa' => '/' . $hariIni['total_ruang']],
    ];
@endphp

<div class="space-y-8">

    @if ($bentrok->isNotEmpty())
        <div class="app-card overflow-hidden border-error/40">
            <div class="flex items-center gap-3 bg-error/10 px-4 py-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-btn bg-error text-error-content shadow-sm">
                    <i class="fas fa-triangle-exclamation"></i>
                </span>
                <div>
                    <h3 class="text-sm font-black text-error">Bentrok Tersembunyi Terdeteksi ({{ $bentrok->count() }})</h3>
                    <p class="text-xs text-base-content/60">Biasanya muncul akibat restore data (Stash) yang melewati
                        validasi bentrok normal.</p>
                </div>
            </div>
            <ul class="space-y-1 p-4 text-xs text-base-content/80">
                @foreach ($bentrok->take(10) as $item)
                    <li class="flex items-start gap-2">
                        <i class="fas fa-circle mt-1.5 text-[5px] text-error"></i>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
                @if ($bentrok->count() > 10)
                    <li class="pt-1 text-[11px] italic text-base-content/50">...dan {{ $bentrok->count() - 10 }} lainnya.</li>
                @endif
            </ul>
        </div>
    @endif

    <section>
        <h3 class="app-section-head">Ringkasan Hari Ini</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($kartuHariIni as $kartu)
                <div class="app-stat">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="app-stat-value">{{ $kartu['nilai'] }}@if ($kartu['sisa'])<span class="text-base font-bold text-base-content/40">{{ $kartu['sisa'] }}</span>@endif</div>
                            <div class="app-stat-label">{{ $kartu['label'] }}</div>
                        </div>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-btn bg-primary/10 text-primary">
                            <i class="fas {{ $kartu['icon'] }}"></i>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

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
        <div class="app-card overflow-hidden">
            <div class="h-1" :class="sudahHariIni ? 'bg-success' : 'bg-warning'"></div>
            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"
                :class="sudahHariIni ? 'bg-success/5' : 'bg-warning/5'">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-btn shadow-sm"
                        :class="sudahHariIni ? 'bg-success text-success-content' : 'bg-warning text-warning-content'">
                        <i class="fas" :class="sudahHariIni ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                    </span>
                    <div>
                        <p class="text-sm font-black" :class="sudahHariIni ? 'text-success' : 'text-warning'"
                            x-text="sudahHariIni ? 'Jadwal WA untuk guru sudah di-copy hari ini' : 'Jadwal WA untuk guru belum di-copy hari ini'">
                        </p>
                        <p class="mt-1 text-xs leading-relaxed text-base-content/70">
                            <template x-if="sudahHariIni">
                                <span>Terakhir jam <span class="font-bold" x-text="terakhirJam"></span><span
                                        x-show="terakhirOleh"> oleh <span class="font-bold" x-text="terakhirOleh"></span></span>.</span>
                            </template>
                            <template x-if="!sudahHariIni">
                                <span>Supaya guru-guru dapat info jadwal mereka, salin dan kirim teks jadwal ke grup WA
                                    setiap hari.</span>
                            </template>
                        </p>
                    </div>
                </div>
                <button type="button" @click="copySekarang()" :disabled="isLoading"
                    class="btn btn-sm shrink-0 self-start border-none sm:self-auto"
                    :class="sudahHariIni ? 'btn-success' : 'btn-warning'">
                    <i class="fas" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-copy'"></i>
                    <span x-text="sudahHariIni ? 'Copy Ulang' : 'Copy Sekarang'"></span>
                </button>
            </div>
        </div>
    </div>

    <section>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="app-section-head mb-0">Statistik Operasional</h3>
            <div class="join">
                <a href="{{ $periodeUrl('harian') }}"
                    class="btn join-item btn-xs sm:btn-sm {{ $periode === 'harian' ? 'btn-primary' : 'btn-ghost border border-base-300' }}">
                    Harian
                </a>
                <a href="{{ $periodeUrl('mingguan') }}"
                    class="btn join-item btn-xs sm:btn-sm {{ $periode === 'mingguan' ? 'btn-primary' : 'btn-ghost border border-base-300' }}">
                    Mingguan
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="app-card app-card-pad">
                <h4 class="app-section-title">Okupansi Ruang ({{ $periode === 'harian' ? 'Hari Ini' : 'Mingguan' }})</h4>
                <p class="app-section-sub mb-3">Rata-rata pemakaian: <span class="font-black text-primary">{{ $okupansi['rata_rata'] }}%</span></p>

                <div class="max-h-72 space-y-3 overflow-y-auto pr-1">
                    @forelse ($okupansi['data'] as $ruang)
                        <div>
                            <div class="flex justify-between gap-2 text-xs font-bold text-base-content/80">
                                <span class="min-w-0 flex-1 truncate">{{ $ruang['name'] }}
                                    @if ($okupansi['ramai']->contains('id', $ruang['id']))
                                        <span class="badge badge-warning badge-sm ml-1 font-bold">Ramai</span>
                                    @elseif ($okupansi['sepi']->contains('id', $ruang['id']))
                                        <span class="badge badge-ghost badge-sm ml-1 font-bold">Sepi</span>
                                    @endif
                                </span>
                                <span class="shrink-0 whitespace-nowrap tabular-nums">{{ $ruang['terpakai'] }}/{{ $ruang['total_slot'] }}
                                    ({{ $ruang['persentase'] }}%)
                                </span>
                            </div>
                            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-base-300">
                                <div class="h-2 rounded-full bg-gradient-to-r from-primary to-accent transition-all duration-500"
                                    style="width: {{ min(100, $ruang['persentase']) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="app-empty">
                            <div class="app-empty-icon"><i class="fas fa-door-closed"></i></div>
                            <p class="app-empty-title">Belum ada data ruang.</p>
                            <p class="app-empty-text">Tambahkan ruang lewat menu Workshop supaya okupansi bisa dihitung.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="app-card app-card-pad">
                <h4 class="app-section-title mb-3">Beban Mengajar Guru ({{ $periode === 'harian' ? 'Hari Ini' : 'Mingguan' }})</h4>

                @if ($beban['back_to_back']->isNotEmpty())
                    <div class="mb-4">
                        <p class="mb-2 flex items-start gap-1.5 text-xs font-bold text-warning">
                            <i class="fas fa-triangle-exclamation mt-0.5"></i>
                            <span>Mengajar {{ $beban['ambang_beruntun'] }}+ sesi berturut tanpa jeda, sekadar pengingat:</span>
                        </p>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach ($beban['back_to_back'] as $b)
                                <div class="rounded-box border border-warning/40 bg-warning/10 p-3">
                                    <p class="text-xs font-black text-base-content">
                                        {{ $b['nama'] }} &middot; {{ $b['hari'] }}
                                        <span class="font-semibold text-warning">({{ $b['jumlah_beruntun'] }} sesi beruntun)</span>
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($b['sesi_list'] as $s)
                                            <span class="inline-flex items-center gap-1 rounded-btn border border-warning/40 bg-base-100 px-2 py-1 text-[11px] font-bold text-base-content/80 shadow-sm">
                                                <i class="fas fa-clock text-[10px] text-warning"></i> {{ $s['name'] }}
                                                ({{ $s['start'] }}&ndash;{{ $s['end'] }})
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="max-h-60 space-y-1 overflow-y-auto pr-1">
                    @forelse ($beban['beban'] as $g)
                        <div class="flex justify-between gap-2 rounded-btn px-2 py-1.5 text-xs font-semibold text-base-content/80 transition hover:bg-base-200">
                            <span class="min-w-0 flex-1 truncate">{{ $g['nama'] }}</span>
                            <span class="shrink-0 whitespace-nowrap tabular-nums text-base-content/60">{{ $g['jumlah_sesi'] }} sesi &middot; {{ intdiv($g['total_menit'], 60) }}j
                                {{ $g['total_menit'] % 60 }}m</span>
                        </div>
                    @empty
                        <div class="app-empty">
                            <div class="app-empty-icon"><i class="fas fa-chalkboard-user"></i></div>
                            <p class="app-empty-title">Belum ada data jadwal guru.</p>
                            <p class="app-empty-text">Beban mengajar muncul setelah ada kelas terjadwal.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="app-section-head mb-0">Pengingat Finansial</h3>
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 text-xs">
                <input type="hidden" name="tab" value="ringkasan">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <label for="piutang_bulan" class="font-bold text-base-content/60">Piutang lebih dari</label>
                <select id="piutang_bulan" name="piutang_bulan" onchange="this.form.submit()"
                    class="select select-bordered select-xs sm:select-sm">
                    @foreach ([1, 2, 3, 4, 6, 12] as $m)
                        <option value="{{ $m }}"
                            {{ (int) $finansial['piutang_bulan'] === $m ? 'selected' : '' }}>{{ $m }} bulan
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-list-panel title="Belum Ditagih Bulan Ini" icon="fa-file-invoice" tone="error"
                :count="$finansial['belum_ditagih']->count()">
                @forelse ($finansial['belum_ditagih'] as $item)
                    <x-list-row icon="fa-user" tone="text-error">
                        <span class="min-w-0 flex-1 truncate font-bold text-base-content">{{ $item['siswa_name'] }}</span>
                        <span class="ml-auto shrink-0 text-base-content/50">{{ $item['paket'] }}</span>
                    </x-list-row>
                @empty
                    <p class="p-2 text-xs italic text-base-content/40">Semua siswa sudah ditagih bulan ini.</p>
                @endforelse
            </x-list-panel>

            <x-list-panel title="Piutang Lama > {{ $finansial['piutang_bulan'] }} Bulan" icon="fa-hourglass-half"
                tone="warning" :count="$finansial['piutang_lama']->count()">
                @forelse ($finansial['piutang_lama'] as $item)
                    <x-list-row icon="fa-user" tone="text-warning">
                        <span class="min-w-0 flex-1 truncate font-bold text-base-content">{{ $item['siswa_names'] ?: $item['no_hp'] }}</span>
                        <span class="ml-auto shrink-0 font-black text-warning">Rp {{ number_format($item['total_sisa'], 0, ',', '.') }}</span>
                    </x-list-row>
                @empty
                    <p class="p-2 text-xs italic text-base-content/40">Tidak ada piutang lama.</p>
                @endforelse
            </x-list-panel>

            <x-list-panel title="Diskon Menggantung" icon="fa-tags" tone="accent"
                :count="$finansial['diskon_menggantung']->count()">
                @forelse ($finansial['diskon_menggantung'] as $d)
                    <x-list-row icon="fa-tag" tone="text-accent">
                        <span class="min-w-0 flex-1 truncate font-bold text-base-content">{{ $d->no_hp }}</span>
                        <span class="ml-auto shrink-0 text-base-content/50">Rp {{ number_format($d->diskon, 0, ',', '.') }}</span>
                    </x-list-row>
                @empty
                    <p class="p-2 text-xs italic text-base-content/40">Tidak ada diskon menggantung.</p>
                @endforelse
            </x-list-panel>
        </div>
    </section>

    <section>
        <h3 class="app-section-head">Kebersihan Data</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-list-panel title="Siswa Tanpa Jadwal" icon="fa-calendar-xmark" tone="info" scroll="max-h-40"
                :count="$kebersihan['siswa_tanpa_jadwal']->count()">
                @forelse ($kebersihan['siswa_tanpa_jadwal'] as $s)
                    <x-list-row icon="fa-user" tone="text-info">
                        <span class="min-w-0 flex-1 truncate font-bold text-base-content">{{ $s->name }}</span>
                        @if ($s->kelas)
                            <span class="ml-auto shrink-0 text-base-content/50">{{ $s->kelas }}</span>
                        @endif
                    </x-list-row>
                @empty
                    <p class="p-2 text-xs italic text-base-content/40">Semua siswa sudah terjadwal.</p>
                @endforelse
            </x-list-panel>

            <x-list-panel title="Siswa Tanpa No. HP" icon="fa-phone-slash" tone="secondary" scroll="max-h-40"
                :count="$kebersihan['siswa_tanpa_hp']->count()">
                @forelse ($kebersihan['siswa_tanpa_hp'] as $s)
                    <x-list-row icon="fa-user" tone="text-secondary">
                        <span class="min-w-0 flex-1 truncate font-bold text-base-content">{{ $s->name }}</span>
                    </x-list-row>
                @empty
                    <p class="p-2 text-xs italic text-base-content/40">Semua siswa punya no. HP.</p>
                @endforelse
            </x-list-panel>

            <x-list-panel title="Arsip Mengendap > {{ $kebersihan['arsip_bulan'] }} Bulan" icon="fa-box-archive"
                tone="neutral" scroll="max-h-40" :count="$kebersihan['arsip_mengendap']->count()">
                @forelse ($kebersihan['arsip_mengendap'] as $a)
                    <x-list-row icon="fa-box">
                        <span class="min-w-0 flex-1 truncate font-bold text-base-content">{{ $a->name }}</span>
                        <span class="ml-auto shrink-0 text-base-content/50">sejak
                            {{ \Carbon\Carbon::parse($a->created_at)->translatedFormat('d M Y') }}</span>
                    </x-list-row>
                @empty
                    <p class="p-2 text-xs italic text-base-content/40">Tidak ada arsip yang mengendap lama.</p>
                @endforelse
            </x-list-panel>

            <x-list-panel title="Catatan > {{ $kebersihan['tanda_lama_hari'] }} Hari" icon="fa-note-sticky"
                tone="warning" scroll="max-h-40" :count="$kebersihan['tanda_lama']->count()">
                @forelse ($kebersihan['tanda_lama'] as $t)
                    <x-list-row icon="fa-note-sticky" tone="text-warning">
                        <span class="min-w-0 flex-1 truncate font-bold text-base-content" title="{{ $t->keterangan }}">
                            {{ $t->siswa->name ?? 'Siswa dihapus' }}
                        </span>
                        <span class="ml-auto shrink-0 text-base-content/50">sejak
                            {{ \Carbon\Carbon::parse($t->created_at)->translatedFormat('d M Y') }}</span>
                    </x-list-row>
                @empty
                    <p class="p-2 text-xs italic text-base-content/40">Tidak ada catatan yang mengendap lama.</p>
                @endforelse
            </x-list-panel>
        </div>

        @if (
            $kebersihan['guru_tidak_terpakai']->isNotEmpty() ||
                $kebersihan['ruang_tidak_terpakai']->isNotEmpty() ||
                $kebersihan['mapel_tidak_terpakai']->isNotEmpty())
            <div class="app-card app-card-pad mt-4 grid grid-cols-1 gap-5 md:grid-cols-3">
                @foreach ([['Guru Tidak Terpakai', 'fa-chalkboard-user', $kebersihan['guru_tidak_terpakai']], ['Ruang Tidak Terpakai', 'fa-door-open', $kebersihan['ruang_tidak_terpakai']], ['Mapel Tidak Terpakai', 'fa-book', $kebersihan['mapel_tidak_terpakai']]] as [$judul, $ikon, $daftar])
                    <div>
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-black text-base-content/60">
                            <i class="fas {{ $ikon }} text-base-content/40"></i> {{ $judul }} ({{ $daftar->count() }})
                        </p>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($daftar as $entri)
                                <span class="app-chip">{{ $entri->name }}</span>
                            @empty
                                <span class="text-xs italic text-base-content/40">-</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section x-data="{
        cards: @js($kelasHariIni),
        selectedCard: null,
        get grouped() {
            const map = {};
            for (const c of this.cards) {
                if (!map[c.sesi_id]) {
                    const jam = c.sesi_start && c.sesi_end ?
                        ` - ${String(c.sesi_start).substring(0, 5)}–${String(c.sesi_end).substring(0, 5)}` : '';
                    map[c.sesi_id] = { sesi_label: c.sesi_name + jam, items: [] };
                }
                map[c.sesi_id].items.push(c);
            }
            return Object.values(map);
        },
    }">
        <h3 class="app-section-head">Jadwal Hari Ini</h3>

        <template x-if="cards.length === 0">
            <div class="app-empty">
                <div class="app-empty-icon"><i class="fas fa-calendar-day"></i></div>
                <p class="app-empty-title">Tidak ada kelas yang terjadwal hari ini.</p>
                <p class="app-empty-text">Kalau ini tidak sesuai, cek kembali Jadwal Pelajaran untuk hari
                    {{ now()->translatedFormat('l') }}.</p>
            </div>
        </template>

        <template x-if="cards.length > 0">
            <div class="app-table-wrap">
                <table class="w-full min-w-full table-fixed border-collapse">
                    <thead>
                        <tr class="bg-base-200">
                            <th class="w-24 border-b border-r border-base-300 p-3 text-center text-xs font-black uppercase tracking-wider text-base-content/60 lg:w-32">
                                Sesi
                            </th>
                            <th class="min-w-[240px] border-b border-base-300 p-3 text-center text-xs uppercase tracking-wider text-base-content/60">
                                <div class="text-base font-black text-base-content">{{ now()->translatedFormat('l') }}</div>
                                <span class="mt-0.5 block text-[10px] font-semibold normal-case">{{ now()->translatedFormat('d F Y') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-base-100">
                        <template x-for="grup in grouped" :key="grup.sesi_label">
                            <tr class="even:bg-base-200/40">
                                <td class="border-t border-r border-base-300 p-2 text-center align-middle text-xs font-bold text-base-content"
                                    x-text="grup.sesi_label"></td>
                                <td class="min-w-[240px] border-t border-base-300 p-2 align-top">
                                    <div class="space-y-2">
                                        <template x-for="(card, idx) in grup.items" :key="idx">
                                            <button type="button" @click="selectedCard = card"
                                                class="group flex w-full flex-col rounded-btn border-l-4 border-base-300 bg-base-200/60 p-2.5 text-left text-sm shadow-sm transition-all hover:-translate-y-0.5 hover:border-l-primary hover:shadow-md">
                                                <div class="flex items-center justify-between gap-2">
                                                    <strong class="truncate font-bold text-base-content group-hover:text-primary"
                                                        x-text="card.mapel_name"></strong>
                                                    <span class="badge badge-neutral badge-sm shrink-0 gap-1 font-black">
                                                        <i class="fas fa-user-group text-[9px]"></i> <span x-text="card.siswa_list.length"></span>
                                                    </span>
                                                </div>
                                                <span class="mt-1 flex items-center gap-1.5 text-xs text-base-content/70">
                                                    <i class="fas fa-chalkboard-user text-[10px] text-base-content/40"></i>
                                                    <span x-text="card.guru_name"></span>
                                                </span>
                                                <span class="flex items-center gap-1.5 text-xs text-base-content/60">
                                                    <i class="fas fa-door-open text-[10px] text-base-content/40"></i>
                                                    <span x-text="card.ruang_name"></span>
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        <template x-if="selectedCard">
            <div x-show="selectedCard" x-transition.opacity @keydown.escape.window="selectedCard = null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                @click="selectedCard = null">
                <div @click.stop x-transition class="responsive-modal-panel max-w-lg">
                    <div class="modal-header-brand">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80"
                                x-text="selectedCard.sesi_name"></p>
                            <h3 class="mt-1 truncate text-xl font-black" x-text="selectedCard.mapel_name"></h3>
                            <p class="mt-1 text-sm opacity-90">
                                <i class="fas fa-chalkboard-user"></i> <span x-text="selectedCard.guru_name"></span>
                                &middot; <i class="fas fa-door-open"></i> <span x-text="selectedCard.ruang_name"></span>
                            </p>
                        </div>
                        <button type="button" @click="selectedCard = null" aria-label="Tutup"
                            class="btn btn-circle btn-ghost btn-sm shrink-0 bg-white/15 text-current hover:bg-white/25">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>
                    <div class="max-h-[50vh] overflow-y-auto p-5">
                        <p class="mb-3 text-xs font-black uppercase tracking-wider text-base-content/50">Daftar Siswa
                            (<span x-text="selectedCard.siswa_list.length"></span>)</p>
                        <div class="space-y-2">
                            <template x-for="siswa in selectedCard.siswa_list" :key="siswa.name">
                                <div class="flex items-center justify-between gap-3 rounded-btn border border-base-300 bg-base-200/60 px-3 py-2">
                                    <span class="text-sm font-bold text-base-content" x-text="siswa.name"></span>
                                    <span class="text-xs font-semibold text-base-content/50" x-text="siswa.kelas || '-'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </section>
</div>
