<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="container mx-auto" x-data="jadwalHandler({
                allMapels: {{ $allMapels->toJson() }},
                allGurus: {{ $allGurus->toJson() }},
                allRuangs: {{ $allRuangs->toJson() }},
                allSiswas: {{ $allSiswas->toJson() }},
                allArsips: {{ $allArsips->toJson() }},
                jadwalsData: {{ $jadwalsData->toJson() }},
                allHaris: {{ $haris->toJson() }},
                allSesis: {{ $sesis->sortBy('start_time')->values()->toJson() }},
                csrfToken: '{{ csrf_token() }}',
                routes: {
                    mapel: { destroy: '{{ route('admin.mapel.destroy', ':id') }}', store: '{{ route('admin.mapel.store') }}', update: '{{ route('admin.mapel.update', ':id') }}' },
                    guru: { destroy: '{{ route('admin.guru.destroy', ':id') }}', store: '{{ route('admin.guru.store') }}', update: '{{ route('admin.guru.update', ':id') }}' },
                    ruang: { destroy: '{{ route('admin.ruang.destroy', ':id') }}', store: '{{ route('admin.ruang.store') }}', update: '{{ route('admin.ruang.update', ':id') }}' },
                    sesi: { destroy: '{{ route('admin.sesi.destroy', ':id') }}', store: '{{ route('admin.sesi.store') }}', update: '{{ route('admin.sesi.update', ':id') }}' },
                    siswa: { destroy: '{{ route('admin.siswa.destroy', ':id') }}', store: '{{ route('admin.siswa.store') }}', update: '{{ route('admin.siswa.update', ':id') }}' },
                    tanda: { destroy: '{{ route('admin.tanda.destroy', ':id') }}', store: '{{ route('admin.tanda.store') }}', update: '{{ route('admin.tanda.update', ':id') }}' },
                    jadwal: {
                        store: '{{ route('admin.jadwal.store') }}',
                        updateKelas: '{{ route('admin.jadwal.updateKelas') }}',
                        export: '{{ route('admin.jadwal.export') }}',
                        generateText: '{{ route('admin.jadwal.generateText') }}'
                    }
                }
            })">

                <div class="mb-5">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button @click="activeTab = 'jadwal'; currentForm = ''"
                                :class="activeTab === 'jadwal' ? 'border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Jadwal Pelajaran
                            </button>

                            <button @click="activeTab = 'data_siswa'; currentForm = 'siswa'; formSearch = ''"
                                :class="activeTab === 'data_siswa' ? 'border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Data Siswa
                            </button>

                            <button @click="activeTab = 'pembayaran'; currentForm = 'pembayaran'; formSearch = ''"
                                :class="activeTab === 'pembayaran' ? 'border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Pembayaran
                            </button>
                        </nav>
                    </div>
                </div>

                <div x-show="activeTab === 'jadwal'" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-xl shadow-lg mb-6">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-6">

                            <div class="flex space-x-3">
                                <button @click.prevent="openExportOptions()" type="button"
                                    class="inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:ring-offset-gray-800 transition-colors">
                                    <i class="fas fa-file-export mr-2"></i> Export / Copy
                                </button>

                                <button @click.prevent="openStashOptions()" type="button"
                                    class="inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 transition-colors">
                                    <i class="fas fa-database mr-2"></i> Stash
                                </button>

                                <div class="relative inline-block text-left">
                                    <button @click="showAddMenu = !showAddMenu" type="button"
                                        class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:ring-offset-gray-800">
                                        Tambah Data Baru
                                        <i class="fas fa-caret-down ml-2 -mr-1"></i>
                                    </button>

                                    <div x-show="showAddMenu" @click.away="showAddMenu = false" x-transition
                                        class="origin-top-left absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-20"
                                        style="display: none;">

                                        <div class="py-1" role="menu">
                                            <a href="#"
                                                @click.prevent="currentForm = 'mapel'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem"><i class="fas fa-book-open w-5 mr-2"></i> Mata
                                                Pelajaran</a>
                                            <a href="#" @click.prevent="currentForm = 'guru'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem"><i class="fas fa-chalkboard-teacher w-5 mr-2"></i>
                                                Guru</a>
                                            <a href="#"
                                                @click.prevent="currentForm = 'ruang'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem"><i class="fas fa-building w-5 mr-2"></i> Ruang</a>
                                            <a href="#" @click.prevent="currentForm = 'sesi'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem"><i class="fas fa-clock w-5 mr-2"></i> Sesi Waktu</a>
                                            <a href="#"
                                                @click.prevent="currentForm = 'tanda'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem"><i class="fas fa-sticky-note w-5 mr-2"></i> Tanda /
                                                Catatan</a>
                                        </div>
                                    </div>

                                    <div x-show="activeTab === 'jadwal'">
                                    </div>

                                    <div x-show="activeTab === 'data_siswa'">
                                        @include('admin.card')
                                    </div>

                                    <div x-show="activeTab === 'pembayaran'">
                                        @include('admin.pembayaran')
                                    </div>

                                    <div x-show="currentForm" x-transition
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm"
                                        style="display: none;">
                                        <div @click="currentForm = ''; formData = {}; activeFormTab = 'input'; formSearch = ''"
                                            class="absolute inset-0"></div>
                                        <div @click.stop
                                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden relative border dark:border-gray-700 transition-all duration-300">
                                            @include('admin.form', ['type' => 'currentForm'])
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-grow max-w-2xl">
                                <label for="universalSearch"
                                    class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                                    <i class="fas fa-search mr-1"></i> Pencarian Universal
                                </label>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="text" id="universalSearch" x-model.debounce.300ms="universalSearch"
                                        placeholder="Cari Hari, Sesi, Mapel, Guru, atau Nama Siswa..."
                                        class="w-full pl-10 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <button x-show="universalSearch.length > 0" @click="universalSearch = ''"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto shadow-md rounded-lg">
                        <table class="min-w-full w-full border-collapse table-fixed">
                            <thead class="bg-gray-100 dark:bg-gray-700/80">
                                <tr>
                                    <th
                                        class="border border-gray-300 dark:border-gray-600 p-3 text-center uppercase text-xs tracking-wider font-semibold text-gray-600 dark:text-white w-24 lg:w-32">
                                        Sesi
                                    </th>

                                    @php
                                        $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
                                        $dayOffsets = [
                                            'Senin' => 0,
                                            'Selasa' => 1,
                                            'Rabu' => 2,
                                            'Kamis' => 3,
                                            'Jumat' => 4,
                                            'Sabtu' => 5,
                                        ];
                                    @endphp

                                    @foreach ($haris as $index => $hari)
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 p-3 text-center uppercase text-xs tracking-wider font-semibold text-gray-600 dark:text-white min-w-[200px]">
                                            <div class="text-base">{{ $hari->name }}</div>
                                            @php
                                                $offset = $dayOffsets[$hari->name] ?? $index;
                                                $date = $startOfWeek->copy()->addDays($offset);
                                            @endphp
                                            <span class="block mt-1 text-[10px] font-normal ">
                                                {{ $date->translatedFormat('d F Y') }}
                                            </span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800">
                                @foreach ($sesis->sortBy('start_time') as $sesi)
                                    <tr class="even:bg-gray-50/50 dark:even:bg-gray-800/60">
                                        <td
                                            class="border border-gray-200 dark:border-gray-600 p-2 text-center align-middle font-semibold text-gray-700 dark:text-white">
                                            {{ $sesi->name }}
                                            <span class="block text-xs text-gray-500 dark:text-gray-300 font-normal">
                                                {{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}
                                            </span>
                                        </td>

                                        @foreach ($haris as $hari)
                                            <td class="kanban-slot border border-gray-200 dark:border-gray-600 p-2 align-top h-64 relative cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors duration-150"
                                                id="slot-{{ $hari->id }}-{{ $sesi->id }}"
                                                data-hari-id="{{ $hari->id }}"
                                                data-sesi-id="{{ $sesi->id }}"
                                                @click="openAddJadwalModal({{ $hari->id }}, {{ $sesi->id }})">

                                                @if (isset($jadwals[$hari->id][$sesi->id]))
                                                    @foreach ($jadwals[$hari->id][$sesi->id] as $groupedClass)
                                                        @php
                                                            $siswaList = $groupedClass['siswa_list'];
                                                            $siswaCount = $siswaList->count();
                                                            $siswaNames = $siswaList->pluck('name')->implode(', ');
                                                            $siswaIDsString = $siswaList->pluck('id')->implode(',');
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
                                                                    $siswaNames,
                                                            );
                                                            $cardBgColor =
                                                                $siswaCount < 4 ? 'bg-white/100' : 'bg-white/90';
                                                        @endphp

                                                        <div class="kanban-card group relative {{ $cardBgColor }} dark:bg-gray-700/90 backdrop-blur-sm p-2.5 mb-2 rounded-lg shadow border-l-4 text-sm cursor-move transition-all duration-200 ease-out hover:shadow-xl hover:-translate-y-1"
                                                            style="border-left-color: {{ $groupedClass['mapel']->border_color }};"
                                                            data-mapel-id="{{ $groupedClass['mapel']->id }}"
                                                            data-guru-id="{{ $groupedClass['guru']->id }}"
                                                            data-ruang-id="{{ $groupedClass['ruang']->id }}"
                                                            data-hari-id="{{ $hari->id }}"
                                                            data-sesi-id="{{ $sesi->id }}"
                                                            data-siswa-ids="[{{ $siswaIDsString }}]"
                                                            :class="{
                                                                'hidden': universalSearch !== '' && !
                                                                    '{{ $searchableText }}'.includes(universalSearch
                                                                        .toLowerCase())
                                                            }"
                                                            @click.stop>

                                                            <button
                                                                @click.prevent="
                                                                const card = $el.closest('.kanban-card');
                                                                editingJadwal = {
                                                                    mapel_id: parseInt(card.dataset.mapelId),
                                                                    guru_id: parseInt(card.dataset.guruId),
                                                                    ruang_id: parseInt(card.dataset.ruangId),
                                                                    siswa_ids: JSON.parse(card.dataset.siswaIds),
                                                                    old_mapel_id: parseInt(card.dataset.mapelId),
                                                                    old_guru_id: parseInt(card.dataset.guruId),
                                                                    old_ruang_id: parseInt(card.dataset.ruangId),
                                                                    old_hari_id: parseInt(card.dataset.hariId),
                                                                    old_sesi_id: parseInt(card.dataset.sesiId)
                                                                };
                                                                selectedStudentDetail = null;
                                                                $nextTick(() => { showModal = true; });
                                                            "
                                                                class="absolute top-1 right-1 p-1.5 rounded-full bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-white hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500 dark:hover:text-white transition-all duration-200 opacity-0 group-hover:opacity-100">
                                                                <i class="fas fa-pencil-alt fa-xs"></i>
                                                            </button>

                                                            <strong
                                                                class="block font-bold text-gray-900 dark:text-white truncate">
                                                                {{ $groupedClass['mapel']->name }}
                                                            </strong>
                                                            <span class="block text-gray-600 dark:text-gray-200 mt-1">
                                                                {{ $groupedClass['guru']->name }}
                                                            </span>
                                                            <span
                                                                class="block text-gray-500 dark:text-gray-300 text-xs mt-1">
                                                                Ruang: {{ $groupedClass['ruang']->name }}
                                                            </span>
                                                            <div
                                                                class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                                                <span
                                                                    class="block text-gray-500 dark:text-gray-300 text-xs font-semibold">Siswa:</span>
                                                                <ol
                                                                    class="list-decimal list-inside text-gray-500 dark:text-gray-200 text-xs pl-1">
                                                                    @foreach ($groupedClass['siswa_list'] as $siswa)
                                                                        <li
                                                                            class="{{ $siswa->tandas->isNotEmpty() ? 'text-yellow-600 dark:text-yellow-400 font-bold' : '' }}">
                                                                            {{ $siswa->name }} -
                                                                            {{ $siswa->kelas }}
                                                                        </li>
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

                <div x-show="activeTab === 'data_siswa'" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0" style="display: none;">
                    @include('admin.card')
                </div>

                <div x-show="activeTab === 'pembayaran'" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0" style="display: none;">
                    @include('admin.pembayaran')
                </div>

                <div x-show="showModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm"
                    style="display: none;">

                    <div @click="showModal = false" class="absolute inset-0"></div>

                    <div @click.stop
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden relative border dark:border-gray-700"
                        x-show="showModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                        <div
                            class="flex justify-between items-center p-5 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-calendar-check text-blue-500"></i> Edit Jadwal & Catatan Siswa
                            </h3>
                            <button @click="showModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-times fa-lg"></i>
                            </button>
                        </div>

                        <form id="editJadwalForm" @submit.prevent="saveJadwal">
                            <div class="flex flex-col md:flex-row h-[75vh]">
                                <div
                                    class="w-full md:w-2/3 p-6 overflow-y-auto border-r dark:border-r-gray-700 bg-white dark:bg-gray-800 space-y-5">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Mata
                                                Pelajaran</label>
                                            <select x-model="editingJadwal.mapel_id"
                                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                <template x-for="mapel in allMapels" :key="mapel.id">
                                                    <option :value="mapel.id" x-text="mapel.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Guru</label>
                                            <select x-model="editingJadwal.guru_id"
                                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                <template x-for="guru in allGurus" :key="guru.id">
                                                    <option :value="guru.id" x-text="guru.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Ruang</label>
                                            <select x-model="editingJadwal.ruang_id"
                                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                <template x-for="ruang in allRuangs" :key="ruang.id">
                                                    <option :value="ruang.id" x-text="ruang.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                                        <label
                                            class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Cari
                                            & Tambah Siswa Baru</label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                                <i class="fas fa-user-plus text-sm"></i>
                                            </div>
                                            <input type="text" x-model.debounce.300ms="searchModalSiswa"
                                                @keydown.escape.prevent="searchModalSiswa = ''"
                                                placeholder="Ketik nama siswa terdaftar untuk ditambahkan ke kelas ini..."
                                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none">
                                            <div x-show="filteredAvailableSiswas().length > 0" x-transition
                                                @click.away="searchModalSiswa = ''"
                                                class="absolute z-30 w-full mt-1 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-600">
                                                <template x-for="siswa in filteredAvailableSiswas()"
                                                    :key="siswa.id">
                                                    <button @click.prevent="addSiswa(siswa.id)" type="button"
                                                        class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-white hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors font-medium">
                                                        <i class="fas fa-plus text-xs text-blue-500 mr-2"></i><span
                                                            x-text="siswa.name"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                                        <label
                                            class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Daftar
                                            Siswa Terpilih Di Kelas Ini (<span
                                                x-text="selectedSiswas().length"></span>)</label>
                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[35vh] overflow-y-auto p-1 custom-scrollbar">
                                            <template x-for="siswa in selectedSiswas()" :key="siswa.id">
                                                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                                                    @click="viewStudentDetail(siswa)"
                                                    :class="{
                                                        'ring-2 ring-blue-500 bg-blue-50/50 dark:bg-blue-900/20 border-transparent': selectedStudentDetail &&
                                                            selectedStudentDetail.id === siswa.id
                                                    }">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <i class="fas fa-circle-user shrink-0 text-gray-400"
                                                            :class="hasTanda(siswa) ? 'text-yellow-500' : ''"></i>
                                                        <span x-text="siswa.name" class="text-sm font-bold truncate"
                                                            :class="hasTanda(siswa) ? 'text-yellow-600 dark:text-yellow-400' :
                                                                'text-gray-900 dark:text-white'"></span>
                                                    </div>
                                                    <button @click.stop.prevent="removeSiswa(siswa.id)" type="button"
                                                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg text-xs transition-colors shrink-0 font-semibold">
                                                        <i class="fas fa-user-minus"></i> Hapus
                                                    </button>
                                                </div>
                                            </template>
                                            <div x-show="editingJadwal.siswa_ids && editingJadwal.siswa_ids.length === 0"
                                                class="col-span-full text-sm text-gray-400 text-center py-10 border-2 border-dashed border-gray-200 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/20 rounded-xl">
                                                <i class="fas fa-users mb-2 text-3xl"></i><br> Belum ada siswa terpilih
                                                di kelas ini
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="w-full md:w-1/3 bg-gray-50 dark:bg-gray-900 border-l dark:border-gray-700 flex flex-col">
                                    <div class="p-4 border-b dark:border-gray-700 bg-white dark:bg-gray-800">
                                        <h4
                                            class="font-bold text-sm text-gray-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                            <i class="fas fa-clipboard-user text-blue-500"></i> Detail & Catatan Siswa
                                        </h4>
                                    </div>
                                    <div class="p-6 overflow-y-auto flex-grow">
                                        <template x-if="selectedStudentDetail">
                                            <div class="animate-fadeIn space-y-6">
                                                <div class="text-center">
                                                    <div
                                                        class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                                        <span class="text-2xl font-black text-white"
                                                            x-text="selectedStudentDetail.name.charAt(0)"></span>
                                                    </div>
                                                    <h3 class="text-base font-black text-gray-900 dark:text-white leading-tight"
                                                        x-text="selectedStudentDetail.name"></h3>
                                                    <span
                                                        class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded"
                                                        x-text="'Kelas: ' + (selectedStudentDetail.kelas || 'N/A')"></span>
                                                </div>
                                                <div class="space-y-3">
                                                    <h5
                                                        class="text-xs font-black uppercase text-gray-400 tracking-widest border-b dark:border-gray-700 pb-1 flex items-center gap-1.5">
                                                        <i class="fas fa-sticky-note text-yellow-500"></i> Catatan
                                                        Khusus
                                                    </h5>
                                                    <template x-if="hasTanda(selectedStudentDetail)">
                                                        <div
                                                            class="space-y-2.5 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                                                            <template x-for="tanda in selectedStudentDetail.tandas"
                                                                :key="tanda.id">
                                                                <div
                                                                    class="relative bg-yellow-50/60 dark:bg-yellow-950/20 border-l-4 border-yellow-400 p-3 rounded-xl shadow-sm text-xs text-gray-800 dark:text-gray-200">
                                                                    <p x-text="tanda.keterangan"
                                                                        class="break-words font-medium leading-relaxed pr-6">
                                                                    </p>
                                                                    <span
                                                                        class="text-[9px] text-gray-400 dark:text-gray-500 mt-1.5 block font-mono"
                                                                        x-text="new Date(tanda.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></span>
                                                                    <button type="button"
                                                                        @click.stop="markTandaForDeletion(tanda.id, selectedStudentDetail.id)"
                                                                        class="absolute top-2 right-2 text-red-400 hover:text-red-600 p-1 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition-colors"
                                                                        title="Hapus Catatan">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="!hasTanda(selectedStudentDetail)">
                                                        <div
                                                            class="text-center py-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                                                            <i
                                                                class="fas fa-circle-check text-green-400 text-2xl mb-1 block"></i>
                                                            <p class="text-xs text-gray-400 dark:text-gray-500">Tidak
                                                                ada catatan untuk siswa ini.</p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!selectedStudentDetail">
                                            <div
                                                class="h-full flex flex-col items-center justify-center text-center text-gray-400 p-4 min-h-[200px]">
                                                <i
                                                    class="fas fa-arrow-pointer text-3xl mb-3 opacity-40 animate-bounce"></i>
                                                <p class="text-xs leading-relaxed">Klik salah satu nama siswa di daftar
                                                    sebelah kiri untuk melihat catatan khusus perkembangan mereka.</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700 flex justify-end gap-2.5">
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-all">Batal</button>
                                <button type="button" id="saveJadwalButton" @click.prevent="saveJadwal"
                                    class="px-5 py-2 text-sm font-bold text-white bg-blue-600 border border-transparent rounded-xl shadow-md hover:bg-blue-700 transition-all flex items-center gap-2"><i
                                        class="fas fa-save"></i> Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="showAddJadwalModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm"
                    style="display: none;">

                    <div @click="showAddJadwalModal = false" class="absolute inset-0"></div>

                    <div @click.stop
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden relative border dark:border-gray-700"
                        x-show="showAddJadwalModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                        <div
                            class="flex justify-between items-center p-5 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fas fa-calendar-plus text-green-500"></i> Tambah Jadwal Baru
                            </h3>
                            <button @click="showAddJadwalModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-times fa-lg"></i>
                            </button>
                        </div>

                        <form @submit.prevent="saveNewJadwal">
                            <div class="flex flex-col md:flex-row h-[75vh]">
                                <div
                                    class="w-full md:w-2/3 p-6 overflow-y-auto border-r dark:border-r-gray-700 bg-white dark:bg-gray-800 space-y-5">
                                    <div
                                        class="bg-gradient-to-r from-blue-500 to-indigo-600 p-4 rounded-xl shadow-inner text-white flex items-center gap-3">
                                        <div class="p-2.5 bg-white/20 rounded-xl">
                                            <i class="fas fa-clock text-lg"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase font-black tracking-widest text-blue-100">Slot
                                                Mengajar Terpilih</p>
                                            <p class="text-base font-bold">
                                                <span
                                                    x-text="allHaris.find(h => h.id === newJadwal.hari_id)?.name || '...'"></span>,
                                                <span
                                                    x-text="allSesis.find(s => s.id === newJadwal.sesi_id)?.name || '...'"></span>
                                                <span class="text-xs font-normal opacity-80"
                                                    x-text="allSesis.find(s => s.id === newJadwal.sesi_id)?.start_time ? '(' + allSesis.find(s => s.id === newJadwal.sesi_id).start_time.substring(0,5) + ' - ' + allSesis.find(s => s.id === newJadwal.sesi_id).end_time.substring(0,5) + ')' : ''"></span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Mata
                                                Pelajaran</label>
                                            <select x-model.number="newJadwal.mata_pelajaran_id"
                                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                <template x-for="mapel in allMapels" :key="mapel.id">
                                                    <option :value="mapel.id" x-text="mapel.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Guru</label>
                                            <select x-model.number="newJadwal.guru_id"
                                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                <template x-for="guru in allGurus" :key="guru.id">
                                                    <option :value="guru.id" x-text="guru.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Ruang</label>
                                            <select x-model.number="newJadwal.ruang_id"
                                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                <template x-for="ruang in allRuangs" :key="ruang.id">
                                                    <option :value="ruang.id" x-text="ruang.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                                        <label
                                            class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Cari
                                            & Hubungkan Siswa</label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                                <i class="fas fa-user-search text-sm"></i>
                                            </div>
                                            <input type="text" x-model.debounce.300ms="searchModalSiswa"
                                                @keydown.escape.prevent="searchModalSiswa = ''"
                                                placeholder="Ketik nama lengkap atau panggilan siswa untuk dimasukkan..."
                                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none">
                                            <div x-show="filteredAvailableSiswas().length > 0" x-transition
                                                @click.away="searchModalSiswa = ''"
                                                class="absolute z-30 w-full mt-1 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-600">
                                                <template x-for="siswa in filteredAvailableSiswas()"
                                                    :key="siswa.id">
                                                    <button @click.prevent="addSiswa(siswa.id)" type="button"
                                                        class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-white hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors font-medium">
                                                        <i class="fas fa-plus text-xs text-green-500 mr-2"></i><span
                                                            x-text="siswa.name"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                                        <label
                                            class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Anggota
                                            Siswa Terpilih Kelas Baru (<span
                                                x-text="selectedSiswas().length"></span>)</label>
                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[30vh] overflow-y-auto p-1 custom-scrollbar">
                                            <template x-for="siswa in selectedSiswas()" :key="siswa.id">
                                                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 hover:ring-2 hover:ring-green-500 transition-all cursor-pointer"
                                                    @click="viewStudentDetail(siswa)"
                                                    :class="{
                                                        'ring-2 ring-green-500 bg-green-50/30 dark:bg-green-900/10 border-transparent': selectedStudentDetail &&
                                                            selectedStudentDetail.id === siswa.id
                                                    }">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <i class="fas fa-circle-user shrink-0 text-gray-400"
                                                            :class="hasTanda(siswa) ? 'text-yellow-500' : ''"></i>
                                                        <span x-text="siswa.name" class="text-sm font-bold truncate"
                                                            :class="hasTanda(siswa) ? 'text-yellow-600 dark:text-yellow-400' :
                                                                'text-gray-900 dark:text-white'"></span>
                                                    </div>
                                                    <button @click.stop.prevent="removeSiswa(siswa.id)" type="button"
                                                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg text-xs transition-colors shrink-0 font-semibold">
                                                        <i class="fas fa-minus"></i> Lepas
                                                    </button>
                                                </div>
                                            </template>
                                            <div x-show="selectedSiswas().length === 0"
                                                class="col-span-full text-sm text-gray-400 text-center py-10 border-2 border-dashed border-gray-200 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/20 rounded-xl">
                                                <i class="fas fa-users-slash mb-2 text-3xl"></i><br> Belum melampirkan
                                                siswa, silakan cari di kolom atas
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="w-full md:w-1/3 bg-gray-50 dark:bg-gray-900 border-l dark:border-gray-700 flex flex-col">
                                    <div class="p-4 border-b dark:border-gray-700 bg-white dark:bg-gray-800">
                                        <h4
                                            class="font-bold text-sm text-gray-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                            <i class="fas fa-clipboard-user text-green-500"></i> Catatan Siswa Terpilih
                                        </h4>
                                    </div>
                                    <div class="p-6 overflow-y-auto flex-grow">
                                        <template x-if="selectedStudentDetail">
                                            <div class="animate-fadeIn space-y-6">
                                                <div class="text-center">
                                                    <div
                                                        class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                                        <span class="text-2xl font-black text-white"
                                                            x-text="selectedStudentDetail.name.charAt(0)"></span>
                                                    </div>
                                                    <h3 class="text-base font-black text-gray-900 dark:text-white leading-tight"
                                                        x-text="selectedStudentDetail.name"></h3>
                                                    <span
                                                        class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded"
                                                        x-text="'Kelas: ' + (selectedStudentDetail.kelas || 'N/A')"></span>
                                                </div>
                                                <div class="space-y-3">
                                                    <h5
                                                        class="text-xs font-black uppercase text-gray-400 tracking-widest border-b dark:border-gray-700 pb-1 flex items-center gap-1.5">
                                                        <i class="fas fa-sticky-note text-yellow-500"></i> Catatan
                                                        Khusus
                                                    </h5>
                                                    <template x-if="hasTanda(selectedStudentDetail)">
                                                        <div
                                                            class="space-y-2.5 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                                                            <template x-for="tanda in selectedStudentDetail.tandas"
                                                                :key="tanda.id">
                                                                <div
                                                                    class="bg-yellow-50/60 dark:bg-yellow-950/20 border-l-4 border-yellow-400 p-3 rounded-xl shadow-sm text-xs text-gray-800 dark:text-gray-200">
                                                                    <p x-text="tanda.keterangan"
                                                                        class="break-words font-medium leading-relaxed">
                                                                    </p>
                                                                    <span
                                                                        class="text-[9px] text-gray-400 dark:text-gray-500 mt-1.5 block font-mono"
                                                                        x-text="new Date(tanda.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="!hasTanda(selectedStudentDetail)">
                                                        <div
                                                            class="text-center py-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                                                            <i
                                                                class="fas fa-circle-check text-green-400 text-2xl mb-1 block"></i>
                                                            <p class="text-xs text-gray-400 dark:text-gray-500">Tidak
                                                                ada catatan untuk siswa ini.</p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!selectedStudentDetail">
                                            <div
                                                class="h-full flex flex-col items-center justify-center text-center text-gray-400 p-4 min-h-[200px]">
                                                <i
                                                    class="fas fa-arrow-pointer text-3xl mb-3 opacity-40 animate-bounce"></i>
                                                <p class="text-xs leading-relaxed">Klik salah satu komponen kartu siswa
                                                    terpilih di bagian kiri untuk memeriksa lampiran rekam catatan
                                                    bimbingan mereka.</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700 flex justify-end gap-2.5">
                                <button type="button" @click="showAddJadwalModal = false"
                                    class="px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-all">Batal</button>
                                <button type="button" id="saveNewJadwalButton" @click.prevent="saveNewJadwal()"
                                    class="px-5 py-2 text-sm font-bold text-white bg-green-600 border border-transparent rounded-xl shadow-md hover:bg-green-700 transition-all flex items-center gap-2"><i
                                        class="fas fa-check-circle"></i> Simpan Jadwal Baru</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('jadwalHandler', (data) => ({
                    activeTab: new URLSearchParams(window.location.search).get('tab') || 'jadwal',
                    universalSearch: '',
                    showModal: false,
                    showAddJadwalModal: false,
                    editingJadwal: {},
                    deletedTandaIds: [],
                    allJadwals: data.jadwalsData || [],
                    newJadwal: {
                        hari_id: null,
                        sesi_id: null,
                        mata_pelajaran_id: null,
                        guru_id: null,
                        ruang_id: null,
                        siswa_ids: []
                    },
                    allMapels: data.allMapels,
                    allGurus: data.allGurus,
                    allRuangs: data.allRuangs,
                    allSiswas: data.allSiswas,
                    allHaris: data.allHaris,
                    allSesis: data.allSesis,
                    routes: data.routes,
                    csrfToken: data.csrfToken,
                    searchModalSiswa: '',
                    showAddMenu: false,
                    currentForm: '',
                    selectedStudentDetail: null,
                    formData: {},
                    activeFormTab: 'input',
                    formSearch: '',

                    refreshPage() {
                        const url = new URL(window.location.href);
                        url.searchParams.set('tab', this.activeTab);
                        window.location.href = url.toString();
                    },

                    sudahPunyaJadwal(siswaId) {
                        if (!this.allJadwals || this.allJadwals.length === 0) return false;
                        return this.allJadwals.some(j => Number(j.siswa_id) === Number(siswaId));
                    },

                    getFilteredList() {
                        const search = this.formSearch.toLowerCase();
                        let source = [];
                        switch (this.currentForm) {
                            case 'mapel':
                                source = this.allMapels;
                                break;
                            case 'guru':
                                source = this.allGurus;
                                break;
                            case 'ruang':
                                source = this.allRuangs;
                                break;
                            case 'sesi':
                                source = this.allSesis;
                                break;
                            case 'siswa':
                                source = this.allSiswas.map(s => ({
                                    ...s,
                                    name: (s.panggilan || s.name) + ' - ' + (s.kelas || '-')
                                }));
                                break;
                            case 'tanda':
                                this.allSiswas.forEach(siswa => {
                                    if (siswa.tandas) {
                                        siswa.tandas.forEach(tanda => {
                                            source.push({
                                                id: tanda.id,
                                                name: (siswa.panggilan || siswa
                                                        .name) + ' - ' + (siswa
                                                        .kelas || '-') + ' : ' +
                                                    tanda.keterangan,
                                                siswa_id: siswa.id,
                                                keterangan: tanda.keterangan
                                            });
                                        });
                                    }
                                });
                                break;
                        }
                        return search === '' ? source : source.filter(item => item.name.toLowerCase()
                            .includes(search));
                    },

                    editDataItem(item) {
                        this.activeFormTab = 'input';
                        this.formData = JSON.parse(JSON.stringify(item));
                        if (this.currentForm === 'tanda') {
                            this.formData.keterangan = item.keterangan || item.name.split(' : ')[1];
                        }
                    },

                    deleteDataItem(id) {
                        if (!this.currentForm) {
                            Swal.fire('Error', 'Tipe data tidak terdeteksi.', 'error');
                            return;
                        }
                        Swal.fire({
                            title: 'Hapus Data?',
                            text: 'Data yang dihapus tidak dapat dikembalikan!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Ya, Hapus!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                let endpoint = this.routes[this.currentForm].destroy.replace(':id',
                                    id);
                                fetch(endpoint, {
                                        method: 'DELETE',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': this.csrfToken
                                        }
                                    })
                                    .then(async response => {
                                        const resData = await response.json();
                                        if (!response.ok) throw resData;
                                        return resData;
                                    })
                                    .then(data => {
                                        if (data.status === 'success') {
                                            Swal.fire('Terhapus!', data.message, 'success')
                                                .then(() => this.refreshPage());
                                        }
                                    })
                                    .catch(error => {
                                        Swal.fire('Gagal!', error.message ||
                                            'Gagal menghubungi server.', 'error');
                                    });
                            }
                        });
                    },

                    saveNewData() {
                        const saveButton = document.getElementById('saveNewDataButton');
                        saveButton.disabled = true;
                        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
                        const isEdit = this.formData.id ? true : false;
                        let endpoint = isEdit ? this.routes[this.currentForm].update.replace(':id', this
                            .formData.id) : this.routes[this.currentForm].store;
                        let method = isEdit ? 'PUT' : 'POST';
                        fetch(endpoint, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken
                                },
                                body: JSON.stringify({
                                    ...this.formData,
                                    _method: method
                                })
                            })
                            .then(async response => {
                                const result = await response.json();
                                if (!response.ok) throw result;
                                return result;
                            })
                            .then(data => {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => this.refreshPage());
                            })
                            .catch(error => {
                                let errorList = '';
                                if (error.errors) {
                                    errorList = '<ul class="text-left mt-2 list-disc list-inside">';
                                    Object.values(error.errors).flat().forEach(msg => {
                                        errorList += `<li class="text-sm">${msg}</li>`;
                                    });
                                    errorList += '</ul>';
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal Menyimpan',
                                    html: (error.message || 'Terjadi kesalahan') + errorList,
                                    confirmButtonColor: '#3b82f6'
                                });
                            })
                            .finally(() => {
                                saveButton.disabled = false;
                                saveButton.innerHTML = 'Simpan Data';
                            });
                    },

                    selectedSiswas() {
                        const target = this.showModal ? this.editingJadwal : this.newJadwal;
                        if (!target.siswa_ids) return [];
                        return this.allSiswas.filter(s => target.siswa_ids.includes(s.id)).sort((a, b) => a
                            .name.localeCompare(b.name));
                    },

                    filteredAvailableSiswas() {
                        const search = this.searchModalSiswa.toLowerCase().trim();
                        const selectedIds = this.showModal ? this.editingJadwal.siswa_ids : this.newJadwal
                            .siswa_ids;
                        if (search === '') return [];
                        return this.allSiswas.filter(s => {
                            const isSelected = selectedIds && selectedIds.includes(s.id);
                            const matchesSearch = s.name.toLowerCase().includes(search) || (s
                                .panggilan && s.panggilan.toLowerCase().includes(search));
                            return !isSelected && matchesSearch;
                        }).sort((a, b) => a.name.localeCompare(b.name)).slice(0, 10);
                    },

                    addSiswa(id) {
                        const target = this.showModal ? this.editingJadwal : this.newJadwal;
                        if (!target.siswa_ids.includes(id)) target.siswa_ids.push(id);
                        this.searchModalSiswa = '';
                    },

                    removeSiswa(id) {
                        const target = this.showModal ? this.editingJadwal : this.newJadwal;
                        target.siswa_ids = target.siswa_ids.filter(sid => sid !== id);
                        if (this.selectedStudentDetail && this.selectedStudentDetail.id === id) this
                            .selectedStudentDetail = null;
                    },

                    hasTanda(siswa) {
                        return siswa.tandas && siswa.tandas.length > 0;
                    },

                    viewStudentDetail(siswa) {
                        this.selectedStudentDetail = siswa;
                    },

                    markTandaForDeletion(tandaId, studentId) {
                        if (!confirm('Hapus tanda ini?')) return;
                        this.deletedTandaIds.push(tandaId);
                        this.selectedStudentDetail.tandas = this.selectedStudentDetail.tandas.filter(t => t
                            .id !== tandaId);
                        const studentIndex = this.allSiswas.findIndex(s => s.id === studentId);
                        if (studentIndex !== -1) {
                            this.allSiswas[studentIndex].tandas = this.allSiswas[studentIndex].tandas
                                .filter(t => t.id !== tandaId);
                        }
                    },

                    saveJadwal() {
                        const saveButton = document.getElementById('saveJadwalButton');
                        saveButton.disabled = true;
                        saveButton.innerHTML = 'Menyimpan...';
                        const payload = {
                            ...this.editingJadwal,
                            deleted_tanda_ids: this.deletedTandaIds
                        };
                        fetch(this.routes.jadwal.updateKelas, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken
                                },
                                body: JSON.stringify(payload)
                            })
                            .then(async r => {
                                const res = await r.json();
                                if (!r.ok) throw res;
                                return res;
                            })
                            .then(data => {
                                if (data.status === 'success') {
                                    this.showModal = false;
                                    this.deletedTandaIds = [];
                                    window.location.reload();
                                }
                            })
                            .catch(error => {
                                Swal.fire('Gagal!', error.message || 'Gagal menyimpan.', 'error');
                            })
                            .finally(() => {
                                saveButton.disabled = false;
                                saveButton.innerHTML = 'Simpan Perubahan';
                            });
                    },

                    openAddJadwalModal(hariId, sesiId) {
                        this.newJadwal = {
                            hari_id: hariId,
                            sesi_id: sesiId,
                            mata_pelajaran_id: this.allMapels.length > 0 ? this.allMapels[0].id : null,
                            guru_id: this.allGurus.length > 0 ? this.allGurus[0].id : null,
                            ruang_id: this.allRuangs.length > 0 ? this.allRuangs[0].id : null,
                            siswa_ids: []
                        };
                        this.searchModalSiswa = '';
                        this.showAddJadwalModal = true;
                        this.selectedStudentDetail = null;
                    },

                    saveNewJadwal() {
                        const btn = document.getElementById('saveNewJadwalButton');
                        btn.disabled = true;
                        btn.innerHTML = 'Menyimpan...';
                        fetch(this.routes.jadwal.store, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken
                                },
                                body: JSON.stringify(this.newJadwal)
                            })
                            .then(async r => {
                                const res = await r.json();
                                if (!r.ok) throw res;
                                return res;
                            })
                            .then(data => {
                                if (data.status === 'success') window.location.reload();
                            })
                            .catch(error => {
                                Swal.fire('Gagal!', error.message || 'Gagal menyimpan.', 'error');
                            })
                            .finally(() => {
                                btn.disabled = false;
                                btn.innerHTML = 'Simpan Jadwal Baru';
                            });
                    },

                    openStashOptions() {
                        Swal.fire({
                            title: 'Stash Manager',
                            text: 'Backup atau Restore data jadwal, perubahan jadwal bersifat permanen dan tidak bisa dibatalkan!',
                            icon: 'info',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: '<i class="fas fa-cloud-download-alt mr-2"></i> Download Stash',
                            denyButtonText: '<i class="fas fa-cloud-upload-alt mr-2"></i> Upload Stash',
                            confirmButtonColor: '#059669',
                            denyButtonColor: '#3b82f6',
                            background: document.documentElement.classList.contains('dark') ?
                                '#1f2937' : '#fff',
                            color: document.documentElement.classList.contains('dark') ? '#fff' :
                                '#000',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.downloadStash();
                            } else if (result.isDenied) {
                                this.uploadStash();
                            }
                        });
                    },

                    downloadStash() {
                        window.location.href = "{{ route('admin.jadwal.downloadStash') }}";
                    },

                    async uploadStash() {
                        const {
                            value: file
                        } = await Swal.fire({
                            title: 'Upload & Replace Jadwal',
                            text: 'PERINGATAN: Seluruh jadwal saat ini akan dihapus dan diganti dengan isi file ini!',
                            input: 'file',
                            inputAttributes: {
                                'accept': '.stash',
                                'aria-label': 'Pilih file stash'
                            },
                            showCancelButton: true,
                            confirmButtonText: 'PROSES REPLACE',
                            confirmButtonColor: '#d33',
                        });

                        if (file) {
                            Swal.showLoading();
                            let formData = new FormData();
                            formData.append('file_stash', file);
                            formData.append('_token', this.csrfToken);

                            try {
                                const response = await fetch(
                                    "{{ route('admin.jadwal.uploadStash') }}", {
                                        method: 'POST',
                                        body: formData,
                                        headers: {
                                            'Accept': 'application/json'
                                        }
                                    });
                                const res = await response.json();

                                if (res.status === 'success') {
                                    Swal.fire('Berhasil!', res.message, 'success').then(() => window
                                        .location.reload());
                                } else {
                                    Swal.fire('Gagal!', res.message, 'error');
                                }
                            } catch (err) {
                                Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
                            }
                        }
                    },

                    openExportOptions() {
                        const searchTerm = this.universalSearch.trim();
                        let htmlContent = searchTerm ?
                            `<div class='text-left'><p class='text-gray-600 mb-2'>Pencarian aktif:</p><div class='bg-blue-50 p-3 rounded border border-blue-200 text-blue-800 text-lg font-bold text-center'>'${searchTerm}'</div></div>` :
                            `<div class='text-left'><div class='bg-yellow-50 p-3 rounded border border-yellow-200 text-yellow-800'>Semua data akan diproses.</div></div>`;
                        Swal.fire({
                            title: 'Export Opsi',
                            html: htmlContent,
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: '<i class="fas fa-file-pdf"></i> PDF',
                            denyButtonText: '<i class="fas fa-copy"></i> Copy WA',
                            confirmButtonColor: '#d33',
                            denyButtonColor: '#3b82f6'
                        }).then((result) => {
                            const params = new URLSearchParams();
                            if (searchTerm) params.append('search', searchTerm);
                            if (result.isConfirmed) {
                                window.open(this.routes.jadwal.export+'?' + params.toString(),
                                    '_blank');
                            } else if (result.isDenied) {
                                Swal.showLoading();
                                fetch(this.routes.jadwal.generateText + '?' + params.toString())
                                    .then(r => r.json())
                                    .then(data => {
                                        if (data.status === 'success') {
                                            navigator.clipboard.writeText(data.text).then(
                                                () => {
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Disalin!',
                                                        timer: 1500,
                                                        showConfirmButton: false
                                                    });
                                                });
                                        }
                                    });
                            }
                        });
                    }
                }));
            });

            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.kanban-slot').forEach(slot => {
                    new Sortable(slot, {
                        group: 'kanban',
                        animation: 150,
                        ghostClass: 'opacity-50',
                        onEnd: function(evt) {
                            const toSlot = evt.to;
                            const fromSlot = evt.from;
                            const card = evt.item;
                            if (!toSlot || !toSlot.dataset.sesiId || !toSlot.dataset.hariId) return;
                            fetch('{{ route('admin.jadwal.updatePosisi') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        mapel_id: card.dataset.mapelId,
                                        guru_id: card.dataset.guruId,
                                        ruang_id: card.dataset.ruangId,
                                        old_hari_id: fromSlot.dataset.hariId,
                                        old_sesi_id: fromSlot.dataset.sesiId,
                                        new_hari_id: toSlot.dataset.hariId,
                                        new_sesi_id: toSlot.dataset.sesiId,
                                    })
                                })
                                .then(async r => {
                                    const res = await r.json();
                                    if (!r.ok) throw res;
                                    return res;
                                })
                                .then(data => {
                                    if (data.status === 'success') {
                                        card.dataset.hariId = toSlot.dataset.hariId;
                                        card.dataset.sesiId = toSlot.dataset.sesiId;
                                    } else {
                                        fromSlot.appendChild(card);
                                        Swal.fire('Gagal!', data.message, 'error');
                                    }
                                })
                                .catch(error => {
                                    fromSlot.appendChild(card);
                                    Swal.fire('Gagal!', error.message ||
                                        'Gagal memindahkan jadwal.', 'error');
                                });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
