@extends('layouts.masters.master')

@section('title', 'Kalender Jadwal')

@section('content')
<div class="mx-auto w-full max-w-7xl px-3 py-5 sm:px-6 lg:px-8 lg:py-8"
    x-data="calendarApp()" x-init="init()">
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 p-5 text-white shadow-lg sm:p-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-100">E-ling Course</p>
                <h1 class="mt-1 text-2xl font-black sm:text-3xl">Kalender Jadwal</h1>
                <p class="mt-2 max-w-2xl text-sm text-emerald-50">Cari jadwal berdasarkan hari, sesi, pelajaran, guru, ruang, atau siswa.</p>
            </div>
            <a :href="exportUrl" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-emerald-700 shadow hover:bg-emerald-50">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-[1fr_auto]">
        <label class="relative block">
            <span class="sr-only">Cari jadwal</span>
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="search" x-model.debounce.200ms="query" placeholder="Cari jadwal..."
                class="min-h-12 w-full rounded-xl border-gray-300 bg-white pl-11 pr-10 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <button x-show="query" @click="query = ''" type="button" aria-label="Hapus pencarian"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">&times;</button>
        </label>
        <div class="flex min-h-12 items-center rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
            <i class="far fa-calendar mr-2 text-emerald-500"></i><span x-text="todayLabel"></span>
        </div>
    </div>

    {{-- Mobile: daftar per hari agar tidak memaksa tabel horizontal. --}}
    <div class="space-y-5 md:hidden">
        @foreach ($haris as $hari)
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <header class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-900/60"
                    :class="isCurrentDay(@js($hari->name)) ? 'ring-2 ring-inset ring-emerald-500' : ''">
                    <h2 class="font-black text-gray-900 dark:text-white">{{ $hari->name }}</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ now()->startOfWeek()->addDays($loop->index)->translatedFormat('d M') }}</span>
                </header>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($sesis->sortBy('start_time') as $sesi)
                        @if (isset($jadwals[$hari->id][$sesi->id]))
                            @foreach ($jadwals[$hari->id][$sesi->id] as $groupedClass)
                                @php($searchText = strtolower($hari->name.' '.$sesi->name.' '.$groupedClass['mapel']->name.' '.$groupedClass['guru']->name.' '.$groupedClass['ruang']->name.' '.$groupedClass['siswa_list']->pluck('name')->implode(' ')))
                                <article x-show="matches(@js($searchText))" x-transition class="p-4">
                                    <div class="mb-2 flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-black text-gray-900 dark:text-white">{{ $groupedClass['mapel']->name }}</p>
                                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $sesi->name }} · {{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}</p>
                                        </div>
                                        <span class="h-3 w-3 shrink-0 rounded-full" style="background: {{ $groupedClass['mapel']->border_color }}"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                        <span><i class="fas fa-chalkboard-teacher mr-1 text-blue-500"></i>{{ $groupedClass['guru']->name }}</span>
                                        <span><i class="fas fa-building mr-1 text-emerald-500"></i>{{ $groupedClass['ruang']->name }}</span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($groupedClass['siswa_list'] as $siswa)
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $siswa->panggilan ?: $siswa->name }}{{ $siswa->kelas ? ' · '.$siswa->kelas : '' }}</span>
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

    {{-- Desktop/tablet: kalender matriks. --}}
    <div class="hidden overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 md:block">
        <table class="min-w-[980px] w-full table-fixed border-collapse">
            <thead><tr class="bg-gray-50 dark:bg-gray-900/60">
                <th class="w-28 border-b border-r border-gray-200 p-3 text-sm dark:border-gray-700">Sesi</th>
                @foreach ($haris as $hari)
                    <th class="border-b border-gray-200 p-3 text-sm dark:border-gray-700" :class="isCurrentDay(@js($hari->name)) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : ''">{{ $hari->name }}</th>
                @endforeach
            </tr></thead>
            <tbody>
            @foreach ($sesis->sortBy('start_time') as $sesi)
                <tr>
                    <th class="border-r border-t border-gray-200 p-3 align-top text-xs dark:border-gray-700">
                        <span class="block font-bold">{{ $sesi->name }}</span>
                        <span class="font-normal text-gray-500">{{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}</span>
                    </th>
                    @foreach ($haris as $hari)
                        <td class="h-36 border-t border-gray-200 p-2 align-top dark:border-gray-700">
                            @foreach (($jadwals[$hari->id][$sesi->id] ?? []) as $groupedClass)
                                @php($searchText = strtolower($hari->name.' '.$sesi->name.' '.$groupedClass['mapel']->name.' '.$groupedClass['guru']->name.' '.$groupedClass['ruang']->name.' '.$groupedClass['siswa_list']->pluck('name')->implode(' ')))
                                <article x-show="matches(@js($searchText))" x-transition class="mb-2 rounded-xl border-l-4 bg-gray-50 p-3 text-xs shadow-sm dark:bg-gray-700/70" style="border-left-color: {{ $groupedClass['mapel']->border_color }}">
                                    <strong class="block text-sm text-gray-900 dark:text-white">{{ $groupedClass['mapel']->name }}</strong>
                                    <span class="mt-1 block text-gray-600 dark:text-gray-300"><i class="fas fa-chalkboard-teacher mr-1 text-blue-500"></i>{{ $groupedClass['guru']->name }}</span>
                                    <span class="block text-gray-500 dark:text-gray-400"><i class="fas fa-building mr-1 text-emerald-500"></i>{{ $groupedClass['ruang']->name }}</span>
                                    <p class="mt-2 line-clamp-3 text-gray-500 dark:text-gray-300">{{ $groupedClass['siswa_list']->map(fn($s) => $s->panggilan ?: $s->name)->join(', ') }}</p>
                                </article>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function calendarApp() {
    return {
        query: '',
        todayLabel: '',
        exportBase: @js(route('jadwal.kalender.export')),
        init() {
            this.todayLabel = new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(new Date());
        },
        matches(text) {
            return !this.query.trim() || text.includes(this.query.toLocaleLowerCase('id-ID').trim());
        },
        isCurrentDay(dayName) {
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            return days[new Date().getDay()] === dayName;
        },
        get exportUrl() {
            const search = this.query.trim();
            return search ? `${this.exportBase}?search=${encodeURIComponent(search)}` : this.exportBase;
        }
    };
}
</script>
@endsection
