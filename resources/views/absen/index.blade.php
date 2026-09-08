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
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">

        <nav class="border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex min-h-16 items-center justify-between py-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-clipboard-user text-emerald-500 text-lg"></i>
                        <div class="min-w-0">
                            <p class="text-sm font-bold truncate">Absen</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                {{ $isAdmin ? 'Semua kelas' : 'Kelas ' . ($guru->name ?? '-') . ' + slot terbuka' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('modulAjar.index') }}"
                            class="text-xs font-bold text-slate-500 hover:text-emerald-600 transition-colors px-3 py-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-950/30">
                            <i class="fas fa-book-open-reader sm:mr-1"></i><span class="hidden sm:inline"> Modul Ajar</span>
                        </a>
                        <a href="{{ $isAdmin ? route('dashboard') : route('guru.jadwal') }}"
                            class="text-xs font-bold text-slate-500 hover:text-emerald-600 transition-colors px-3 py-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-950/30">
                            <i class="fas fa-arrow-left sm:mr-1"></i><span class="hidden sm:inline"> {{ $isAdmin ? 'Dashboard' : 'Jadwal Saya' }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="text-xs font-bold text-slate-500 hover:text-red-500 transition-colors px-3 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30">
                                <i class="fas fa-right-from-bracket sm:mr-1"></i><span class="hidden sm:inline"> Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

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
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl px-6 py-5 flex items-center gap-3 border dark:border-gray-700">
                        <i class="fas fa-circle-notch fa-spin text-emerald-500 text-xl"></i>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Sedang diproses...</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-4 md:p-6 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 mb-6">
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-clipboard-user text-emerald-500"></i> Absen
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-0.5 text-xs md:text-sm">
                        Klik kelas untuk mulai mengajar, menandai tidak bisa hadir, atau menilai anak-anak setelah mengajar.
                        Kotak kuning = sedang dipersiapkan. Kotak merah = slot terbuka, siapa cepat dia dapat.
                    </p>

                    @if ($guru)
                        <div class="mt-3 inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                            <i class="fas fa-clipboard-check"></i> Sudah mengajar {{ $absenBulanIni }} sesi bulan ini
                        </div>
                    @elseif ($rekapAbsenGuru && $rekapAbsenGuru->isNotEmpty())
                        <div class="mt-3 rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Rekap Absen Guru Bulan Ini</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($rekapAbsenGuru as $r)
                                    <span class="rounded-lg bg-slate-100 dark:bg-slate-700 px-2.5 py-1 text-xs font-bold text-slate-600 dark:text-slate-200">
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
                            :class="activeDayMobile === {{ $hari->id }} ? 'bg-emerald-600 text-white' :
                                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                            class="shrink-0 rounded-lg px-3 py-2 text-xs font-bold transition-colors">
                            {{ $hari->name }}
                        </button>
                    @endforeach
                </div>

                <div class="overflow-x-auto shadow-md rounded-lg">
                    <table class="min-w-full w-full border-collapse table-fixed">
                        <thead class="bg-gray-100 dark:bg-gray-700/80">
                            <tr>
                                <th class="sticky left-0 z-10 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/80 p-3 text-center uppercase text-xs tracking-wider font-semibold text-gray-600 dark:text-white w-24 lg:w-32">
                                    Sesi
                                </th>
                                @foreach ($haris as $hari)
                                    <th :class="activeDayMobile === {{ $hari->id }} ? '' : 'hidden lg:table-cell'"
                                        class="border border-gray-300 dark:border-gray-600 p-3 text-center uppercase text-xs tracking-wider font-semibold text-gray-600 dark:text-white min-w-[220px]">
                                        {{ $hari->name }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                            @foreach ($sesis as $sesi)
                                <tr class="even:bg-gray-50/50 dark:even:bg-gray-800/60">
                                    <td class="sticky left-0 z-10 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-2 text-center align-middle font-semibold text-gray-700 dark:text-white">
                                        {{ $sesi->name }}
                                        <span class="block text-xs text-gray-500 dark:text-gray-300 font-normal">
                                            {{ \Illuminate\Support\Str::of($sesi->start_time)->substr(0, 5) }} –
                                            {{ \Illuminate\Support\Str::of($sesi->end_time)->substr(0, 5) }}
                                        </span>
                                    </td>

                                    @foreach ($haris as $hari)
                                        <td :class="activeDayMobile === {{ $hari->id }} ? '' : 'hidden lg:table-cell'"
                                            class="border border-gray-200 dark:border-gray-600 p-2 align-top h-40">
                                            <template x-for="kelas in kelasDi({{ $hari->id }}, {{ $sesi->id }})" :key="kelas.kode_kelas">
                                                <div @click="openKelas(kelas)"
                                                    class="group relative p-2.5 mb-2 rounded-lg shadow border-l-4 text-sm cursor-pointer transition-all duration-200 ease-out hover:shadow-xl hover:-translate-y-0.5"
                                                    :class="adaSlotTerbuka(kelas) ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/30' : (adaSedangDipersiapkan(kelas) ? 'border-amber-400 bg-amber-50 dark:bg-amber-950/30' : 'border-emerald-400 bg-white dark:bg-gray-700/90')">

                                                    <strong class="block font-bold text-gray-900 dark:text-white truncate" x-text="kelas.mapel"></strong>
                                                    <span class="block text-gray-600 dark:text-gray-200 mt-1" x-text="kelas.guru"></span>
                                                    <span class="block text-gray-500 dark:text-gray-300 text-xs mt-1" x-text="'Ruang: ' + kelas.ruang"></span>

                                                    <span x-show="adaSlotTerbuka(kelas)" class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                                        <i class="fas fa-bolt"></i> Slot Terbuka
                                                    </span>
                                                    <span x-show="!adaSlotTerbuka(kelas) && adaSedangDipersiapkan(kelas)" class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
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
                    <div x-show="selectedKelas" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" @click="closeModal()">
                        <div @click.stop x-transition class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-4 bg-gradient-to-r from-emerald-600 to-teal-600 p-5 text-white sticky top-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-100" x-text="selectedKelas.guru + ' · ' + selectedKelas.ruang"></p>
                                    <h3 class="mt-1 text-xl font-black truncate" x-text="selectedKelas.mapel"></h3>
                                </div>
                                <button type="button" @click="closeModal()" class="shrink-0 rounded-full bg-white/15 px-3 py-2 text-sm font-bold hover:bg-white/25">Tutup</button>
                            </div>

                            <div class="p-5 space-y-3">
                                <template x-if="!selectedKelas.ada_header || (selectedKelas.modul_ajar.details || []).length === 0">
                                    <p class="text-xs text-gray-400 italic text-center py-6">
                                        Belum ada rincian materi untuk kelas ini. Isi dulu lewat menu
                                        <a href="{{ route('modulAjar.index') }}" class="font-bold text-emerald-600 hover:underline">Modul Ajar</a>.
                                    </p>
                                </template>

                                <template x-for="d in (selectedKelas.modul_ajar?.details || [])" :key="d.id">
                                    <div class="rounded-lg border p-3"
                                        :class="d.tidak_bisa_hadir ? 'border-rose-300 bg-rose-50/60 dark:bg-rose-950/20' : (d.sedang_dipersiapkan ? 'border-amber-300 bg-amber-50/60 dark:bg-amber-950/20' : (d.diajarkan_oleh_guru_id ? 'border-emerald-200 bg-emerald-50/40 dark:bg-emerald-950/10' : 'border-gray-100 dark:border-gray-700'))">
                                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100">
                                            <i x-show="d.diajarkan_oleh_guru_id" class="fas fa-circle-check text-emerald-500 mr-1"></i>
                                            <span x-text="d.materi"></span>
                                        </p>
                                        <p x-show="d.sub_materi" class="text-xs text-gray-500 dark:text-gray-400" x-text="d.sub_materi"></p>

                                        <p x-show="d.diajarkan_oleh_guru_id" class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1">
                                            Diajarkan oleh <span x-text="d.diajarkan_oleh_guru?.name"></span> pada <span x-text="d.tanggal_diajarkan"></span>
                                        </p>
                                        <p x-show="d.tidak_bisa_hadir" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 mt-1">
                                            <i class="fas fa-bolt"></i> Terbuka untuk siapa saja — belum ada yang ambil.
                                        </p>
                                        <p x-show="d.sedang_dipersiapkan" class="text-[11px] font-bold text-amber-600 dark:text-amber-400 mt-1">
                                            <i class="fas fa-hourglass-half"></i> Sedang dipersiapkan<span x-show="d.guru_pengganti"> oleh <span x-text="d.guru_pengganti?.name"></span></span>.
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <template x-if="!d.sedang_dipersiapkan && !d.tidak_bisa_hadir && !d.diajarkan_oleh_guru_id">
                                                <button type="button" @click="mulaiAjar(d)" class="btn btn-accent text-[11px] px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-chalkboard-user"></i> Mulai Ajar
                                                </button>
                                            </template>
                                            <template x-if="!d.sedang_dipersiapkan && !d.tidak_bisa_hadir && !d.diajarkan_oleh_guru_id && isPemilikKelas(selectedKelas)">
                                                <button type="button" @click="tandaiTidakBisaHadir(d)" class="btn-sacred text-[11px] px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-user-slash"></i> Tidak Bisa Hadir
                                                </button>
                                            </template>
                                            <template x-if="d.tidak_bisa_hadir">
                                                <button type="button" @click="ambilKelas(d)" class="btn btn-warning text-[11px] px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-bolt"></i> Ambil Kelas Ini
                                                </button>
                                            </template>
                                            <template x-if="d.sedang_dipersiapkan">
                                                <button type="button" @click="bukaNilai(d)" class="btn btn-warning text-[11px] px-2.5 py-1 rounded-md">
                                                    <i class="fas fa-clipboard-list"></i> Lihat Roster / Nilai
                                                </button>
                                            </template>
                                            <template x-if="!d.sedang_dipersiapkan && !d.tidak_bisa_hadir && d.diajarkan_oleh_guru_id && isPemilikKelas(selectedKelas)">
                                                <button type="button" @click="mulaiAjar(d)" class="btn btn-neutral text-[11px] px-2.5 py-1 rounded-md">
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
                    <div x-show="pengajaranDetail" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" @click="tutupPengajaran()">
                        <div @click.stop x-transition class="w-full max-w-lg max-h-[85vh] overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-4 bg-gradient-to-r from-amber-500 to-orange-500 p-4 text-white sticky top-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-100" x-text="pengajaranTahap === 'nilai' ? 'Penilaian' : 'Persiapan'"></p>
                                    <h3 class="mt-1 text-lg font-black truncate" x-text="pengajaranDetail.materi"></h3>
                                </div>
                                <button type="button" @click="tutupPengajaran()" class="shrink-0 rounded-full bg-white/15 px-3 py-2 text-sm font-bold hover:bg-white/25">Tutup</button>
                            </div>

                            <div class="p-4 space-y-4">
                                <template x-if="pengajaranTahap === 'persiapan'">
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Daftar Anak (<span x-text="(selectedKelas.siswa_list || []).length"></span>)</p>
                                            <div class="space-y-1">
                                                <template x-for="s in (selectedKelas.siswa_list || [])" :key="s.id">
                                                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900/40 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="s.panggilan || s.name"></div>
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
                                            <div class="flex items-center gap-2 rounded-lg border border-gray-100 dark:border-gray-700 p-2.5">
                                                <span class="flex-1 text-sm font-bold text-gray-800 dark:text-gray-100" x-text="item.nama"></span>
                                                <label class="flex items-center gap-1 text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                                                    <input type="checkbox" x-model="item.hadir"> Hadir
                                                </label>
                                                <select x-show="item.hadir" x-model="item.nilai" :required="item.hadir"
                                                    class="rounded-lg border border-gray-300 dark:border-gray-600 p-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
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
