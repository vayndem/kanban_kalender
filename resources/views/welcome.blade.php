@extends('layouts.masters.master')

@section('title', 'E-Ling Course | Home')

@section('content')
    <div class="p-6 space-y-10" x-data="{ showSiswaModal: false, modalTitle: '', modalStudents: [] }">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 to-teal-700 p-10 shadow-2xl">
            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-10">
                <div class="text-center lg:text-left max-w-2xl">
                    <span
                        class="inline-block px-4 py-1 rounded-full bg-white/20 text-white text-xs font-bold uppercase tracking-wider mb-4">
                        Official Learning Center
                    </span>
                    <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4 leading-tight">
                        Membangun Masa Depan <br><span class="text-emerald-200">Bersama E-Ling Course</span>
                    </h1>
                    <p class="text-emerald-50 text-lg opacity-90 mb-8">
                        Pantau aktivitas belajar mengajar dan jadwal kelas harian secara real-time di sini.
                    </p>
                    <div class="flex flex-wrap justify-center lg:justify-start gap-4">
                        <a href="#jadwal"
                            class="bg-white text-emerald-700 px-8 py-3 rounded-2xl font-bold hover:bg-emerald-50 transition-all shadow-lg flex items-center gap-2">
                            <i class="fas fa-calendar-day"></i> Lihat Jadwal Hari Ini
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}"
                                class="bg-emerald-800/40 text-white border border-emerald-400/30 px-8 py-3 rounded-2xl font-bold hover:bg-emerald-800/60 transition-all">
                                Dashboard Admin
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="bg-emerald-800/40 text-white border border-emerald-400/30 px-8 py-3 rounded-2xl font-bold hover:bg-emerald-800/60 transition-all">
                                Login Staf
                            </a>
                        @endauth
                    </div>
                </div>
                <div class="hidden lg:block">
                    <div class="bg-white/10 p-8 rounded-full backdrop-blur-sm">
                        <i class="fas fa-graduation-cap fa-10x text-white/20"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div
                class="bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm text-center group hover:border-emerald-500 transition-all">
                <div
                    class="w-14 h-14 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users fa-xl"></i>
                </div>
                <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ $stats['total_siswa'] }}</h3>
                <p class="text-sm font-bold text-gray-500 uppercase tracking-widest mt-1">Siswa Terdaftar</p>
            </div>

            <div
                class="bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm text-center group hover:border-emerald-500 transition-all">
                <div
                    class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chalkboard-teacher fa-xl"></i>
                </div>
                <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ $stats['kelas_aktif'] }}</h3>
                <p class="text-sm font-bold text-gray-500 uppercase tracking-widest mt-1">Sesi Aktif Hari Ini</p>
            </div>

            <div
                class="bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm text-center group hover:border-emerald-500 transition-all">
                <div
                    class="w-14 h-14 bg-purple-50 dark:bg-purple-900/20 text-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-tie fa-xl"></i>
                </div>
                <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ $stats['pengajar'] }}</h3>
                <p class="text-sm font-bold text-gray-500 uppercase tracking-widest mt-1">Tenaga Pendidik</p>
            </div>
        </div>

        <div id="jadwal" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div
                class="lg:col-span-2 bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>
                        Aktivitas Belajar Hari Ini
                    </h4>
                    <span class="px-4 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-xl text-xs font-bold text-gray-500">
                        {{ \Carbon\Carbon::now()->translatedFormat('l, d F') }}
                    </span>
                </div>

                <div class="space-y-6">
                    @forelse($listJadwal as $namaSesi => $jadwals)
                        <div
                            class="flex flex-col md:flex-row gap-6 p-6 rounded-3xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 shadow-sm">
                            <div
                                class="flex-shrink-0 w-full md:w-24 text-center border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 pb-4 md:pb-0 md:pr-6">
                                <span
                                    class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Waktu</span>
                                <div class="text-xl font-black text-gray-900 dark:text-white mt-1">{{ $namaSesi }}</div>
                                <div class="text-[10px] text-gray-400 font-medium">{{ $jadwals->first()->sesi->start_time }}
                                    - {{ $jadwals->first()->sesi->end_time }}</div>
                            </div>

                            <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($jadwals as $j)
                                    @php
                                        $slotStudents = $jadwalsData
                                            ->filter(function ($item) use ($j) {
                                                return $item->hari_id == $j->hari_id &&
                                                    $item->sesi_id == $j->sesi_id &&
                                                    $item->mata_pelajaran_id == $j->mata_pelajaran_id &&
                                                    $item->guru_id == $j->guru_id &&
                                                    $item->siswa;
                                            })
                                            ->map(function ($item) {
                                                return [
                                                    'name' => $item->siswa->name,
                                                    'kelas' => $item->siswa->kelas ?? 'N/A',
                                                ];
                                            })
                                            ->values();
                                    @endphp
                                    <div @click="modalTitle = '{{ $j->mataPelajaran->name }}'; modalStudents = {{ $slotStudents->toJson() }}; showSiswaModal = true"
                                        class="bg-white dark:bg-gray-700/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-600 shadow-sm transition-all hover:shadow-md cursor-pointer">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <div class="font-bold text-sm text-gray-800 dark:text-white leading-tight">
                                                    {{ $j->mataPelajaran->name }}
                                                </div>
                                                <div
                                                    class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold mt-1 flex items-center gap-1">
                                                    <i class="fas fa-user-tie text-[9px]"></i>
                                                    {{ $j->guru->name }}
                                                </div>
                                            </div>
                                            <div
                                                class="text-[10px] bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-lg text-gray-500 dark:text-gray-300 font-bold">
                                                {{ $j->ruang->name }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div
                            class="text-center py-20 bg-gray-50 dark:bg-gray-800/50 rounded-3xl border border-dashed border-gray-200 dark:border-gray-700">
                            <i class="fas fa-mug-hot text-gray-300 fa-3x mb-4"></i>
                            <p class="text-gray-400 italic">Tidak ada jadwal belajar untuk hari ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div
                    class="bg-emerald-50 dark:bg-emerald-900/10 p-8 rounded-3xl border border-emerald-100 dark:border-emerald-800/30">
                    <div
                        class="w-12 h-12 bg-white dark:bg-emerald-900/50 rounded-2xl flex items-center justify-center mb-4 shadow-sm">
                        <i class="fas fa-info text-emerald-600"></i>
                    </div>
                    <h4 class="font-bold text-emerald-900 dark:text-emerald-400 mb-2">Informasi Pendaftaran</h4>
                    <p class="text-sm text-emerald-700 dark:text-emerald-500/80 leading-relaxed">
                        Tertarik bergabung? Hubungi kami untuk konsultasi penempatan kelas sesuai kemampuan ananda.
                    </p>
                    <button
                        class="mt-6 w-full py-3 bg-emerald-600 text-white rounded-2xl font-bold shadow-lg shadow-emerald-200 dark:shadow-none hover:bg-emerald-700 transition-all">
                        Hubungi Admin
                    </button>
                </div>
            </div>
        </div>

        <div x-show="showSiswaModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            style="display: none;" x-transition>
            <div @click="showSiswaModal = false" class="absolute inset-0"></div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative border dark:border-gray-700"
                @click.stop>
                <div
                    class="p-4 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-base" x-text="modalTitle"></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar Siswa Terjadwal</p>
                    </div>
                    <button @click="showSiswaModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>
                <div class="p-6 max-h-[60vh] overflow-y-auto space-y-2">
                    <template x-for="(siswa, index) in modalStudents" :key="index">
                        <div
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-100 dark:border-gray-600">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm"
                                    x-text="siswa.name.charAt(0)"></div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-white"
                                    x-text="siswa.name"></span>
                            </div>
                            <span
                                class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30"
                                x-text="'Kelas ' + siswa.kelas"></span>
                        </div>
                    </template>
                    <template x-if="modalStudents.length === 0">
                        <div class="text-center py-8">
                            <i class="fas fa-users-slash text-gray-300 dark:text-gray-600 text-3xl mb-2"></i>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Belum ada siswa di kelas ini.</p>
                        </div>
                    </template>
                </div>
                <div class="p-4 border-t dark:border-gray-700 flex justify-end bg-gray-50 dark:bg-gray-900">
                    <button @click="showSiswaModal = false"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-white rounded-xl text-sm font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection
