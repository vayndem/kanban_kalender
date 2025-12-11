@extends('layouts.masters.master')

@section('title', 'Kalender Jadwal')

@section('content')
    <div class="container mx-auto p-4 lg:p-8" x-data="{
        universalSearch: '',

        // Fungsi Filter Kartu Jadwal berdasarkan Pencarian Universal
        filterCard(searchableText) {
            const search = this.universalSearch.toLowerCase().trim();
            if (search === '') return false;

            // Check if searchableText (which includes Mapel, Guru, Ruang, Siswa, Hari, Sesi) contains the search term
            return !searchableText.includes(search);
        },

        // Fungsi untuk menyorot hari ini
        isCurrentDay(dayName) {
            const today = moment().format('dddd');
            const dayMap = {
                'Monday': 'Senin', 'Tuesday': 'Selasa', 'Wednesday': 'Rabu',
                'Thursday': 'Kamis', 'Friday': 'Jumat', 'Saturday': 'Sabtu',
                'Sunday': 'Minggu'
            };
            return dayMap[today] === dayName;
        }
    }">

        <h1 class="text-2xl lg:text-3xl font-bold text-center mb-8 text-gray-800 dark:text-gray-100">
            Jadwal Kalender
        </h1>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4 max-w-5xl mx-auto">
            <div class="md:col-span-2">
                <label for="universalSearch" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-search mr-1"></i> Pencarian Universal
                </label>
                <input type="text" id="universalSearch" x-model.debounce.300ms="universalSearch"
                    placeholder="Cari Hari, Sesi, Mapel, Guru, Ruang, atau Nama Siswa..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="self-end">
                <label for="currentDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-calendar-alt mr-1"></i> Tanggal Hari Ini
                </label>
                {{-- Gunakan Carbon untuk memastikan tanggalnya benar saat render pertama --}}
                <input type="text" id="currentDate" disabled
                    value="{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-gray-100 cursor-default">
            </div>
        </div>

        <div class="overflow-x-auto shadow-md rounded-lg">
            <table class="min-w-full w-full border-collapse table-fixed">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="border border-gray-300 dark:border-gray-600 p-3 text-center font-semibold text-gray-600 dark:text-gray-300 w-24 lg:w-32">
                            Sesi
                        </th>

                        @php
                            // Untuk memastikan tanggal di header tabel selalu sesuai dengan minggu saat ini
                            $startOfWeek = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);
                            $dayOffsets = ['Senin' => 0, 'Selasa' => 1, 'Rabu' => 2, 'Kamis' => 3, 'Jumat' => 4, 'Sabtu' => 5];
                        @endphp

                        @foreach ($haris as $index => $hari)
                            @php
                                $offset = $dayOffsets[$hari->name] ?? $index;
                                $date = $startOfWeek->copy()->addDays($offset);
                            @endphp
                            <th
                                class="border border-gray-300 dark:border-gray-600 p-3 text-center font-semibold min-w-[150px] transition-colors"
                                :class="{
                                    'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 border-blue-400 dark:border-blue-700': isCurrentDay('{{ $hari->name }}'),
                                    'text-gray-600 dark:text-gray-300': !isCurrentDay('{{ $hari->name }}')
                                }">
                                <div class="text-base">{{ $hari->name }}</div>
                                <span class="block mt-1 text-[10px] font-normal">
                                    {{ $date->translatedFormat('d F Y') }}
                                </span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @foreach ($sesis as $sesi)
                        <tr class=" dark:even:bg-gray-700/50">
                            <td
                                class="border border-gray-200 dark:border-gray-600 p-2 text-center align-middle font-semibold text-gray-700 dark:text-gray-200">
                                {{ $sesi->name }}
                                <span class="block text-xs text-gray-500 dark:text-gray-400 font-normal">
                                    {{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}
                                </span>
                            </td>

                            @foreach ($haris as $hari)
                                <td class="border border-gray-200 dark:border-gray-600 p-2 align-top h-40">

                                    @if (isset($jadwals[$hari->id][$sesi->id]))
                                        @foreach ($jadwals[$hari->id][$sesi->id] as $groupedClass)
                                            @php
                                                // Menggabungkan semua data yang mungkin dicari ke satu string
                                                $siswaNames = $groupedClass['siswa_list']->pluck('name')->implode(' ');
                                                $searchableText = strtolower(
                                                    $hari->name .
                                                    ' ' .
                                                    $sesi->name .
                                                    ' ' .
                                                    $groupedClass['mapel']->name .
                                                    ' ' .
                                                    $groupedClass['guru']->name .
                                                    ' ' .
                                                    $groupedClass['ruang']->name .
                                                    ' ' .
                                                    $siswaNames
                                                );
                                            @endphp

                                            <div class="bg-white dark:bg-gray-700 p-2.5 mb-2 rounded-lg shadow border-l-4 text-sm
                                                    transition-opacity duration-300 ease-in-out cursor-default"
                                                style="border-left-color: {{ $groupedClass['mapel']->border_color }};"
                                                x-cloak
                                                :class="{
                                                    // Jika filterCard mengembalikan TRUE (Tidak Cocok), terapkan opacity rendah
                                                    'hidden': filterCard('{{ $searchableText }}')
                                                }">

                                                <strong class="block font-bold text-gray-900 dark:text-white truncate">
                                                    {{ $groupedClass['mapel']->name }}
                                                </strong>
                                                <span class="block text-gray-600 dark:text-gray-300 mt-1 text-xs">
                                                    <i class="fas fa-chalkboard-teacher mr-1 text-blue-500"></i>{{ $groupedClass['guru']->name }}
                                                </span>
                                                <span class="block text-gray-500 dark:text-gray-400 text-xs mt-1">
                                                    <i class="fas fa-building mr-1 text-green-500"></i>Ruang: {{ $groupedClass['ruang']->name }}
                                                </span>

                                                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                                    <span
                                                        class="block text-gray-500 dark:text-gray-400 text-xs font-semibold">
                                                        <i class="fas fa-users mr-1"></i>Siswa:
                                                    </span>
                                                    <ul
                                                        class="list-disc list-inside text-gray-500 dark:text-gray-400 text-xs pl-3 space-y-0.5 max-h-16 overflow-y-auto">
                                                        @foreach ($groupedClass['siswa_list'] as $siswa)
                                                            {{-- Siswa yang tidak cocok dengan pencarian universal tetap ditampilkan buram di sini --}}
                                                            <li
                                                                class="{{ $siswa->tandas->isNotEmpty() ? 'text-yellow-600 dark:text-yellow-400 font-bold' : '' }}">
                                                                {{ $siswa->panggilan ?? $siswa->name }} ({{ $siswa->kelas }})
                                                                @if ($siswa->tandas->isNotEmpty())
                                                                    <i class="fas fa-exclamation-circle ml-1 text-yellow-500" title="Ada Catatan Khusus"></i>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>

                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
