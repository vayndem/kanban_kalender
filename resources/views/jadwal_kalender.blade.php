@extends('layouts.masters.master')

@section('title', 'Kalender Jadwal')

@section('content')
    <div class="container mx-auto p-4 lg:p-8" x-data="{ searchPelajaran: '', searchSiswa: '' }">

        <h1 class="text-2xl lg:text-3xl font-bold text-center mb-8 text-gray-800 dark:text-gray-100">
            Jadwal Kalender
        </h1>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl mx-auto">
            <div>
                <label for="searchPelajaran" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-book-open mr-1"></i> Cari Pelajaran
                </label>
                <input type="text" id="searchPelajaran" x-model.debounce.300ms="searchPelajaran"
                    placeholder="Ketik nama pelajaran..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>
            <div>
                <label for="searchSiswa" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-user-graduate mr-1"></i> Cari Siswa
                </label>
                <input type="text" id="searchSiswa" x-model.debounce.300ms="searchSiswa"
                    placeholder="Ketik nama siswa..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
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

                        @foreach ($haris as $hari)
                            <th
                                class="border border-gray-300 dark:border-gray-600 p-3 text-center font-semibold text-gray-600 dark:text-gray-300 min-w-[150px]">
                                {{ $hari->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @foreach ($sesis as $sesi)
                        <tr class="even:bg-gray-50 dark:even:bg-gray-700">
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
                                                $namaSiswaString = $groupedClass['siswa_list']
                                                    ->pluck('name')
                                                    ->implode(', ');
                                            @endphp

                                            <div class="bg-white dark:bg-gray-700 p-2.5 mb-2 rounded-lg shadow border-l-4 text-sm
                                                    transition-opacity duration-300 ease-in-out"
                                                style="border-left-color: {{ $groupedClass['mapel']->border_color }};"
                                                :class="{
                                                    'opacity-30': (searchPelajaran !== '' && !
                                                            '{{ strtolower($groupedClass['mapel']->name) }}'.includes(
                                                                searchPelajaran.toLowerCase())) ||
                                                        (searchSiswa !== '' && !
                                                            '{{ strtolower($namaSiswaString) }}'.includes(searchSiswa
                                                                .toLowerCase()))
                                                }">

                                                <strong class="block font-bold text-gray-900 dark:text-white truncate">
                                                    {{ $groupedClass['mapel']->name }}
                                                </strong>
                                                <span class="block text-gray-600 dark:text-gray-300 mt-1">
                                                    {{ $groupedClass['guru']->name }}
                                                </span>
                                                <span class="block text-gray-500 dark:text-gray-400 text-xs mt-1">
                                                    Ruang: {{ $groupedClass['ruang']->name }}
                                                </span>

                                                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                                    <span
                                                        class="block text-gray-500 dark:text-gray-400 text-xs font-semibold">
                                                        Siswa:
                                                    </span>
                                                    <ol
                                                        class="list-decimal list-inside text-gray-500 dark:text-gray-400 text-xs pl-1">
                                                        @foreach ($groupedClass['siswa_list'] as $siswa)
                                                            <li>{{ $siswa->name }}</li>
                                                        @endforeach
                                                    </ol>
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
