@extends('layouts.masters.master')

@section('title', 'Kalender Jadwal')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-3 py-5 sm:px-6 lg:px-8 lg:py-8" x-data="calendarApp(@js(route('jadwal.kalender.export')))" x-init="init()">
        <div class="mb-6 overflow-hidden rounded-box bg-gradient-to-br from-primary to-accent p-5 text-white shadow-xl sm:p-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.2em] text-white/70">E-ling Course</p>
                    <h1 class="mt-1 text-2xl font-black sm:text-3xl">Kalender Jadwal</h1>
                    <p class="mt-2 max-w-2xl text-sm text-white/80">Cari jadwal berdasarkan hari, sesi, pelajaran, guru,
                        ruang, atau siswa.</p>
                </div>
                <a :href="exportUrl"
                    class="btn border-none bg-white text-primary shadow-lg hover:bg-white/90">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </div>

        <div class="mb-3 grid gap-3 sm:grid-cols-[1fr_auto]">
            <label class="relative block">
                <span class="sr-only">Cari jadwal</span>
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-base-content/60"></i>
                <input type="search" x-model.debounce.200ms="query" placeholder="Cari jadwal..."
                    class="input min-h-12 w-full pl-11 pr-10">
                <button x-show="query" @click="query = ''" type="button" aria-label="Hapus pencarian"
                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-base-content/60 hover:bg-base-200">&times;</button>
            </label>
            <div
                class="flex min-h-12 items-center rounded-xl border border-base-300 bg-base-100 px-4 text-sm font-semibold text-base-content/70 shadow-xs">
                <i class="far fa-calendar mr-2 text-primary"></i><span x-text="todayLabel"></span>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <select x-model="filterHari"
                class="select min-h-11 w-full text-sm">
                <option value="">Semua Hari</option>
                @foreach ($haris as $hari)
                    <option value="{{ $hari->id }}">{{ $hari->name }}</option>
                @endforeach
            </select>
            <select x-model="filterMapel"
                class="select min-h-11 w-full text-sm">
                <option value="">Semua Mata Pelajaran</option>
                @foreach ($mapels as $mapel)
                    <option value="{{ $mapel->id }}">{{ $mapel->name }}</option>
                @endforeach
            </select>
            <select x-model="filterGuru"
                class="select min-h-11 w-full text-sm">
                <option value="">Semua Guru</option>
                @foreach ($gurus as $guru)
                    <option value="{{ $guru->id }}">{{ $guru->name }}</option>
                @endforeach
            </select>
            <select x-model="filterRuang"
                class="select min-h-11 w-full text-sm">
                <option value="">Semua Ruang</option>
                @foreach ($ruangs as $ruang)
                    <option value="{{ $ruang->id }}">{{ $ruang->name }}</option>
                @endforeach
            </select>
        </div>

        <div x-show="hasActiveFilters" class="mb-6 -mt-3">
            <button type="button" @click="resetFilters()"
                class="btn btn-ghost btn-xs text-primary">
                <i class="fas fa-rotate-left"></i> Reset pencarian & filter
            </button>
        </div>

        {{-- Mobile: daftar per hari agar tidak memaksa tabel horizontal. --}}
        <template x-if="!isDesktop">
            <div class="space-y-5">
                @foreach ($haris as $hari)
                    <section
                        class="app-card overflow-hidden">
                        <header class="flex items-center justify-between bg-base-200 px-4 py-3"
                            :class="isCurrentDay(@js($hari->name)) ? 'ring-2 ring-inset ring-primary' : ''">
                            <h2 class="font-black text-base-content">{{ $hari->name }}</h2>
                            <span
                                class="text-xs text-base-content/60">{{ now()->startOfWeek()->addDays($loop->index)->translatedFormat('d M') }}</span>
                        </header>
                        <div class="divide-y divide-base-300">
                            @foreach ($sesis->sortBy('start_time') as $sesi)
                                @if (isset($jadwals[$hari->id][$sesi->id]))
                                    @foreach ($jadwals[$hari->id][$sesi->id] as $groupedClass)
                                        @php($searchText = strtolower($hari->name . ' ' . $sesi->name . ' ' . $groupedClass['mapel']->name . ' ' . $groupedClass['guru']->name . ' ' . $groupedClass['ruang']->name . ' ' . $groupedClass['siswa_list']->pluck('name')->implode(' ')))
                                        <article
                                            x-show="matches(@js($searchText), { hari: {{ $hari->id }}, mapel: {{ $groupedClass['mapel']->id }}, guru: {{ $groupedClass['guru']->id }}, ruang: {{ $groupedClass['ruang']->id }} })"
                                            x-transition class="p-4">
                                            <div class="mb-2 flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="font-black text-base-content">
                                                        {{ $groupedClass['mapel']->name }}</p>
                                                    <p class="text-xs font-semibold text-primary">
                                                        {{ $sesi->name }} ·
                                                        {{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}
                                                    </p>
                                                </div>
                                                <span class="h-3 w-3 shrink-0 rounded-full"
                                                    style="background: {{ $groupedClass['mapel']->border_color }}"></span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 text-xs text-base-content/70">
                                                <span><i
                                                        class="fas fa-chalkboard-teacher mr-1 text-primary"></i>{{ $groupedClass['guru']->name }}</span>
                                                <span><i
                                                        class="fas fa-building mr-1 text-accent"></i>{{ $groupedClass['ruang']->name }}</span>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @foreach ($groupedClass['siswa_list'] as $siswa)
                                                    <span
                                                        class="app-chip rounded-full">{{ $siswa->panggilan ?: $siswa->name }}{{ $siswa->kelas ? ' · ' . $siswa->kelas : '' }}</span>
                                                @endforeach
                                            </div>
                                        </article>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </template>

        {{-- Desktop/tablet: kalender matriks. --}}
        <template x-if="isDesktop">
            <div
                class="app-table-wrap bg-base-100 shadow-xs">
                <table class="min-w-[980px] w-full table-fixed border-collapse">
                    <thead>
                        <tr class="bg-base-200">
                            <th class="w-28 border-b border-r border-base-300 p-3 text-sm">Sesi</th>
                            @foreach ($haris as $hari)
                                <th class="border-b border-base-300 p-3 text-sm"
                                    :class="isCurrentDay(@js($hari->name)) ?
                                        'bg-primary text-primary-content font-black' : ''">
                                    {{ $hari->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sesis->sortBy('start_time') as $sesi)
                            <tr>
                                <th class="border-r border-t border-base-300 p-3 align-top text-xs">
                                    <span class="block font-bold">{{ $sesi->name }}</span>
                                    <span
                                        class="font-normal text-base-content/60">{{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}</span>
                                </th>
                                @foreach ($haris as $hari)
                                    <td class="h-36 border-t border-base-300 p-2 align-top">
                                        @foreach ($jadwals[$hari->id][$sesi->id] ?? [] as $groupedClass)
                                            @php($searchText = strtolower($hari->name . ' ' . $sesi->name . ' ' . $groupedClass['mapel']->name . ' ' . $groupedClass['guru']->name . ' ' . $groupedClass['ruang']->name . ' ' . $groupedClass['siswa_list']->pluck('name')->implode(' ')))
                                            <article
                                                x-show="matches(@js($searchText), { hari: {{ $hari->id }}, mapel: {{ $groupedClass['mapel']->id }}, guru: {{ $groupedClass['guru']->id }}, ruang: {{ $groupedClass['ruang']->id }} })"
                                                x-transition
                                                class="mb-2 rounded-field border border-base-300 border-l-4 bg-base-200 p-3 text-xs shadow-xs transition-all hover:-translate-y-0.5 hover:shadow-md"
                                                style="border-left-color: {{ $groupedClass['mapel']->border_color }}">
                                                <strong
                                                    class="block text-sm text-base-content">{{ $groupedClass['mapel']->name }}</strong>
                                                <span class="mt-1 block text-base-content/70"><i
                                                        class="fas fa-chalkboard-teacher mr-1 text-primary"></i>{{ $groupedClass['guru']->name }}</span>
                                                <span class="block text-base-content/60"><i
                                                        class="fas fa-building mr-1 text-accent"></i>{{ $groupedClass['ruang']->name }}</span>
                                                <p class="mt-2 line-clamp-3 text-base-content/60">
                                                    {{ $groupedClass['siswa_list']->map(fn($s) => $s->panggilan ?: $s->name)->join(', ') }}
                                                </p>
                                            </article>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </template>
    </div>

@endsection
