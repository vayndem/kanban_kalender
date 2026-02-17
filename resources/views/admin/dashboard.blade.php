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
                allHaris: {{ $haris->toJson() }},
                allSesis: {{ $sesis->sortBy('start_time')->values()->toJson() }},
                {{-- FITUR BARU: SORT SESI UNTUK DATA JS --}}
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
                            <button @click="activeTab = 'jadwal'"
                                :class="{
                                    'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'jadwal',
                                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeTab !== 'jadwal'
                                }"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Jadwal Pelajaran
                            </button>

                            <button @click="activeTab = 'berita'"
                                :class="{
                                    'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'berita',
                                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-600': activeTab !== 'berita'
                                }"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Berita
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
                                                @click.prevent="currentForm = 'siswa'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem"><i class="fas fa-user-graduate w-5 mr-2"></i> Siswa</a>
                                            <a href="#"
                                                @click.prevent="currentForm = 'tanda'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem"><i class="fas fa-sticky-note w-5 mr-2"></i> Tanda /
                                                Catatan</a>
                                        </div>
                                    </div>

                                    <div x-show="currentForm" x-transition
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
                                        style="display: none;">
                                        <div @click="currentForm = ''" class="absolute inset-0"></div>
                                        <div @click.stop
                                            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md overflow-hidden relative"
                                            x-show="currentForm" x-transition>
                                            @include('admin.form', ['type' => 'currentForm'])
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-grow max-w-2xl">
                                <label for="universalSearch"
                                    class="block text-sm font-medium text-gray-700 dark:text-white mb-1"><i
                                        class="fas fa-search mr-1"></i> Pencarian Universal</label>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="text" id="universalSearch" x-model.debounce.300ms="universalSearch"
                                        placeholder="Cari Hari, Sesi, Mapel, Guru, atau Nama Siswa..."
                                        class="w-full pl-10 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i
                                            class="fas fa-search text-gray-400"></i></div>
                                    <button x-show="universalSearch.length > 0" @click="universalSearch = ''"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"><i
                                            class="fas fa-times-circle"></i></button>
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
                                        Sesi</th>

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
                                            <span
                                                class="block mt-1 text-[10px] font-normal ">{{ $date->translatedFormat('d F Y') }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800">
                                {{-- FITUR BARU: DI SINI SESI DIURUTKAN BERDASARKAN START_TIME --}}
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

                                                            // FITUR BARU: JIKA SISWA < 4, PAKAI BG LEBIH TERANG (bg-white/100)
                                                            $cardBgClass =
                                                                $siswaCount < 4
                                                                    ? 'bg-white/100 dark:bg-gray-600/100'
                                                                    : 'bg-white/90 dark:bg-gray-700/90';
                                                        @endphp

                                                        <div class="kanban-card group relative {{ $cardBgClass }} backdrop-blur-sm p-2.5 mb-2 rounded-lg shadow border-l-4 text-sm cursor-move transition-all duration-200 ease-out hover:shadow-xl hover:-translate-y-1"
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
                                                                $nextTick(() => { showModal = true; });"
                                                                class="absolute top-1 right-1 p-1.5 rounded-full bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-white hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-500 dark:hover:text-white transition-all duration-200 opacity-0 group-hover:opacity-100">
                                                                <i class="fas fa-pencil-alt fa-xs"></i>
                                                            </button>

                                                            <strong
                                                                class="block font-bold text-gray-900 dark:text-white truncate">{{ $groupedClass['mapel']->name }}</strong>
                                                            <span
                                                                class="block text-gray-600 dark:text-gray-200 mt-1">{{ $groupedClass['guru']->name }}</span>
                                                            <span
                                                                class="block text-gray-500 dark:text-gray-300 text-xs mt-1">Ruang:
                                                                {{ $groupedClass['ruang']->name }}</span>
                                                            <div
                                                                class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                                                <span
                                                                    class="block text-gray-500 dark:text-gray-300 text-xs font-semibold">Siswa
                                                                    ({{ $siswaCount }})
                                                                    :</span>
                                                                <ol
                                                                    class="list-decimal list-inside text-gray-500 dark:text-gray-200 text-xs pl-1">
                                                                    @foreach ($siswaList as $siswa)
                                                                        <li
                                                                            class="{{ $siswa->tandas->isNotEmpty() ? 'text-yellow-600 dark:text-yellow-400 font-bold' : '' }}">
                                                                            {{ $siswa->name }} - {{ $siswa->kelas }}
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

                <div x-show="activeTab === 'berita'" style="display: none;">
                    @include('admin.card')
                </div>

                {{-- MODAL EDIT JADWAL - TETAP ADA SEMUANYA --}}
                <div x-show="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
                    style="display: none;">
                    <div @click="showModal = false" class="absolute inset-0"></div>
                    <div @click.stop
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-5xl overflow-hidden relative">
                        <div
                            class="flex justify-between items-center p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Jadwal & Catatan Siswa
                            </h3>
                            <button @click="showModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"><i
                                    class="fas fa-times"></i></button>
                        </div>
                        <form id="editJadwalForm">
                            <div class="flex flex-col md:flex-row h-[70vh]">
                                <div class="w-full md:w-2/3 p-6 overflow-y-auto border-r dark:border-gray-700">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-white">Mata
                                                Pelajaran</label>
                                            <select x-model="editingJadwal.mapel_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                                <template x-for="mapel in allMapels" :key="mapel.id">
                                                    <option :value="mapel.id" x-text="mapel.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-white">Guru</label>
                                            <select x-model="editingJadwal.guru_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                                <template x-for="guru in allGurus" :key="guru.id">
                                                    <option :value="guru.id" x-text="guru.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-white">Ruang</label>
                                            <select x-model="editingJadwal.ruang_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white">
                                                <template x-for="ruang in allRuangs" :key="ruang.id">
                                                    <option :value="ruang.id" x-text="ruang.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-white mb-2">Siswa
                                                Terpilih</label>
                                            <div class="p-1 min-h-[100px]">
                                                <ul class="space-y-2">
                                                    <template x-for="siswa in selectedSiswas()" :key="siswa.id">
                                                        <li class="flex justify-between items-center text-sm p-3 rounded cursor-pointer transition-all duration-200 border border-transparent"
                                                            @click="viewStudentDetail(siswa)"
                                                            :class="selectedStudentDetail && selectedStudentDetail.id === siswa
                                                                .id ?
                                                                'bg-blue-50 dark:bg-blue-900/30 ring-1 ring-blue-500' :
                                                                'hover:bg-gray-100 dark:hover:bg-gray-700'">
                                                            <span x-text="siswa.name"
                                                                :class="hasTanda(siswa) ?
                                                                    'text-yellow-600 dark:text-yellow-400 font-bold' :
                                                                    'font-medium text-gray-800 dark:text-white'"></span>
                                                            <button @click.stop.prevent="removeSiswa(siswa.id)"
                                                                class="text-red-500 text-xs">Hapus</button>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </div>
                                            <div class="relative mt-4">
                                                <input type="text" x-model.debounce.300ms="searchModalSiswa"
                                                    placeholder="Cari & Tambah Siswa..."
                                                    class="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:text-white">
                                                <div x-show="filteredAvailableSiswas().length > 0"
                                                    class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border rounded shadow-lg max-h-48 overflow-y-auto">
                                                    <template x-for="siswa in filteredAvailableSiswas()"
                                                        :key="siswa.id">
                                                        <button @click.prevent="addSiswa(siswa.id)"
                                                            class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                                                            x-text="siswa.name"></button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full md:w-1/3 bg-gray-50 dark:bg-gray-900 p-6 flex flex-col">
                                    <h4 class="font-semibold dark:text-white mb-4"><i
                                            class="fas fa-info-circle mr-1 text-blue-500"></i> Detail Siswa</h4>
                                    <template x-if="selectedStudentDetail">
                                        <div class="overflow-y-auto">
                                            <p class="font-bold text-lg dark:text-white"
                                                x-text="selectedStudentDetail.name"></p>
                                            <div class="mt-4 space-y-3">
                                                <template x-for="tanda in selectedStudentDetail.tandas"
                                                    :key="tanda.id">
                                                    <div
                                                        class="relative bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-3 rounded shadow-sm">
                                                        <p class="text-sm dark:text-white" x-text="tanda.keterangan">
                                                        </p>
                                                        <button type="button"
                                                            @click.stop="markTandaForDeletion(tanda.id, selectedStudentDetail.id)"
                                                            class="absolute top-1 right-1 text-red-500"><i
                                                                class="fas fa-times"></i></button>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div
                                class="px-6 py-4 bg-white dark:bg-gray-800 border-t dark:border-gray-700 flex justify-end space-x-3">
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded dark:bg-gray-600 dark:text-white">Batal</button>
                                <button type="button" id="saveJadwalButton" @click.prevent="saveJadwal"
                                    class="px-4 py-2 text-sm text-white bg-blue-600 rounded">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- MODAL TAMBAH JADWAL BARU - TETAP ADA --}}
                <div x-show="showAddJadwalModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
                    style="display: none;">
                    <div @click="showAddJadwalModal = false" class="absolute inset-0"></div>
                    <div @click.stop
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg overflow-hidden relative">
                        <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                            <h3 class="text-lg font-semibold dark:text-white">Tambah Jadwal Baru</h3><button
                                @click="showAddJadwalModal = false" class="text-gray-400"><i
                                    class="fas fa-times"></i></button>
                        </div>
                        <form @submit.prevent="saveNewJadwal">
                            <div class="p-6 space-y-4">
                                <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
                                    Slot: <span x-text="allHaris.find(h => h.id === newJadwal.hari_id)?.name"></span>,
                                    <span x-text="allSesis.find(s => s.id === newJadwal.sesi_id)?.name"></span>
                                </div>
                                <div><label class="block text-sm font-medium dark:text-white">Mata
                                        Pelajaran</label><select x-model.number="newJadwal.mata_pelajaran_id"
                                        class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-700 dark:text-white"><template
                                            x-for="mapel in allMapels" :key="mapel.id">
                                            <option :value="mapel.id" x-text="mapel.name"></option>
                                        </template></select></div>
                                <div><label class="block text-sm font-medium dark:text-white">Guru</label><select
                                        x-model.number="newJadwal.guru_id"
                                        class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-700 dark:text-white"><template
                                            x-for="guru in allGurus" :key="guru.id">
                                            <option :value="guru.id" x-text="guru.name"></option>
                                        </template></select></div>
                                <div><label class="block text-sm font-medium dark:text-white">Ruang</label><select
                                        x-model.number="newJadwal.ruang_id"
                                        class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-700 dark:text-white"><template
                                            x-for="ruang in allRuangs" :key="ruang.id">
                                            <option :value="ruang.id" x-text="ruang.name"></option>
                                        </template></select></div>
                                <div><label class="block text-sm font-medium dark:text-white">Tambah Siswa</label>
                                    <div class="min-h-[60px] border p-2 rounded mb-2 dark:bg-gray-900"><template
                                            x-for="siswa in selectedSiswas()" :key="siswa.id">
                                            <div
                                                class="flex justify-between items-center text-xs p-1 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white">
                                                <span x-text="siswa.name"></span><button
                                                    @click.prevent="removeSiswa(siswa.id)"
                                                    class="text-red-500">X</button>
                                            </div>
                                        </template></div><input type="text"
                                        x-model.debounce.300ms="searchModalSiswa" placeholder="Cari..."
                                        class="w-full border-gray-300 rounded-md dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3"><button
                                    type="button" @click="showAddJadwalModal = false"
                                    class="px-4 py-2 text-sm text-gray-700">Batal</button><button type="submit"
                                    id="saveNewJadwalButton"
                                    class="px-4 py-2 text-sm text-white bg-green-600 rounded">Simpan</button></div>
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
                    activeTab: 'jadwal',
                    universalSearch: '',
                    showModal: false,
                    showAddJadwalModal: false,
                    editingJadwal: {},
                    deletedTandaIds: [],
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

                    // SEMUA LOGIKA ASLI KAMU (Detail, Sortable, Export, dll)
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
                        return this.allSiswas.filter(s => !selectedIds.includes(s.id) && s.name
                            .toLowerCase().includes(search)).slice(0, 10);
                    },
                    addSiswa(id) {
                        const target = this.showModal ? this.editingJadwal : this.newJadwal;
                        if (!target.siswa_ids.includes(id)) target.siswa_ids.push(id);
                        this.searchModalSiswa = '';
                    },
                    removeSiswa(id) {
                        const target = this.showModal ? this.editingJadwal : this.newJadwal;
                        target.siswa_ids = target.siswa_ids.filter(sId => sId !== id);
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
                    },
                    saveJadwal() {
                        const btn = document.getElementById('saveJadwalButton');
                        btn.disabled = true;
                        btn.innerHTML = 'Menyimpan...';
                        fetch(this.routes.jadwal.updateKelas, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken
                                },
                                body: JSON.stringify({
                                    ...this.editingJadwal,
                                    deleted_tanda_ids: this.deletedTandaIds
                                })
                            })
                            .then(r => r.json()).then(d => d.status === 'success' ? window.location
                                .reload() : alert(d.message)).finally(() => btn.disabled = false);
                    },
                    openAddJadwalModal(hId, sId) {
                        this.newJadwal = {
                            hari_id: hId,
                            sesi_id: sId,
                            mata_pelajaran_id: this.allMapels[0]?.id,
                            guru_id: this.allGurus[0]?.id,
                            ruang_id: this.allRuangs[0]?.id,
                            siswa_ids: []
                        };
                        this.showAddJadwalModal = true;
                    },
                    saveNewJadwal() {
                        const btn = document.getElementById('saveNewJadwalButton');
                        btn.disabled = true;
                        fetch(this.routes.jadwal.store, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken
                                },
                                body: JSON.stringify(this.newJadwal)
                            })
                            .then(r => r.json()).then(d => d.status === 'success' ? window.location
                                .reload() : alert(d.message)).finally(() => btn.disabled = false);
                    },
                    openExportOptions() {
                        const term = this.universalSearch.trim();
                        Swal.fire({
                                title: 'Export Opsi',
                                showCancelButton: true,
                                showDenyButton: true,
                                confirmButtonText: 'Download PDF',
                                denyButtonText: 'Copy WA'
                            })
                            .then((result) => {
                                const params = term ? '?search=' + term : '';
                                if (result.isConfirmed) window.open(this.routes.jadwal.export+params,
                                    '_blank');
                                else if (result.isDenied) {
                                    fetch(this.routes.jadwal.generateText + params).then(r => r.json())
                                        .then(d => {
                                            if (d.status === 'success') {
                                                navigator.clipboard.writeText(d.text).then(() =>
                                                    Swal.fire('Berhasil Disalin!', '',
                                                        'success'));
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
                            const card = evt.item;
                            fetch('{{ route('admin.jadwal.updatePosisi') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    mapel_id: card.dataset.mapelId,
                                    guru_id: card.dataset.guruId,
                                    ruang_id: card.dataset.ruangId,
                                    old_hari_id: evt.from.dataset.hariId,
                                    old_sesi_id: evt.from.dataset.sesiId,
                                    new_hari_id: evt.to.dataset.hariId,
                                    new_sesi_id: evt.to.dataset.sesiId,
                                })
                            }).then(r => r.json()).then(d => {
                                if (d.status === 'success') {
                                    card.dataset.hariId = evt.to.dataset.hariId;
                                    card.dataset.sesiId = evt.to.dataset.sesiId;
                                } else {
                                    evt.from.appendChild(card);
                                    alert(d.message);
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
