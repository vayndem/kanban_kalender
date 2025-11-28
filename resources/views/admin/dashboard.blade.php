<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">

            <div class="container mx-auto" x-data="{
                activeTab: 'jadwal',
                searchPelajaran: '',
                searchSiswa: '',
                showModal: false,
                showAddJadwalModal: false,
                editingJadwal: {},
                deletedTandaIds: [],
                newJadwal: { hari_id: null, sesi_id: null, mata_pelajaran_id: null, guru_id: null, ruang_id: null, siswa_ids: [] },
                allMapels: {{ $allMapels->toJson() }},
                allGurus: {{ $allGurus->toJson() }},
                allRuangs: {{ $allRuangs->toJson() }},
                allSiswas: {{ $allSiswas->toJson() }},
                allHaris: {{ $haris->toJson() }},
                allSesis: {{ $sesis->toJson() }},
                searchModalSiswa: '',
                showAddMenu: false,
                currentForm: '',
                selectedStudentDetail: null,

                formData: {},
                activeFormTab: 'input',
                formSearch: '',

                getFilteredList() {
                    const search = this.formSearch.toLowerCase();
                    let source = [];

                    if (this.currentForm === 'mapel') source = this.allMapels;
                    else if (this.currentForm === 'guru') source = this.allGurus;
                    else if (this.currentForm === 'ruang') source = this.allRuangs;
                    else if (this.currentForm === 'sesi') source = this.allSesis;
                    else if (this.currentForm === 'siswa') {
                        source = this.allSiswas.map(s => ({
                            ...s,
                            name: (s.panggilan || s.name) + ' - ' + (s.kelas || '-')
                        }));
                    } else if (this.currentForm === 'tanda') {
                        let flatTandas = [];
                        this.allSiswas.forEach(siswa => {
                            if (siswa.tandas && siswa.tandas.length > 0) {
                                siswa.tandas.forEach(tanda => {
                                    flatTandas.push({
                                        id: tanda.id,
                                        name: (siswa.panggilan || siswa.name) + ' - ' + (siswa.kelas || '-') + ' : ' + tanda.keterangan,
                                        original_date: tanda.created_at
                                    });
                                });
                            }
                        });
                        source = flatTandas;
                    }

                    if (search === '') return source;
                    return source.filter(item => item.name.toLowerCase().includes(search));
                },

                selectedSiswas() {
                    const target = this.showModal ? this.editingJadwal : this.newJadwal;
                    if (!target.siswa_ids) return [];
                    return this.allSiswas
                        .filter(s => target.siswa_ids.includes(s.id))
                        .sort((a, b) => a.name.localeCompare(b.name));
                },

                filteredAvailableSiswas() {
                    const search = this.searchModalSiswa.toLowerCase().trim();
                    const selectedIds = this.showModal ? this.editingJadwal.siswa_ids : this.newJadwal.siswa_ids;
                    if (search === '') return [];

                    return this.allSiswas
                        .filter(s => {
                            const isSelected = selectedIds && selectedIds.includes(s.id);
                            const matchesSearch = s.name.toLowerCase().includes(search) || (s.panggilan && s.panggilan.toLowerCase().includes(search));
                            return !isSelected && matchesSearch;
                        })
                        .sort((a, b) => a.name.localeCompare(b.name))
                        .slice(0, 10);
                },

                addSiswa(id) {
                    const target = this.showModal ? this.editingJadwal : this.newJadwal;
                    if (!target.siswa_ids.includes(id)) {
                        target.siswa_ids.push(id);
                    }
                    this.searchModalSiswa = '';
                },

                removeSiswa(id) {
                    const target = this.showModal ? this.editingJadwal : this.newJadwal;
                    target.siswa_ids = target.siswa_ids.filter(siswaId => siswaId !== id);

                    if (this.selectedStudentDetail && this.selectedStudentDetail.id === id) {
                        this.selectedStudentDetail = null;
                    }
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

                    this.selectedStudentDetail.tandas = this.selectedStudentDetail.tandas.filter(t => t.id !== tandaId);

                    const studentIndex = this.allSiswas.findIndex(s => s.id === studentId);
                    if (studentIndex !== -1) {
                        this.allSiswas[studentIndex].tandas = this.allSiswas[studentIndex].tandas.filter(t => t.id !== tandaId);
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

                    fetch('{{ route('admin.jadwal.updateKelas') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                this.showModal = false;
                                this.deletedTandaIds = [];
                                alert('Perubahan berhasil disimpan! Halaman akan dimuat ulang.');
                                window.location.reload();
                            } else {
                                alert('Gagal menyimpan: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Gagal menyimpan. Cek konsol.');
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
                    const saveButton = document.getElementById('saveNewJadwalButton');
                    saveButton.disabled = true;
                    saveButton.innerHTML = 'Menyimpan...';

                    fetch('{{ route('admin.jadwal.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.newJadwal)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                this.showAddJadwalModal = false;
                                alert(data.message + ' Halaman akan dimuat ulang.');
                                window.location.reload();
                            } else {
                                const errorMsg = data.errors ? Object.values(data.errors).flat().join('\n') : (data.message || 'Gagal menyimpan.');
                                alert('Gagal menyimpan:\n' + errorMsg);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Gagal menyimpan. Cek konsol atau koneksi.');
                        })
                        .finally(() => {
                            saveButton.disabled = false;
                            saveButton.innerHTML = 'Simpan Jadwal Baru';
                        });
                },

                openExportOptions() {
                    let siswaOptions = '<option value=\'\'>-- Semua Siswa --</option>';
                    this.allSiswas.forEach(s => {
                        // Menggunakan nama lengkap karena panggilan bisa kosong
                        siswaOptions += `<option value='${s.id}'>${s.name}</option>`;
                    });

                    let hariOptions = '<option value=\'\'>-- Semua Hari --</option>';
                    this.allHaris.forEach(h => {
                        hariOptions += `<option value='${h.id}'>${h.name}</option>`;
                    });

                    Swal.fire({
                        title: 'Filter Export PDF',
                        html: `
                                                    <div class='text-left space-y-4'>
                                                        <div>
                                                            <label class='block text-sm font-medium text-gray-700 mb-1'>Pilih Siswa</label>
                                                            <select id='swal-siswa' class='w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border bg-white text-gray-900'>
                                                                ${siswaOptions}
                                                            </select>
                                                            <p class='text-xs text-gray-500 mt-1'>Biarkan kosong untuk mencetak semua siswa.</p>
                                                        </div>
                                                        <div>
                                                            <label class='block text-sm font-medium text-gray-700 mb-1'>Pilih Hari</label>
                                                            <select id='swal-hari' class='w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border bg-white text-gray-900'>
                                                                ${hariOptions}
                                                            </select>
                                                             <p class='text-xs text-gray-500 mt-1'>Biarkan kosong untuk mencetak satu minggu penuh.</p>
                                                        </div>
                                                    </div>
                                                `,
                        showCancelButton: true,
                        confirmButtonText: '<i class=\'fas fa-file-pdf\'></i> Download PDF',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#d33',
                        preConfirm: () => {
                            return {
                                siswa_id: document.getElementById('swal-siswa').value,
                                hari_id: document.getElementById('swal-hari').value
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const params = new URLSearchParams();
                            if (result.value.siswa_id) params.append('siswa_id', result.value.siswa_id);
                            if (result.value.hari_id) params.append('hari_id', result.value.hari_id);

                            window.open('{{ route('admin.jadwal.export') }}?' + params.toString(), '_blank');
                        }
                    });
                }
            }">

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
                                    <i class="fas fa-file-pdf mr-2"></i> Export PDF
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
                                                role="menuitem">
                                                <i class="fas fa-book-open w-5 mr-2"></i> Mata Pelajaran
                                            </a>
                                            <a href="#" @click.prevent="currentForm = 'guru'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem">
                                                <i class="fas fa-chalkboard-teacher w-5 mr-2"></i> Guru
                                            </a>
                                            <a href="#"
                                                @click.prevent="currentForm = 'ruang'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem">
                                                <i class="fas fa-building w-5 mr-2"></i> Ruang
                                            </a>
                                            <a href="#" @click.prevent="currentForm = 'sesi'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem">
                                                <i class="fas fa-clock w-5 mr-2"></i> Sesi Waktu
                                            </a>
                                            <a href="#"
                                                @click.prevent="currentForm = 'siswa'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem">
                                                <i class="fas fa-user-graduate w-5 mr-2"></i> Siswa
                                            </a>
                                            <a href="#"
                                                @click.prevent="currentForm = 'tanda'; showAddMenu = false"
                                                class="block px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
                                                role="menuitem">
                                                <i class="fas fa-sticky-note w-5 mr-2"></i> Tanda / Catatan
                                            </a>
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

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-grow max-w-2xl">
                                <div>
                                    <label for="searchPelajaran"
                                        class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                                        <i class="fas fa-book-open mr-1"></i> Cari Pelajaran
                                    </label>
                                    <input type="text" id="searchPelajaran" x-model.debounce.300ms="searchPelajaran"
                                        placeholder="Ketik nama pelajaran..."
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label for="searchSiswa"
                                        class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                                        <i class="fas fa-user-graduate mr-1"></i> Cari Siswa
                                    </label>
                                    <input type="text" id="searchSiswa" x-model.debounce.300ms="searchSiswa"
                                        placeholder="Ketik nama siswa..."
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
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
                                    @foreach ($haris as $hari)
                                        <th
                                            class="border border-gray-300 dark:border-gray-600 p-3 text-center uppercase text-xs tracking-wider font-semibold text-gray-600 dark:text-white min-w-[200px]">
                                            {{ $hari->name }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800">
                                @foreach ($sesis as $sesi)
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
                                                            $siswaNames = $siswaList->pluck('name')->implode(', ');
                                                            $siswaIDsString = $siswaList->pluck('id')->implode(',');
                                                        @endphp

                                                        <div class="kanban-card group relative bg-white/90 dark:bg-gray-700/90 backdrop-blur-sm p-2.5 mb-2 rounded-lg shadow border-l-4 text-sm cursor-move transition-all duration-200 ease-out hover:shadow-xl hover:-translate-y-1"
                                                            style="border-left-color: {{ $groupedClass['mapel']->border_color }};"
                                                            data-mapel-id="{{ $groupedClass['mapel']->id }}"
                                                            data-guru-id="{{ $groupedClass['guru']->id }}"
                                                            data-ruang-id="{{ $groupedClass['ruang']->id }}"
                                                            data-hari-id="{{ $hari->id }}"
                                                            data-sesi-id="{{ $sesi->id }}"
                                                            data-siswa-ids="[{{ $siswaIDsString }}]"
                                                            :class="{
                                                                'opacity-30': (searchPelajaran !== '' && !
                                                                        '{{ strtolower($groupedClass['mapel']->name) }}'
                                                                        .includes(searchPelajaran.toLowerCase())) ||
                                                                    (searchSiswa !== '' && !
                                                                        '{{ strtolower($siswaNames) }}'.includes(
                                                                            searchSiswa.toLowerCase()))
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
                                                                    class="block text-gray-500 dark:text-gray-300 text-xs font-semibold">
                                                                    Siswa:
                                                                </span>
                                                                <ol
                                                                    class="list-decimal list-inside text-gray-500 dark:text-gray-200 text-xs pl-1">
                                                                    @foreach ($groupedClass['siswa_list'] as $siswa)
                                                                        <li
                                                                            class="{{ $siswa->tandas->isNotEmpty() ? 'text-yellow-600 dark:text-yellow-400 font-bold' : '' }}">
                                                                            {{ $siswa->panggilan ?? $siswa->name }} -
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

                <div x-show="activeTab === 'berita'" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform -translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0" style="display: none;">
                    @include('admin.card')
                </div>

                <div x-show="showModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
                    style="display: none;">

                    <div @click="showModal = false" class="absolute inset-0"></div>

                    <div @click.stop
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-5xl overflow-hidden relative"
                        x-show="showModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                        <div
                            class="flex justify-between items-center p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Jadwal & Catatan Siswa
                            </h3>
                            <button @click="showModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <form id="editJadwalForm">
                            <div class="flex flex-col md:flex-row h-[70vh]">

                                <div class="w-full md:w-2/3 p-6 overflow-y-auto border-r dark:border-gray-700">
                                    <div class="space-y-4">
                                        <div>
                                            <label for="editMapel"
                                                class="block text-sm font-medium text-gray-700 dark:text-white">Mata
                                                Pelajaran</label>
                                            <select id="editMapel" x-model="editingJadwal.mapel_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
                                                <template x-for="mapel in allMapels" :key="mapel.id">
                                                    <option :value="mapel.id" x-text="mapel.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="editGuru"
                                                class="block text-sm font-medium text-gray-700 dark:text-white">Guru</label>
                                            <select id="editGuru" x-model="editingJadwal.guru_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
                                                <template x-for="guru in allGurus" :key="guru.id">
                                                    <option :value="guru.id" x-text="guru.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="editRuang"
                                                class="block text-sm font-medium text-gray-700 dark:text-white">Ruang</label>
                                            <select id="editRuang" x-model="editingJadwal.ruang_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
                                                <template x-for="ruang in allRuangs" :key="ruang.id">
                                                    <option :value="ruang.id" x-text="ruang.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-white mb-2">Siswa
                                                Terpilih (Klik Nama untuk Lihat Catatan)</label>
                                            <div class="p-1 min-h-[100px]">
                                                <ul class="space-y-2">
                                                    <template x-for="siswa in selectedSiswas()" :key="siswa.id">
                                                        <li class="flex justify-between items-center text-sm p-3 rounded cursor-pointer transition-all duration-200 border border-transparent"
                                                            @click="viewStudentDetail(siswa)"
                                                            :class="{
                                                                'bg-blue-50 dark:bg-blue-900/30 ring-1 ring-blue-500': selectedStudentDetail &&
                                                                    selectedStudentDetail.id === siswa.id,
                                                                'hover:bg-gray-100 dark:hover:bg-gray-700': !
                                                                    selectedStudentDetail || selectedStudentDetail
                                                                    .id !== siswa.id
                                                            }">
                                                            <div class="flex items-center">
                                                                <span x-text="siswa.name"
                                                                    :class="hasTanda(siswa) ?
                                                                        'text-yellow-600 dark:text-yellow-400 font-bold' :
                                                                        'font-medium text-gray-800 dark:text-white'"></span>
                                                            </div>
                                                            <button @click.stop.prevent="removeSiswa(siswa.id)"
                                                                type="button"
                                                                class="font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs ml-3 transition-colors hover:underline">
                                                                Hapus
                                                            </button>
                                                        </li>
                                                    </template>
                                                    <li x-show="editingJadwal.siswa_ids && editingJadwal.siswa_ids.length === 0"
                                                        class="text-sm text-gray-400 text-center py-8 border-2 border-dashed border-gray-200 rounded-lg">
                                                        <i class="fas fa-users mb-2 text-2xl"></i><br> Belum ada siswa
                                                        terpilih
                                                    </li>
                                                </ul>
                                            </div>

                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-white mt-6">Cari
                                                & Tambah Siswa</label>
                                            <div class="relative mt-1">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-search text-gray-400"></i>
                                                </div>
                                                <input type="text" x-model.debounce.300ms="searchModalSiswa"
                                                    @keydown.escape.prevent="searchModalSiswa = ''"
                                                    placeholder="Ketik nama siswa untuk menambah..."
                                                    class="pl-10 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">

                                                <div x-show="filteredAvailableSiswas().length > 0" x-transition
                                                    @click.away="searchModalSiswa = ''"
                                                    class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                                    <template x-for="siswa in filteredAvailableSiswas()"
                                                        :key="siswa.id">
                                                        <button @click.prevent="addSiswa(siswa.id)" type="button"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600">
                                                            <span x-text="siswa.name"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="w-full md:w-1/3 bg-gray-50 dark:bg-gray-900 border-l dark:border-gray-700 flex flex-col">
                                    <div class="p-4 border-b dark:border-gray-700 bg-white dark:bg-gray-800">
                                        <h4 class="font-semibold text-gray-800 dark:text-white">
                                            <i class="fas fa-info-circle mr-1 text-blue-500"></i> Detail Siswa
                                        </h4>
                                    </div>

                                    <div class="p-6 overflow-y-auto flex-grow">
                                        <template x-if="selectedStudentDetail">
                                            <div class="animate-fadeIn">
                                                <div class="mb-4 text-center">
                                                    <div
                                                        class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-3">
                                                        <span
                                                            class="text-2xl font-bold text-blue-600 dark:text-blue-300"
                                                            x-text="selectedStudentDetail.name.charAt(0)"></span>
                                                    </div>
                                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white"
                                                        x-text="selectedStudentDetail.name"></h3>
                                                    <span class="text-xs text-gray-500 dark:text-gray-300">Siswa
                                                        Terdaftar</span>
                                                </div>

                                                <div class="mt-6">
                                                    <h5
                                                        class="text-xs font-bold uppercase text-gray-500 dark:text-gray-300 tracking-wider mb-3">
                                                        Catatan / Tanda</h5>
                                                    <template x-if="hasTanda(selectedStudentDetail)">
                                                        <ul class="space-y-3">
                                                            <template x-for="tanda in selectedStudentDetail.tandas"
                                                                :key="tanda.id">
                                                                <li
                                                                    class="relative bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-3 rounded shadow-sm text-sm text-gray-800 dark:text-white mb-2">
                                                                    <div class="pr-8">
                                                                        <p x-text="tanda.keterangan"
                                                                            class="break-words"></p>
                                                                        <span class="text-xs text-gray-400 mt-1 block"
                                                                            x-text="new Date(tanda.created_at).toLocaleDateString()"></span>
                                                                    </div>
                                                                    <button type="button"
                                                                        @click.stop="markTandaForDeletion(tanda.id, selectedStudentDetail.id)"
                                                                        class="absolute top-2 right-2 text-red-500 hover:text-red-700 hover:bg-red-100 p-1.5 rounded-full transition-colors z-10"
                                                                        title="Hapus Tanda">
                                                                        <i class="fas fa-times fa-lg"></i>
                                                                    </button>
                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </template>
                                                    <template x-if="!hasTanda(selectedStudentDetail)">
                                                        <div
                                                            class="text-center py-6 bg-white dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700">
                                                            <i
                                                                class="fas fa-check-circle text-green-400 text-3xl mb-2"></i>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak
                                                                ada catatan khusus untuk siswa ini.</p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!selectedStudentDetail">
                                            <div
                                                class="h-full flex flex-col items-center justify-center text-center text-gray-400 p-4">
                                                <i class="fas fa-mouse-pointer text-4xl mb-4 opacity-50"></i>
                                                <p class="text-sm">Klik nama siswa di daftar sebelah kiri untuk melihat
                                                    detail catatan dan informasi lainnya.</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="px-6 py-4 bg-white dark:bg-gray-800 border-t dark:border-gray-700 flex justify-end space-x-3">
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-500">
                                    Batal
                                </button>
                                <button type="button" id="saveJadwalButton" @click.prevent="saveJadwal"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="showAddJadwalModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
                    style="display: none;">

                    <div @click="showAddJadwalModal = false" class="absolute inset-0"></div>

                    <div @click.stop
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg overflow-hidden relative"
                        x-show="showAddJadwalModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                        <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Jadwal Baru</h3>
                            <button @click="showAddJadwalModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <form @submit.prevent="saveNewJadwal">
                            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                <div
                                    class="bg-blue-50 dark:bg-blue-900/50 p-3 rounded-md border border-blue-200 dark:border-blue-800">
                                    <p class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                                        Slot Terpilih:
                                        <span
                                            x-text="allHaris.find(h => h.id === newJadwal.hari_id)?.name || '...'"></span>,
                                        <span
                                            x-text="allSesis.find(s => s.id === newJadwal.sesi_id)?.name || '...'"></span>
                                    </p>
                                </div>

                                <div>
                                    <label for="newMapel"
                                        class="block text-sm font-medium text-gray-700 dark:text-white">Mata
                                        Pelajaran</label>
                                    <select id="newMapel" x-model.number="newJadwal.mata_pelajaran_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
                                        <template x-for="mapel in allMapels" :key="mapel.id">
                                            <option :value="mapel.id" x-text="mapel.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label for="newGuru"
                                        class="block text-sm font-medium text-gray-700 dark:text-white">Guru</label>
                                    <select id="newGuru" x-model.number="newJadwal.guru_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
                                        <template x-for="guru in allGurus" :key="guru.id">
                                            <option :value="guru.id" x-text="guru.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label for="newRuang"
                                        class="block text-sm font-medium text-gray-700 dark:text-white">Ruang</label>
                                    <select id="newRuang" x-model.number="newJadwal.ruang_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:text-white">
                                        <template x-for="ruang in allRuangs" :key="ruang.id">
                                            <option :value="ruang.id" x-text="ruang.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-white">Siswa
                                        Terpilih</label>
                                    <div
                                        class="mt-2 p-3 border dark:border-gray-600 rounded-md min-h-[80px] bg-gray-50 dark:bg-gray-900/50 max-h-40 overflow-y-auto">
                                        <ul class="space-y-2">
                                            <template x-for="siswa in selectedSiswas()" :key="siswa.id">
                                                <li
                                                    class="flex justify-between items-center text-sm py-1 px-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    <div class="flex items-center cursor-pointer"
                                                        :class="hasTanda(siswa) ?
                                                            'text-yellow-600 dark:text-yellow-400 font-bold' :
                                                            'text-gray-800 dark:text-white'">
                                                        <span x-text="siswa.name"></span>
                                                        <template x-if="hasTanda(siswa)">
                                                            <i
                                                                class="fas fa-exclamation-circle ml-2 text-yellow-500 animate-pulse"></i>
                                                        </template>
                                                    </div>
                                                    <button @click.prevent="removeSiswa(siswa.id)" type="button"
                                                        class="font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-xs ml-3">
                                                        Hapus
                                                    </button>
                                                </li>
                                            </template>
                                            <li x-show="selectedSiswas().length === 0"
                                                class="text-sm text-gray-400 text-center py-2">
                                                Belum ada siswa terpilih
                                            </li>
                                        </ul>
                                    </div>

                                    <label class="block text-sm font-medium text-gray-700 dark:text-white mt-3">Cari &
                                        Tambah Siswa</label>
                                    <div class="relative">
                                        <input type="text" x-model.debounce.300ms="searchModalSiswa"
                                            @keydown.escape.prevent="searchModalSiswa = ''"
                                            placeholder="Ketik nama siswa untuk menambah..."
                                            class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">

                                        <div x-show="filteredAvailableSiswas().length > 0" x-transition
                                            @click.away="searchModalSiswa = ''"
                                            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                            <template x-for="siswa in filteredAvailableSiswas()"
                                                :key="siswa.id">
                                                <button @click.prevent="addSiswa(siswa.id)" type="button"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <span x-text="siswa.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3">
                                <button type="button" @click="showAddJadwalModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-500">
                                    Batal
                                </button>
                                <button type="submit" id="saveNewJadwalButton"
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Simpan Jadwal Baru
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.kanban-slot').forEach(slot => {
                    new Sortable(slot, {
                        group: 'kanban',
                        animation: 150,
                        ghostClass: 'opacity-50',
                        onEnd: function(evt) {
                            const card = evt.item;
                            const fromSlot = evt.from;
                            const toSlot = evt.to;

                            const updateData = {
                                mapel_id: card.dataset.mapelId,
                                guru_id: card.dataset.guruId,
                                ruang_id: card.dataset.ruangId,
                                old_hari_id: fromSlot.dataset.hariId,
                                old_sesi_id: fromSlot.dataset.sesiId,
                                new_hari_id: toSlot.dataset.hariId,
                                new_sesi_id: toSlot.dataset.sesiId,
                            };

                            fetch('{{ route('admin.jadwal.updatePosisi') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify(updateData)
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        card.dataset.hariId = toSlot.dataset.hariId;
                                        card.dataset.sesiId = toSlot.dataset.sesiId;
                                    } else {
                                        fromSlot.appendChild(card);
                                        alert('Update Gagal: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    fromSlot.appendChild(card);
                                    alert('Update Gagal. Periksa koneksi Anda.');
                                });
                        }
                    });
                });
            });

            function saveNewData() {
                const formType = this.currentForm;
                const data = this.formData;
                const saveButton = document.getElementById('saveNewDataButton');

                saveButton.disabled = true;
                saveButton.innerHTML = 'Menyimpan...';

                let endpoint = '';
                if (formType === 'mapel') endpoint = '{{ route('admin.mapel.store') }}';
                else if (formType === 'guru') endpoint = '{{ route('admin.guru.store') }}';
                else if (formType === 'ruang') endpoint = '{{ route('admin.ruang.store') }}';
                else if (formType === 'sesi') endpoint = '{{ route('admin.sesi.store') }}';
                else if (formType === 'siswa') endpoint = '{{ route('admin.siswa.store') }}';
                else if (formType === 'tanda') endpoint = '{{ route('admin.tanda.store') }}';

                fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Data ' + (formType === 'tanda' ? 'Catatan Siswa' : formType) +
                                ' berhasil ditambahkan! Halaman akan dimuat ulang.');
                            window.location.reload();
                        } else {
                            alert('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan server.'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Gagal menyimpan. Cek konsol atau koneksi.');
                    })
                    .finally(() => {
                        saveButton.disabled = false;
                        saveButton.innerHTML = 'Simpan Data Baru';
                    });
            }

            window.saveNewData = saveNewData;
        </script>
    @endpush

</x-app-layout>
