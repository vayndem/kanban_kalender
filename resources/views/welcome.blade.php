@extends('layouts.masters.master')

@section('title', 'E-Ling Course | Home')

@section('content')
    <div class="space-y-6 p-4 sm:p-6 md:space-y-10" x-data="{ showSiswaModal: false, modalTitle: '', modalStudents: [] }">

        <div class="relative overflow-hidden rounded-box bg-gradient-to-br from-primary to-accent p-6 shadow-2xl sm:p-10">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-black/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-col items-center justify-between gap-10 lg:flex-row">
                <div class="max-w-2xl text-center lg:text-left">
                    <span class="mb-4 inline-block rounded-full bg-white/20 px-4 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-xs">
                        Official Learning Center
                    </span>
                    <h1 class="mb-4 text-3xl font-extrabold leading-tight text-white sm:text-4xl md:text-5xl">
                        Membangun Masa Depan <br><span class="text-white/70">Bersama E-Ling Course</span>
                    </h1>
                    <p class="mb-8 text-base text-white/80 sm:text-lg">
                        Pantau aktivitas belajar mengajar dan jadwal kelas harian secara real-time di sini.
                    </p>
                    <div class="flex flex-col flex-wrap justify-center gap-3 sm:flex-row lg:justify-start">
                        <a href="#jadwal" class="btn border-none bg-white text-primary shadow-lg hover:bg-white/90">
                            <i class="fas fa-calendar-day"></i> Lihat Jadwal Hari Ini
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}"
                                class="btn border-white/30 bg-white/10 text-white backdrop-blur-xs hover:border-white/50 hover:bg-white/20">
                                Dashboard Admin
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                                class="btn border-white/30 bg-white/10 text-white backdrop-blur-xs hover:border-white/50 hover:bg-white/20">
                                Login Staf
                            </a>
                        @endauth
                    </div>
                </div>
                <div class="hidden lg:block">
                    <div class="rounded-full bg-white/10 p-8 backdrop-blur-xs">
                        <i class="fas fa-graduation-cap fa-10x text-white/20"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
            @foreach ([['fa-users', $stats['total_siswa'], 'Siswa Terdaftar', 'bg-primary/10 text-primary'], ['fa-chalkboard-teacher', $stats['kelas_aktif'], 'Sesi Aktif Hari Ini', 'bg-success/10 text-success'], ['fa-user-tie', $stats['pengajar'], 'Tenaga Pendidik', 'bg-accent/10 text-accent']] as $i => [$ikon, $angka, $label, $tone])
                <div class="app-card-hover p-6 text-center sm:p-8 {{ $i === 2 ? 'sm:col-span-2 lg:col-span-1' : '' }}">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-box {{ $tone }}">
                        <i class="fas {{ $ikon }} fa-xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-base-content sm:text-3xl">{{ $angka }}</h3>
                    <p class="mt-1 text-xs font-bold uppercase tracking-widest text-base-content/60 sm:text-sm">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        <div id="jadwal" class="grid grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-3">
            <div class="app-card p-4 sm:p-8 lg:col-span-2">
                <div class="mb-6 flex flex-col justify-between gap-4 sm:mb-8 sm:flex-row sm:items-center">
                    <h4 class="flex items-center gap-3 text-lg font-black text-base-content sm:text-xl">
                        <span class="h-8 w-2 rounded-full bg-gradient-to-b from-primary to-accent"></span>
                        Aktivitas Belajar Hari Ini
                    </h4>
                    <span class="self-start rounded-field bg-base-200 px-4 py-1.5 text-xs font-bold text-base-content/60 sm:self-auto">
                        {{ \Carbon\Carbon::now()->translatedFormat('l, d F') }}
                    </span>
                </div>

                <div class="space-y-4 sm:space-y-6">
                    @forelse($listJadwal as $namaSesi => $jadwals)
                        <div class="flex flex-col gap-4 rounded-box border border-base-300 bg-base-200/60 p-4 sm:gap-6 sm:p-6 md:flex-row">
                            <div class="flex w-full shrink-0 items-center justify-between gap-1 border-b border-base-300 pb-4 text-center md:w-28 md:flex-col md:justify-center md:border-b-0 md:border-r md:pb-0 md:pr-6">
                                <span class="text-xs font-black uppercase tracking-widest text-primary">Waktu</span>
                                <div class="text-lg font-black text-base-content sm:text-xl md:mt-1">{{ $namaSesi }}</div>
                                <div class="text-[11px] font-semibold text-base-content/60">
                                    {{ $jadwals->first()->sesi->start_time }} - {{ $jadwals->first()->sesi->end_time }}
                                </div>
                            </div>

                            <div class="grid grow grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ($jadwals as $j)
                                    <button type="button"
                                        @click="modalTitle = '{{ $j->mataPelajaran->name }}'; modalStudents = {{ $j->slot_students->toJson() }}; showSiswaModal = true"
                                        class="app-card-hover flex flex-col justify-between gap-4 p-4 text-left">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <div class="break-words text-sm font-bold leading-tight text-base-content">
                                                    {{ $j->mataPelajaran->name }}
                                                </div>
                                                <div class="mt-1 flex items-center gap-1 truncate text-xs font-bold text-primary">
                                                    <i class="fas fa-user-tie shrink-0 text-[10px]"></i>
                                                    <span class="truncate">{{ $j->guru->name }}</span>
                                                </div>
                                            </div>
                                            <div class="app-chip shrink-0 whitespace-nowrap">
                                                {{ $j->ruang->name }}
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="app-empty py-16 sm:py-20">
                            <div class="app-empty-icon"><i class="fas fa-mug-hot"></i></div>
                            <p class="app-empty-title">Tidak ada jadwal belajar untuk hari ini.</p>
                            <p class="app-empty-text">Silakan cek kembali besok, atau lihat kalender jadwal lengkap.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div class="app-card overflow-hidden">
                    <div class="h-1.5 bg-gradient-to-r from-primary to-accent"></div>
                    <div class="p-6 sm:p-8">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-box bg-primary/10 text-primary">
                            <i class="fas fa-circle-info fa-lg"></i>
                        </div>
                        <h4 class="mb-2 font-black text-base-content">Informasi Pendaftaran</h4>
                        <p class="text-sm leading-relaxed text-base-content/70">
                            Tertarik bergabung? Hubungi kami untuk konsultasi penempatan kelas sesuai kemampuan ananda.
                        </p>
                        <a href="{{ route('jadwal.kalender') }}" class="btn btn-primary mt-6 w-full">
                            <i class="fas fa-calendar-days"></i> Lihat Kalender Jadwal
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <template x-if="showSiswaModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs" x-transition
                @keydown.escape.window="showSiswaModal = false">
                <div @click="showSiswaModal = false" class="absolute inset-0"></div>
                <div class="responsive-modal-panel relative m-2 max-w-md" @click.stop>
                    <div class="flex items-center justify-between border-b border-base-300 bg-base-200 p-4">
                        <div class="min-w-0 flex-1 pr-2">
                            <h3 class="truncate text-base font-black text-base-content" x-text="modalTitle"></h3>
                            <p class="mt-0.5 text-xs text-base-content/60">Daftar Siswa Terjadwal</p>
                        </div>
                        <button @click="showSiswaModal = false" aria-label="Tutup"
                            class="btn btn-circle btn-ghost btn-sm shrink-0">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="max-h-[50vh] space-y-2 overflow-y-auto p-4 sm:max-h-[60vh] sm:p-6">
                        <template x-for="(siswa, index) in modalStudents" :key="index">
                            <div class="flex min-w-0 items-center justify-between gap-2 rounded-field border border-base-300 bg-base-200/60 p-3">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-field bg-primary/10 text-sm font-black text-primary"
                                        x-text="siswa.name.charAt(0)"></div>
                                    <span class="truncate text-sm font-semibold text-base-content" x-text="siswa.name"></span>
                                </div>
                                <span class="badge badge-primary badge-sm shrink-0 whitespace-nowrap font-bold"
                                    x-text="'Kelas ' + siswa.kelas"></span>
                            </div>
                        </template>
                        <template x-if="modalStudents.length === 0">
                            <div class="app-empty border-0 py-8">
                                <div class="app-empty-icon"><i class="fas fa-users-slash"></i></div>
                                <p class="app-empty-title">Belum ada siswa di kelas ini.</p>
                            </div>
                        </template>
                    </div>
                    <div class="flex justify-end border-t border-base-300 bg-base-200 p-4">
                        <button @click="showSiswaModal = false" class="btn btn-neutral btn-sm w-full sm:w-auto">Tutup</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection
