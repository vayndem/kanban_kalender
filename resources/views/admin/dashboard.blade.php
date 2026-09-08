<x-admin-layout :active-tab="$activeTab">
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[.2em] text-success">
                    Control Center</p>
                <h2 class="text-xl font-black tracking-tight text-base-content sm:text-2xl">Dashboard
                    Operasional</h2>
            </div>
            <div
                class="mt-2 inline-flex items-center gap-2 self-start rounded-xl border border-success/40 bg-success/10 px-3 py-2 text-xs font-bold text-success sm:mt-0">
                <span class="relative flex h-2 w-2"><span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-75"></span><span
                        class="relative inline-flex h-2 w-2 rounded-full bg-success"></span></span>
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
            jadwal: {
                store: '{{ route('admin.jadwal.store') }}',
                updateKelas: '{{ route('admin.jadwal.updateKelas') }}',
                export: '{{ route('admin.jadwal.export') }}',
                exportPdf: '{{ route('jadwal.kalender.export') }}',
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
                <div class="bg-base-100 p-4 sm:p-6 rounded-xl shadow-lg mb-6">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-6">

                        <div class="flex flex-wrap gap-3">
                            <button @click.prevent="openExportOptions()" type="button" class="btn btn-export text-base">
                                <i class="fas fa-file-export mr-2"></i> Export / Copy
                            </button>

                            <a :href="pdfExportUrl" target="_blank" class="btn btn-primary text-base">
                                <i class="fas fa-file-pdf mr-2"></i> Export PDF
                            </a>

                            <button @click.prevent="openStashOptions()" type="button" class="btn btn-info text-base">
                                <i class="fas fa-database mr-2"></i> Stash
                            </button>

                            <a href="{{ route('admin.workshop.index') }}" class="btn btn-accent text-base">
                                <i class="fas fa-toolbox mr-2"></i> Kelola Guru / Ruang / Sesi / Mapel
                            </a>
                        </div>

                        <div class="grow max-w-2xl">
                            <label for="universalSearch"
                                class="block text-sm font-medium text-base-content/80 mb-1">
                                <i class="fas fa-search mr-1"></i> Pencarian Universal
                            </label>
                            <div class="relative rounded-md shadow-xs">
                                <input type="text" id="universalSearch" x-model.debounce.300ms="universalSearch"
                                    placeholder="Cari Hari, Sesi, Mapel, Guru, atau Nama Siswa..."
                                    class="w-full pl-10 px-3 py-2 border border-base-300 rounded-md shadow-xs focus:outline-hidden focus:ring-primary focus:border-primary bg-base-100 text-base-content">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-base-content/60"></i>
                                </div>
                                <button x-show="universalSearch.length > 0" @click="universalSearch = ''"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-base-content/60 hover:text-base-content/70 cursor-pointer">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 flex gap-1.5 overflow-x-auto pb-1 lg:hidden">
                    @foreach ($haris as $hari)
                        <button type="button" @click="activeDayId = {{ $hari->id }}"
                            :class="Number(activeDayId) === {{ $hari->id }} ?
                                'bg-success text-white' :
                                'bg-base-200 text-base-content/70'"
                            class="shrink-0 rounded-lg px-3 py-2 text-xs font-bold transition-colors">
                            {{ $hari->name }}
                        </button>
                    @endforeach
                </div>

                <div class="overflow-x-auto shadow-md rounded-lg">
                    <table class="min-w-full w-full border-collapse table-fixed"
                        data-update-posisi-url="{{ route('admin.jadwal.updatePosisi') }}">
                        <thead class="bg-base-200">
                            <tr>
                                <th
                                    class="border border-base-300 p-3 text-center uppercase text-xs tracking-wider font-semibold text-base-content/70 w-24 lg:w-32">
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
                                    <th x-show="dayVisible({{ $hari->id }})"
                                        class="border border-base-300 p-3 text-center uppercase text-xs tracking-wider font-semibold text-base-content/70 min-w-[200px]">
                                        <div class="text-base">{{ $hari->name }}</div>
                                        @php
                                            $offset = $dayOffsets[$hari->name] ?? $index;
                                            $date = $startOfWeek->copy()->addDays($offset);
                                        @endphp
                                        <span class="block mt-1 text-[11px] font-normal ">
                                            {{ $date->translatedFormat('d F Y') }}
                                        </span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-base-100">
                            @foreach ($sesis->sortBy('start_time') as $sesi)
                                <tr x-show="sessionMatches({{ $sesi->id }})"
                                    class="even:bg-base-200/50">
                                    <td
                                        class="border border-base-300 p-2 text-center align-middle font-semibold text-base-content/80">
                                        {{ $sesi->name }}
                                        <span class="block text-xs text-base-content/60 font-normal">
                                            {{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}
                                        </span>
                                    </td>

                                    @foreach ($haris as $hari)
                                        <td x-show="dayVisible({{ $hari->id }})"
                                            class="kanban-slot border border-base-300 p-2 align-top h-64 relative cursor-pointer hover:bg-base-200 transition-colors duration-150"
                                            id="slot-{{ $hari->id }}-{{ $sesi->id }}"
                                            data-hari-id="{{ $hari->id }}" data-sesi-id="{{ $sesi->id }}"
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
                                                        $cardBgColor = $siswaCount < 4 ? 'bg-base-200' : 'bg-base-200/80';
                                                    @endphp

                                                    <div class="kanban-card group relative {{ $cardBgColor }} p-2.5 mb-2 rounded-field shadow-xs border border-base-300 border-l-4 text-sm cursor-move transition-all duration-200 ease-out hover:shadow-lg hover:-translate-y-0.5 hover:border-primary"
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
                                                            class="absolute top-1 right-1 p-2.5 rounded-full bg-base-200 text-base-content/60 hover:bg-primary/10 hover:text-primary transition-all duration-200 opacity-100 lg:opacity-0 lg:group-hover:opacity-100">
                                                            <i class="fas fa-pencil-alt fa-xs"></i>
                                                        </button>

                                                        <strong
                                                            class="block font-bold text-base-content truncate">
                                                            {{ $groupedClass['mapel']->name }}
                                                        </strong>
                                                        <span class="block text-base-content/70 mt-1">
                                                            {{ $groupedClass['guru']->name }}
                                                        </span>
                                                        <span
                                                            class="block text-base-content/60 text-xs mt-1">
                                                            Ruang: {{ $groupedClass['ruang']->name }}
                                                        </span>
                                                        <div
                                                            class="mt-2 pt-2 border-t border-base-300">
                                                            <span
                                                                class="block text-base-content/60 text-xs font-semibold">Siswa:</span>
                                                            <ol
                                                                class="list-decimal list-inside text-base-content/60 text-xs pl-1">
                                                                @foreach ($groupedClass['siswa_list'] as $siswa)
                                                                    <li
                                                                        class="{{ $siswa->tandas->isNotEmpty() ? 'text-warning font-bold' : '' }}">
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
            <div x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 py-8 bg-black bg-opacity-60 backdrop-blur-xs sm:items-center">

                <div @click="showModal = false" class="absolute inset-0"></div>

                <div @click.stop
                    class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden relative border"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <div
                        class="flex justify-between items-center p-5 border-b bg-base-200">
                        <h3 class="text-xl font-bold text-base-content flex items-center gap-2">
                            <i class="fas fa-calendar-check text-primary"></i> Edit Jadwal & Catatan Siswa
                        </h3>
                        <button @click="showModal = false"
                            class="text-base-content/60 hover:text-base-content/70 p-2.5 hover:bg-base-300 rounded-lg transition-colors">
                            <i class="fas fa-times fa-lg"></i>
                        </button>
                    </div>

                    <form id="editJadwalForm" @submit.prevent="saveJadwal" class="flex flex-1 flex-col min-h-0">
                        <div class="flex flex-1 min-h-0 flex-col overflow-y-auto md:h-[75vh] md:flex-row md:overflow-visible">
                            <div
                                class="w-full md:w-2/3 p-6 overflow-y-auto border-r bg-base-100 space-y-5">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-1.5">Mata
                                            Pelajaran</label>
                                        <select x-model="editingJadwal.mapel_id"
                                            class="w-full rounded-xl border border-base-300 p-2.5 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden">
                                            <template x-for="mapel in allMapels" :key="mapel.id">
                                                <option :value="mapel.id" x-text="mapel.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-1.5">Guru</label>
                                        <select x-model="editingJadwal.guru_id"
                                            class="w-full rounded-xl border border-base-300 p-2.5 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden">
                                            <template x-for="guru in availableGurus(editingJadwal)"
                                                :key="guru.id">
                                                <option :value="guru.id" x-text="guru.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-1.5">Ruang</label>
                                        <select x-model="editingJadwal.ruang_id"
                                            class="w-full rounded-xl border border-base-300 p-2.5 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden">
                                            <template x-for="ruang in availableRuangs(editingJadwal)"
                                                :key="ruang.id">
                                                <option :value="ruang.id" x-text="ruang.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <div class="border-t border-base-300 pt-4">
                                    <label
                                        class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-2">Cari
                                        & Tambah Siswa Baru</label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/60">
                                            <i class="fas fa-user-plus text-sm"></i>
                                        </div>
                                        <input type="text" x-model.debounce.300ms="searchModalSiswa"
                                            @keydown.escape.prevent="searchModalSiswa = ''"
                                            placeholder="Ketik nama siswa terdaftar untuk ditambahkan ke kelas ini..."
                                            class="pl-10 w-full px-4 py-2.5 border border-base-300 rounded-xl shadow-xs focus:ring-2 focus:ring-primary bg-base-100 text-base-content text-sm focus:outline-hidden">
                                        <div x-show="availableStudentResults.length > 0" x-transition
                                            @click.away="searchModalSiswa = ''"
                                            class="absolute z-30 w-full mt-1 bg-base-100 border rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-base-300">
                                            <template x-for="siswa in availableStudentResults" :key="siswa.id">
                                                <button @click.prevent="addSiswa(siswa.id)" type="button"
                                                    class="block w-full text-left px-4 py-2.5 text-sm text-base-content/80 hover:bg-primary/10 transition-colors font-medium">
                                                    <i class="fas fa-plus text-xs text-primary mr-2"></i><span
                                                        x-text="siswa.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-base-300 pt-4">
                                    <label
                                        class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-2">Daftar
                                        Siswa Terpilih Di Kelas Ini (<span
                                            x-text="selectedStudentResults.length"></span>)</label>
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[35vh] overflow-y-auto p-1 custom-scrollbar">
                                        <template x-for="siswa in selectedStudentResults" :key="siswa.id">
                                            <div class="flex justify-between items-center p-3 bg-base-200 rounded-xl border border-base-300 cursor-pointer hover:ring-2 hover:ring-primary transition-all"
                                                @click="viewStudentDetail(siswa)"
                                                :class="{
                                                    'ring-2 ring-primary bg-primary/10 border-transparent': selectedStudentDetail &&
                                                        selectedStudentDetail.id === siswa.id
                                                }">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <i class="fas fa-circle-user shrink-0 text-base-content/60"
                                                        :class="hasTanda(siswa) ? 'text-warning' : ''"></i>
                                                    <span x-text="siswa.name" class="text-sm font-bold truncate"
                                                        :class="hasTanda(siswa) ? 'text-warning' :
                                                            'text-base-content'"></span>
                                                </div>
                                                <button @click.stop.prevent="removeSiswa(siswa.id)" type="button"
                                                    class="p-2 text-error hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg text-xs transition-colors shrink-0 font-semibold">
                                                    <i class="fas fa-user-minus"></i> Hapus
                                                </button>
                                            </div>
                                        </template>
                                        <div x-show="editingJadwal.siswa_ids && editingJadwal.siswa_ids.length === 0"
                                            class="col-span-full text-sm text-base-content/60 text-center py-10 border-2 border-dashed border-base-300 bg-base-200/50 rounded-xl">
                                            <i class="fas fa-users mb-2 text-3xl"></i><br> Belum ada siswa terpilih
                                            di kelas ini
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="w-full md:w-1/3 bg-base-200 border-l flex flex-col">
                                <div class="p-4 border-b bg-base-100">
                                    <h4
                                        class="font-bold text-sm text-base-content uppercase tracking-wider flex items-center gap-2">
                                        <i class="fas fa-clipboard-user text-primary"></i> Detail & Catatan Siswa
                                    </h4>
                                </div>
                                <div class="p-6 overflow-y-auto grow">
                                    <template x-if="selectedStudentDetail">
                                        <div class="animate-fadeIn space-y-6">
                                            <div class="text-center">
                                                <div
                                                    class="w-16 h-16 bg-gradient-to-br from-blue-500 to-accent rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                                    <span class="text-2xl font-black text-white"
                                                        x-text="selectedStudentDetail.name.charAt(0)"></span>
                                                </div>
                                                <h3 class="text-base font-black text-base-content leading-tight"
                                                    x-text="selectedStudentDetail.name"></h3>
                                                <span
                                                    class="inline-block mt-1 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider bg-base-300 text-base-content/70 rounded"
                                                    x-text="'Kelas: ' + (selectedStudentDetail.kelas || 'N/A')"></span>
                                            </div>
                                            <div class="space-y-3">
                                                <h5
                                                    class="text-xs font-black uppercase text-base-content/60 tracking-widest border-b pb-1 flex items-center gap-1.5">
                                                    <i class="fas fa-sticky-note text-warning"></i> Catatan
                                                    Khusus
                                                </h5>
                                                <template x-if="hasTanda(selectedStudentDetail)">
                                                    <div
                                                        class="space-y-2.5 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                                                        <template x-for="tanda in selectedStudentDetail.tandas"
                                                            :key="tanda.id">
                                                            <div
                                                                class="relative bg-yellow-50/60 dark:bg-yellow-950/20 border-l-4 border-yellow-400 p-3 rounded-xl shadow-xs text-xs text-base-content">
                                                                <p x-text="tanda.keterangan"
                                                                    class="break-words font-medium leading-relaxed pr-6">
                                                                </p>
                                                                <span
                                                                    class="text-[10px] text-base-content/60 mt-1.5 block font-mono"
                                                                    x-text="new Date(tanda.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></span>
                                                                <button type="button"
                                                                    @click.stop="markTandaForDeletion(tanda.id, selectedStudentDetail.id)"
                                                                    class="absolute top-2 right-2 text-error hover:text-error p-1 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition-colors"
                                                                    title="Hapus Catatan">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="!hasTanda(selectedStudentDetail)">
                                                    <div
                                                        class="text-center py-6 bg-base-100 rounded-xl border border-base-300 p-4">
                                                        <i
                                                            class="fas fa-circle-check text-green-400 text-2xl mb-1 block"></i>
                                                        <p class="text-xs text-base-content/60">Tidak
                                                            ada catatan untuk siswa ini.</p>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!selectedStudentDetail">
                                        <div
                                            class="h-full flex flex-col items-center justify-center text-center text-base-content/60 p-4 min-h-[200px]">
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
                            class="px-6 py-4 bg-base-200 border-t flex justify-end gap-2.5">
                            <button type="button" @click="showModal = false"
                                class="px-4 py-2 text-sm font-bold text-base-content/70 bg-base-100 border border-base-300 rounded-xl shadow-xs hover:bg-base-200 transition-all">Batal</button>
                            <button type="button" id="saveJadwalButton" @click.prevent="saveJadwal"
                                class="px-5 py-2 text-sm font-bold text-white bg-primary border border-transparent rounded-xl shadow-md hover:bg-primary/90 transition-all flex items-center gap-2"><i
                                    class="fas fa-save"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <template x-if="showAddJadwalModal">
            <div x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 py-8 bg-black bg-opacity-60 backdrop-blur-xs sm:items-center">

                <div @click="showAddJadwalModal = false" class="absolute inset-0"></div>

                <div @click.stop
                    class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden relative border"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <div
                        class="flex justify-between items-center p-5 border-b bg-base-200">
                        <h3 class="text-xl font-bold text-base-content flex items-center gap-2">
                            <i class="fas fa-calendar-plus text-green-500"></i> Tambah Jadwal Baru
                        </h3>
                        <button @click="showAddJadwalModal = false"
                            class="text-base-content/60 hover:text-base-content/70 p-2.5 hover:bg-base-300 rounded-lg transition-colors">
                            <i class="fas fa-times fa-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="saveNewJadwal" class="flex flex-1 flex-col min-h-0">
                        <div class="flex flex-1 min-h-0 flex-col overflow-y-auto md:h-[75vh] md:flex-row md:overflow-visible">
                            <div
                                class="w-full md:w-2/3 p-6 overflow-y-auto border-r bg-base-100 space-y-5">
                                <div
                                    class="bg-gradient-to-r from-blue-500 to-accent p-4 rounded-xl shadow-inner text-white flex items-center gap-3">
                                    <div class="p-2.5 bg-white/20 rounded-xl">
                                        <i class="fas fa-clock text-lg"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase font-black tracking-widest text-blue-100">Slot
                                            Mengajar Terpilih</p>
                                        <p class="text-base font-bold">
                                            <span x-text="hariIndex[newJadwal.hari_id]?.name || '...'"></span>,
                                            <span x-text="sesiIndex[newJadwal.sesi_id]?.name || '...'"></span>
                                            <span class="text-xs font-normal opacity-80"
                                                x-text="formatSessionTime(newJadwal.sesi_id)"></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-1.5">Mata
                                            Pelajaran</label>
                                        <select x-model.number="newJadwal.mata_pelajaran_id"
                                            class="w-full rounded-xl border border-base-300 p-2.5 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden">
                                            <template x-for="mapel in allMapels" :key="mapel.id">
                                                <option :value="mapel.id" x-text="mapel.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-1.5">Guru</label>
                                        <select x-model.number="newJadwal.guru_id"
                                            class="w-full rounded-xl border border-base-300 p-2.5 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden">
                                            <template x-for="guru in availableGurus(newJadwal)"
                                                :key="guru.id">
                                                <option :value="guru.id" x-text="guru.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-1.5">Ruang</label>
                                        <select x-model.number="newJadwal.ruang_id"
                                            class="w-full rounded-xl border border-base-300 p-2.5 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden">
                                            <template x-for="ruang in availableRuangs(newJadwal)"
                                                :key="ruang.id">
                                                <option :value="ruang.id" x-text="ruang.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <div class="border-t border-base-300 pt-4">
                                    <label
                                        class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-2">Cari
                                        & Hubungkan Siswa</label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/60">
                                            <i class="fas fa-user-search text-sm"></i>
                                        </div>
                                        <input type="text" x-model.debounce.300ms="searchModalSiswa"
                                            @keydown.escape.prevent="searchModalSiswa = ''"
                                            placeholder="Ketik nama lengkap atau panggilan siswa untuk dimasukkan..."
                                            class="pl-10 w-full px-4 py-2.5 border border-base-300 rounded-xl shadow-xs focus:ring-2 focus:ring-primary bg-base-100 text-base-content text-sm focus:outline-hidden">
                                        <div x-show="availableStudentResults.length > 0" x-transition
                                            @click.away="searchModalSiswa = ''"
                                            class="absolute z-30 w-full mt-1 bg-base-100 border rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-base-300">
                                            <template x-for="siswa in availableStudentResults" :key="siswa.id">
                                                <button @click.prevent="addSiswa(siswa.id)" type="button"
                                                    class="block w-full text-left px-4 py-2.5 text-sm text-base-content/80 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors font-medium">
                                                    <i class="fas fa-plus text-xs text-green-500 mr-2"></i><span
                                                        x-text="siswa.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-base-300 pt-4">
                                    <label
                                        class="block text-xs font-bold text-base-content/60 uppercase tracking-wider mb-2">Anggota
                                        Siswa Terpilih Kelas Baru (<span
                                            x-text="selectedStudentResults.length"></span>)</label>
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[30vh] overflow-y-auto p-1 custom-scrollbar">
                                        <template x-for="siswa in selectedStudentResults" :key="siswa.id">
                                            <div class="flex justify-between items-center p-3 bg-base-200 rounded-xl border border-base-300 hover:ring-2 hover:ring-green-500 transition-all cursor-pointer"
                                                @click="viewStudentDetail(siswa)"
                                                :class="{
                                                    'ring-2 ring-green-500 bg-green-50/30 dark:bg-green-900/10 border-transparent': selectedStudentDetail &&
                                                        selectedStudentDetail.id === siswa.id
                                                }">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <i class="fas fa-circle-user shrink-0 text-base-content/60"
                                                        :class="hasTanda(siswa) ? 'text-warning' : ''"></i>
                                                    <span x-text="siswa.name" class="text-sm font-bold truncate"
                                                        :class="hasTanda(siswa) ? 'text-warning' :
                                                            'text-base-content'"></span>
                                                </div>
                                                <button @click.stop.prevent="removeSiswa(siswa.id)" type="button"
                                                    class="p-2 text-error hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg text-xs transition-colors shrink-0 font-semibold">
                                                    <i class="fas fa-minus"></i> Lepas
                                                </button>
                                            </div>
                                        </template>
                                        <div x-show="selectedStudentResults.length === 0"
                                            class="col-span-full text-sm text-base-content/60 text-center py-10 border-2 border-dashed border-base-300 bg-base-200/50 rounded-xl">
                                            <i class="fas fa-users-slash mb-2 text-3xl"></i><br> Belum melampirkan
                                            siswa, silakan cari di kolom atas
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="w-full md:w-1/3 bg-base-200 border-l flex flex-col">
                                <div class="p-4 border-b bg-base-100">
                                    <h4
                                        class="font-bold text-sm text-base-content uppercase tracking-wider flex items-center gap-2">
                                        <i class="fas fa-clipboard-user text-green-500"></i> Catatan Siswa Terpilih
                                    </h4>
                                </div>
                                <div class="p-6 overflow-y-auto grow">
                                    <template x-if="selectedStudentDetail">
                                        <div class="animate-fadeIn space-y-6">
                                            <div class="text-center">
                                                <div
                                                    class="w-16 h-16 bg-gradient-to-br from-green-500 to-success rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-md">
                                                    <span class="text-2xl font-black text-white"
                                                        x-text="selectedStudentDetail.name.charAt(0)"></span>
                                                </div>
                                                <h3 class="text-base font-black text-base-content leading-tight"
                                                    x-text="selectedStudentDetail.name"></h3>
                                                <span
                                                    class="inline-block mt-1 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider bg-base-300 text-base-content/70 rounded"
                                                    x-text="'Kelas: ' + (selectedStudentDetail.kelas || 'N/A')"></span>
                                            </div>
                                            <div class="space-y-3">
                                                <h5
                                                    class="text-xs font-black uppercase text-base-content/60 tracking-widest border-b pb-1 flex items-center gap-1.5">
                                                    <i class="fas fa-sticky-note text-warning"></i> Catatan
                                                    Khusus
                                                </h5>
                                                <template x-if="hasTanda(selectedStudentDetail)">
                                                    <div
                                                        class="space-y-2.5 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                                                        <template x-for="tanda in selectedStudentDetail.tandas"
                                                            :key="tanda.id">
                                                            <div
                                                                class="bg-yellow-50/60 dark:bg-yellow-950/20 border-l-4 border-yellow-400 p-3 rounded-xl shadow-xs text-xs text-base-content">
                                                                <p x-text="tanda.keterangan"
                                                                    class="break-words font-medium leading-relaxed">
                                                                </p>
                                                                <span
                                                                    class="text-[10px] text-base-content/60 mt-1.5 block font-mono"
                                                                    x-text="new Date(tanda.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <template x-if="!hasTanda(selectedStudentDetail)">
                                                    <div
                                                        class="text-center py-6 bg-base-100 rounded-xl border border-base-300 p-4">
                                                        <i
                                                            class="fas fa-circle-check text-green-400 text-2xl mb-1 block"></i>
                                                        <p class="text-xs text-base-content/60">Tidak
                                                            ada catatan untuk siswa ini.</p>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!selectedStudentDetail">
                                        <div
                                            class="h-full flex flex-col items-center justify-center text-center text-base-content/60 p-4 min-h-[200px]">
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
                            class="px-6 py-4 bg-base-200 border-t flex justify-end gap-2.5">
                            <button type="button" @click="showAddJadwalModal = false"
                                class="px-4 py-2 text-sm font-bold text-base-content/70 bg-base-100 border border-base-300 rounded-xl shadow-xs hover:bg-base-200 transition-all">Batal</button>
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
