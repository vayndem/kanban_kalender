<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Absen — {{ config('app.name', 'E-Ling Course') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="app-canvas min-h-screen text-base-content">

        <x-portal-nav icon="fa-clipboard-user" title="Absen" wide
            :subtitle="$isAdmin ? 'Semua kelas' : 'Kelas ' . ($guru->name ?? '-') . ' + slot terbuka'">
            <a href="{{ route('modulAjar.index') }}" class="btn btn-ghost btn-sm text-xs">
                <i class="fas fa-book-open-reader"></i><span class="hidden sm:inline"> Modul Ajar</span>
            </a>
            <a href="{{ $isAdmin ? route('dashboard') : route('guru.jadwal') }}" class="btn btn-ghost btn-sm text-xs">
                <i class="fas fa-arrow-left"></i><span class="hidden sm:inline"> {{ $isAdmin ? 'Dashboard' : 'Jadwal Saya' }}</span>
            </a>
        </x-portal-nav>

        <main class="py-6 sm:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="absenHandler({
                isAdmin: @js($isAdmin),
                guruId: @js($guru?->id),
                initialKelasList: @js($kelasList),
                allHaris: @js($haris),
                routes: {
                    detailBase: @js(url('modul-ajar/detail')),
                },
            })">

                <div x-show="isLoading" x-cloak
                    class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-[2px] cursor-wait">
                    <div class="bg-base-100 rounded-2xl shadow-2xl px-6 py-5 flex items-center gap-3 border">
                        <i class="fas fa-circle-notch fa-spin text-success text-xl"></i>
                        <p class="text-sm font-bold text-base-content">Sedang diproses...</p>
                    </div>
                </div>

                <div class="bg-base-100 p-4 md:p-6 rounded-xl shadow-lg border border-base-300 mb-6">
                    <h3 class="text-lg md:text-xl font-bold text-base-content flex items-center gap-2">
                        <i class="fas fa-clipboard-user text-success"></i> Absen
                    </h3>
                    <p class="text-base-content/60 mt-0.5 text-xs md:text-sm">
                        Klik kelas untuk mulai mengajar, menandai tidak bisa hadir, atau menilai anak-anak setelah mengajar.
                        Kotak kuning = sedang dipersiapkan. Kotak merah = slot terbuka, siapa cepat dia dapat.
                    </p>

                    @if ($guru)
                        <div class="mt-3 inline-flex items-center gap-2 rounded-lg border border-success/40 bg-success/10 px-3 py-2 text-xs font-bold text-success">
                            <i class="fas fa-clipboard-check"></i> Sudah mengajar {{ $absenBulanIni }} sesi bulan ini
                        </div>
                    @elseif ($rekapAbsenGuru && $rekapAbsenGuru->isNotEmpty())
                        <div class="mt-3 rounded-lg border border-base-300 p-3">
                            <p class="text-xs font-bold text-base-content/60 uppercase tracking-wider mb-2">Rekap Absen Guru Bulan Ini</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($rekapAbsenGuru as $r)
                                    <span class="rounded-lg bg-base-200 px-2.5 py-1 text-xs font-bold text-base-content/70">
                                        {{ $r['nama'] }}: {{ $r['jumlah'] }} sesi
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mb-3 flex gap-1.5 overflow-x-auto pb-1 lg:hidden">
                    @foreach ($haris as $hari)
                        <button type="button" @click="activeDayMobile = {{ $hari->id }}"
                            :class="activeDayMobile === {{ $hari->id }} ? 'bg-success text-white' :
                                'bg-base-200 text-base-content/70'"
                            class="shrink-0 rounded-lg px-3 py-2 text-xs font-bold transition-colors">
                            {{ $hari->name }}
                        </button>
                    @endforeach
                </div>

                <div class="overflow-x-auto shadow-md rounded-lg">
                    <table class="min-w-full w-full border-collapse table-fixed">
                        <thead class="bg-base-200">
                            <tr>
                                <th class="sticky left-0 z-10 border border-base-300 bg-base-200 p-3 text-center uppercase text-xs tracking-wider font-semibold text-base-content/70 w-24 lg:w-32">
                                    Sesi
                                </th>
                                @foreach ($haris as $hari)
                                    <th :class="activeDayMobile === {{ $hari->id }} ? '' : 'hidden lg:table-cell'"
                                        class="border border-base-300 p-3 text-center uppercase text-xs tracking-wider font-semibold text-base-content/70 min-w-[220px]">
                                        {{ $hari->name }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-base-100">
                            @foreach ($sesis as $sesi)
                                <tr class="even:bg-base-200/50">
                                    <td class="sticky left-0 z-10 border border-base-300 bg-base-100 p-2 text-center align-middle font-semibold text-base-content/80">
                                        {{ $sesi->name }}
                                        <span class="block text-xs text-base-content/60 font-normal">
                                            {{ \Illuminate\Support\Str::of($sesi->start_time)->substr(0, 5) }} –
                                            {{ \Illuminate\Support\Str::of($sesi->end_time)->substr(0, 5) }}
                                        </span>
                                    </td>

                                    @foreach ($haris as $hari)
                                        <td :class="activeDayMobile === {{ $hari->id }} ? '' : 'hidden lg:table-cell'"
                                            class="border border-base-300 p-2 align-top h-40">
                                            <template x-for="kelas in kelasDi({{ $hari->id }}, {{ $sesi->id }})" :key="kelas.kode_kelas">
                                                <div @click="openKelas(kelas)"
                                                    class="group relative p-2.5 mb-2 rounded-lg shadow-sm border-l-4 text-sm cursor-pointer transition-all duration-200 ease-out hover:shadow-xl hover:-translate-y-0.5"
                                                    :class="adaSlotTerbuka(kelas) ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/30' : (adaSedangDipersiapkan(kelas) ? 'border-amber-400 bg-amber-50 dark:bg-amber-950/30' : 'border-success/40 bg-base-100')">

                                                    <strong class="block font-bold text-base-content truncate" x-text="kelas.mapel"></strong>
                                                    <span class="block text-base-content/70 mt-1" x-text="kelas.guru"></span>
                                                    <span class="block text-base-content/60 text-xs mt-1" x-text="'Ruang: ' + kelas.ruang"></span>

                                                    <span x-show="adaSlotTerbuka(kelas)" class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-rose-600 px-1.5 py-0.5 text-[11px] font-bold text-white">
                                                        <i class="fas fa-bolt"></i> Slot Terbuka
                                                    </span>
                                                    <span x-show="!adaSlotTerbuka(kelas) && adaSedangDipersiapkan(kelas)" class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-amber-500 px-1.5 py-0.5 text-[11px] font-bold text-white">
                                                        <i class="fas fa-hourglass-half"></i> Sedang dipersiapkan
                                                    </span>
                                                </div>
                                            </template>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Modal: daftar materi/pertemuan kelas ini --}}
                <template x-if="selectedKelas">
                    <div x-show="selectedKelas" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs" @click="closeModal()">
                        <div @click.stop x-transition class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-base-300 bg-base-100 shadow-2xl">
                            <div class="modal-header-brand sticky top-0 z-10">
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-white/75" x-text="selectedKelas.guru + ' · ' + selectedKelas.ruang"></p>
                                    <h3 class="mt-1 text-xl font-black truncate" x-text="selectedKelas.mapel"></h3>
                                </div>
                                <button type="button" @click="closeModal()" class="shrink-0 rounded-full bg-white/15 px-3 py-2 text-sm font-bold hover:bg-white/25">Tutup</button>
                            </div>

                            <div class="p-5 space-y-3">
                                <template x-if="!selectedKelas.ada_header || (selectedKelas.modul_ajar.details || []).length === 0">
                                    <p class="text-xs text-base-content/60 italic text-center py-6">
                                        Belum ada rincian materi untuk kelas ini. Isi dulu lewat menu
                                        <a href="{{ route('modulAjar.index') }}" class="font-bold text-success hover:underline">Modul Ajar</a>.
                                    </p>
                                </template>

                                <template x-for="d in (selectedKelas.modul_ajar?.details || [])" :key="d.id">
                                    <div class="rounded-lg border p-3"
                                        :class="d.tidak_bisa_hadir ? 'border-rose-300 bg-rose-50/60 dark:bg-rose-950/20' : (d.sedang_dipersiapkan ? 'border-amber-300 bg-amber-50/60 dark:bg-amber-950/20' : (d.diajarkan_oleh_guru_id ? 'border-success/40 bg-success/10' : 'border-base-300'))">
                                        <p class="text-sm font-bold text-base-content">
                                            <i x-show="d.diajarkan_oleh_guru_id" class="fas fa-circle-check text-success mr-1"></i>
                                            <span x-text="d.materi"></span>
                                        </p>
                                        <p x-show="d.sub_materi" class="text-xs text-base-content/60" x-text="d.sub_materi"></p>

                                        <p x-show="d.diajarkan_oleh_guru_id" class="text-xs text-success mt-1">
                                            Diajarkan oleh <span x-text="d.diajarkan_oleh_guru?.name"></span> pada <span x-text="d.tanggal_diajarkan"></span>
                                        </p>
                                        <p x-show="d.tidak_bisa_hadir" class="text-xs font-bold text-error mt-1">
                                            <i class="fas fa-bolt"></i> Terbuka untuk siapa saja — belum ada yang ambil.
                                        </p>
                                        <p x-show="d.sedang_dipersiapkan" class="text-xs font-bold text-warning mt-1">
                                            <i class="fas fa-hourglass-half"></i> Sedang dipersiapkan<span x-show="d.guru_pengganti"> oleh <span x-text="d.guru_pengganti?.name"></span></span>.
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <template x-if="!d.sedang_dipersiapkan && !d.tidak_bisa_hadir && !d.diajarkan_oleh_guru_id">
                                                <button type="button" @click="mulaiAjar(d)" class="btn btn-accent text-xs px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-chalkboard-user"></i> Mulai Ajar
                                                </button>
                                            </template>
                                            <template x-if="!d.sedang_dipersiapkan && !d.tidak_bisa_hadir && !d.diajarkan_oleh_guru_id && isPemilikKelas(selectedKelas)">
                                                <button type="button" @click="tandaiTidakBisaHadir(d)" class="btn btn-sacred text-xs px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-user-slash"></i> Tidak Bisa Hadir
                                                </button>
                                            </template>
                                            <template x-if="d.tidak_bisa_hadir">
                                                <button type="button" @click="ambilKelas(d)" class="btn btn-warning text-xs px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-bolt"></i> Ambil Kelas Ini
                                                </button>
                                            </template>
                                            <template x-if="d.sedang_dipersiapkan">
                                                <button type="button" @click="bukaNilai(d)" class="btn btn-warning text-xs px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-clipboard-list"></i> Lihat Roster / Nilai
                                                </button>
                                            </template>
                                            <template x-if="!d.sedang_dipersiapkan && !d.tidak_bisa_hadir && d.diajarkan_oleh_guru_id && isPemilikKelas(selectedKelas)">
                                                <button type="button" @click="mulaiAjar(d)" class="btn btn-neutral text-xs px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-rotate"></i> Ajar Ulang
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Sub-panel: persiapan (roster) / nilai (grading) --}}
                <template x-if="pengajaranDetail">
                    <div x-show="pengajaranDetail" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4 backdrop-blur-xs" @click="tutupPengajaran()">
                        <div @click.stop x-transition class="w-full max-w-lg max-h-[85vh] overflow-y-auto rounded-2xl border border-base-300 bg-base-100 shadow-2xl">
                            <div class="flex items-start justify-between gap-4 bg-gradient-to-r from-amber-500 to-orange-500 p-4 text-white sticky top-0">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-warning" x-text="pengajaranTahap === 'nilai' ? 'Penilaian' : 'Persiapan'"></p>
                                    <h3 class="mt-1 text-lg font-black truncate" x-text="pengajaranDetail.materi"></h3>
                                </div>
                                <button type="button" @click="tutupPengajaran()" class="shrink-0 rounded-full bg-white/15 px-3 py-2 text-sm font-bold hover:bg-white/25">Tutup</button>
                            </div>

                            <div class="p-4 space-y-4">
                                <template x-if="pengajaranTahap === 'persiapan'">
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-xs font-bold text-base-content/60 uppercase tracking-wider mb-2">Daftar Anak (<span x-text="(selectedKelas.siswa_list || []).length"></span>)</p>
                                            <div class="space-y-1">
                                                <template x-for="s in (selectedKelas.siswa_list || [])" :key="s.id">
                                                    <div class="rounded-lg bg-base-200 px-3 py-2 text-sm font-semibold text-base-content/80" x-text="s.panggilan || s.name"></div>
                                                </template>
                                            </div>
                                        </div>

                                        <button type="button" @click="bukaNilai(pengajaranDetail)" class="btn btn-primary text-sm w-full">
                                            <i class="fas fa-arrow-right"></i> Lanjut ke Penilaian
                                        </button>
                                    </div>
                                </template>

                                <template x-if="pengajaranTahap === 'nilai'">
                                    <form @submit.prevent="simpanNilai()" class="space-y-3">
                                        <template x-for="item in nilaiForm" :key="item.siswa_id">
                                            <div class="flex items-center gap-2 rounded-lg border border-base-300 p-2.5">
                                                <span class="flex-1 text-sm font-bold text-base-content" x-text="item.nama"></span>
                                                <label class="flex items-center gap-1 text-xs font-semibold text-base-content/60">
                                                    <input type="checkbox" x-model="item.hadir"> Hadir
                                                </label>
                                                <select x-show="item.hadir" x-model="item.nilai" :required="item.hadir"
                                                    class="rounded-lg border border-base-300 p-1.5 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden">
                                                    <option value="">Nilai</option>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="5">5</option>
                                                </select>
                                            </div>
                                        </template>
                                        <button type="submit" class="btn btn-primary text-sm w-full" :disabled="isLoading">
                                            <i class="fas fa-check"></i> Simpan Nilai & Selesaikan Pertemuan
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </main>
    </div>
</body>

</html>
