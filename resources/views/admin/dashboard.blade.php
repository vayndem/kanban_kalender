<x-admin-layout :active-tab="$activeTab">
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[.2em] text-emerald-600 dark:text-emerald-400">Control Center</p>
                <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl">Dashboard Operasional</h2>
            </div>
            <div class="mt-2 inline-flex items-center gap-2 self-start rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300 sm:mt-0">
                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                Sistem aktif
            </div>
        </div>
    </x-slot>

    <div class="container mx-auto" x-data="jadwalHandler({
                allMapels: @js($activeTab === 'jadwal' ? $allMapels : []),
                allGurus: @js($activeTab === 'jadwal' ? $allGurus : []),
                allRuangs: @js($activeTab === 'jadwal' ? $allRuangs : []),
                allSiswas: @js($activeTab === 'jadwal' ? $allSiswas : []),
                jadwalsData: @js($activeTab === 'jadwal' ? $jadwalsData : []),
                allHaris: @js($activeTab === 'jadwal' ? $haris : []),
                allSesis: @js($activeTab === 'jadwal' ? $sesis->sortBy('start_time')->values() : []),
                activeTab: @js($activeTab),
                searchIndex: @js($scheduleSearchIndex),
                occupancy: @js($activeTab === 'jadwal' ? $scheduleOccupancy : []),
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
                        generateText: '{{ route('admin.jadwal.generateText') }}',
                        downloadStash: '{{ route('admin.jadwal.downloadStash') }}',
                        uploadStash: '{{ route('admin.jadwal.uploadStash') }}'
                    }
                }
            })">
                @if ($activeTab === 'jadwal')
                <div x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-xl shadow-lg mb-6">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-6">

                            <div class="flex space-x-3">
                                <button @click.prevent="openExportOptions()" type="button"
                                    class="btn-export text-base">
                                    <i class="fas fa-file-export mr-2"></i> Export / Copy
                                </button>

                                <button @click.prevent="openStashOptions()" type="button"
                                    class="btn-success text-base">
                                    <i class="fas fa-database mr-2"></i> Stash
                                </button>

                                <div class="relative inline-block text-left">
                                    <button @click="showAddMenu = !showAddMenu" type="button"
                                        class="btn-primary w-full text-base">
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

                                    <template x-if="currentForm">
                                    <div x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
                                        <div @click="currentForm = ''; formData = {}; activeFormTab = 'input'; formSearch = ''"
                                            class="absolute inset-0"></div>
                                        <div @click.stop
                                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden relative border dark:border-gray-700 transition-all duration-300">
                                            @include('admin.form', ['type' => 'currentForm'])
                                        </div>
                                    </div>
                                    </template>
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
                        <table class="min-w-full w-full border-collapse table-fixed"
                            data-update-posisi-url="{{ route('admin.jadwal.updatePosisi') }}">
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
                                        <th x-show="dayMatches({{ $hari->id }})"
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
                                    <tr x-show="sessionMatches({{ $sesi->id }})" class="even:bg-gray-50/50 dark:even:bg-gray-800/60">
                                        <td
                                            class="border border-gray-200 dark:border-gray-600 p-2 text-center align-middle font-semibold text-gray-700 dark:text-white">
                                            {{ $sesi->name }}
                                            <span class="block text-xs text-gray-500 dark:text-gray-300 font-normal">
                                                {{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}
                                            </span>
                                        </td>

                                        @foreach ($haris as $hari)
                                            <td x-show="dayMatches({{ $hari->id }})" class="kanban-slot border border-gray-200 dark:border-gray-600 p-2 align-top h-64 relative cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors duration-150"
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
                                                                $nextTick(() => {
                                                                    showModal = true;
                                                                    refreshStudentSelections();
                                                                });
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
                @endif

                @if ($activeTab === 'data_siswa')
                <div x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0">
                    @include('admin.card')
                </div>
                @endif

                @if ($activeTab === 'pembayaran')
                <div x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0">
                    @include('admin.pembayaran')
                </div>
                @endif

                @if ($activeTab === 'ringkasan')
                <div x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0">
                    @include('admin.ringkasan')
                </div>
                @endif

                <template x-if="showModal">
                <div x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm"
                    >

                    <div @click="showModal = false" class="absolute inset-0"></div>

                    <div @click.stop
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden relative border dark:border-gray-700"
                        x-transition:enter="ease-out duration-300"
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
                                                <template x-for="guru in availableGurus(editingJadwal)" :key="guru.id">
                                                    <option :value="guru.id" x-text="guru.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Ruang</label>
                                            <select x-model="editingJadwal.ruang_id"
                                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                <template x-for="ruang in availableRuangs(editingJadwal)" :key="ruang.id">
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
                                            <div x-show="availableStudentResults.length > 0" x-transition
                                                @click.away="searchModalSiswa = ''"
                                                class="absolute z-30 w-full mt-1 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-600">
                                                <template x-for="siswa in availableStudentResults"
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
                                                x-text="selectedStudentResults.length"></span>)</label>
                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[35vh] overflow-y-auto p-1 custom-scrollbar">
                                            <template x-for="siswa in selectedStudentResults" :key="siswa.id">
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
                </template>

                <template x-if="showAddJadwalModal">
                <div x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm"
                    >

                    <div @click="showAddJadwalModal = false" class="absolute inset-0"></div>

                    <div @click.stop
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden relative border dark:border-gray-700"
                        x-transition:enter="ease-out duration-300"
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
                                                    x-text="hariIndex[newJadwal.hari_id]?.name || '...'"></span>,
                                                <span
                                                    x-text="sesiIndex[newJadwal.sesi_id]?.name || '...'"></span>
                                                <span class="text-xs font-normal opacity-80"
                                                    x-text="formatSessionTime(newJadwal.sesi_id)"></span>
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
                                                <template x-for="guru in availableGurus(newJadwal)" :key="guru.id">
                                                    <option :value="guru.id" x-text="guru.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Ruang</label>
                                            <select x-model.number="newJadwal.ruang_id"
                                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                                <template x-for="ruang in availableRuangs(newJadwal)" :key="ruang.id">
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
                                            <div x-show="availableStudentResults.length > 0" x-transition
                                                @click.away="searchModalSiswa = ''"
                                                class="absolute z-30 w-full mt-1 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-600">
                                                <template x-for="siswa in availableStudentResults"
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
                                                x-text="selectedStudentResults.length"></span>)</label>
                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[30vh] overflow-y-auto p-1 custom-scrollbar">
                                            <template x-for="siswa in selectedStudentResults" :key="siswa.id">
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
                                            <div x-show="selectedStudentResults.length === 0"
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
                </template>
            </div>
</x-admin-layout>
