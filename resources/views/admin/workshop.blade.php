<x-admin-layout activeTab="workshop">
    <div x-data="workshopHandler({
        initialMapels: @js($mapels),
        initialGurus: @js($gurus),
        initialRuangs: @js($ruangs),
        initialSesis: @js($sesis),
        initialPakets: @js($pakets),
        initialKemampuans: @js($kemampuans),
        initialKetersediaan: @js($ketersediaan),
        initialSiswas: @js($siswas),
        petaKelas: @js($petaKelas),
        editSiswaId: @js($editSiswaId),
        routes: {
            mapelBase: @js(url('admin/mapel')),
            mapelStore: @js(route('admin.mapel.store')),
            guruBase: @js(url('admin/guru')),
            guruStore: @js(route('admin.guru.store')),
            ruangBase: @js(url('admin/ruang')),
            ruangStore: @js(route('admin.ruang.store')),
            sesiBase: @js(url('admin/sesi')),
            sesiStore: @js(route('admin.sesi.store')),
            kemampuanBase: @js(url('admin/kemampuan')),
            kemampuanStore: @js(route('admin.kemampuan.store')),
            siswaBase: @js(url('admin/siswa')),
            siswaStore: @js(route('admin.siswa.store')),
            siswaImportTemplate: @js(route('admin.siswa.importTemplate')),
            siswaImport: @js(route('admin.siswa.import')),
        },
    })">

        <div x-show="isLoading" x-cloak
            class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-[2px] cursor-wait">
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl px-6 py-5 flex items-center gap-3 border dark:border-gray-700">
                <i class="fas fa-circle-notch fa-spin text-emerald-500 text-xl"></i>
                <p class="text-sm font-bold text-gray-900 dark:text-white">Sedang diproses...</p>
            </div>
        </div>

        <div
            class="bg-white dark:bg-gray-800 p-4 md:p-6 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700">

            <div class="border-b border-gray-50 dark:border-gray-700/50 pb-4 mb-6">
                <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-toolbox text-emerald-500"></i> Workshop
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mt-0.5 text-xs md:text-sm">
                    Tempat menambah dan mengubah data pokok: siswa, guru, ruang, sesi, dan mata pelajaran.
                    Kalau ada kemiripan dengan data yang sudah ada, sistem akan langsung menunjukkannya di sini.
                </p>
            </div>

            <div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-6">
                @php
                    $kartu = [
                        ['Siswa', $ringkasan['siswa'], 'fa-users', 'cyan'],
                        ['Guru', $ringkasan['guru'], 'fa-chalkboard-user', 'emerald'],
                        ['Ruang', $ringkasan['ruang'], 'fa-door-open', 'blue'],
                        ['Sesi', $ringkasan['sesi'], 'fa-clock', 'purple'],
                        ['Mapel', $ringkasan['mapel'], 'fa-book', 'amber'],
                        ['Paket', $ringkasan['paket'], 'fa-box', 'rose'],
                    ];
                @endphp
                @foreach ($kartu as [$label, $nilai, $ikon, $warna])
                    <div
                        class="rounded-xl border border-{{ $warna }}-200/70 dark:border-{{ $warna }}-900/50 bg-{{ $warna }}-50/70 dark:bg-{{ $warna }}-950/20 p-3">
                        <p
                            class="text-[10px] font-bold uppercase tracking-wider text-{{ $warna }}-700 dark:text-{{ $warna }}-300">
                            <i class="fas {{ $ikon }} mr-1"></i>{{ $label }}
                        </p>
                        <p
                            class="mt-1 text-xl font-black text-{{ $warna }}-800 dark:text-{{ $warna }}-200">
                            {{ $nilai }}</p>
                    </div>
                @endforeach
            </div>

            <div
                class="mb-6 flex flex-wrap gap-1.5 bg-gray-50 dark:bg-gray-900/50 p-1.5 rounded-xl border border-gray-100 dark:border-gray-700">
                @foreach ([['key' => 'siswa', 'label' => 'Siswa', 'icon' => 'fa-user-graduate'], ['key' => 'guru', 'label' => 'Guru', 'icon' => 'fa-chalkboard-user'], ['key' => 'ruang', 'label' => 'Ruang', 'icon' => 'fa-door-open'], ['key' => 'sesi', 'label' => 'Sesi', 'icon' => 'fa-clock'], ['key' => 'mapel', 'label' => 'Mata Pelajaran', 'icon' => 'fa-book'], ['key' => 'paket', 'label' => 'Paket', 'icon' => 'fa-box'], ['key' => 'kemampuan', 'label' => 'Kemampuan', 'icon' => 'fa-star'], ['key' => 'ketersediaan', 'label' => 'Slot Kosong', 'icon' => 'fa-calendar-check']] as $tab)
                    <button type="button" @click="activeSection = '{{ $tab['key'] }}'"
                        :class="activeSection === '{{ $tab['key'] }}' ? 'bg-emerald-600 text-white shadow-sm' :
                            'text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-800'"
                        class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all">
                        <i class="fas {{ $tab['icon'] }} mr-1"></i> {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            {{-- ===================== SISWA ===================== --}}
            <div x-show="activeSection === 'siswa'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form id="workshop-siswa-form" @submit.prevent="simpanSiswa" class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider"
                            x-text="siswaForm.id ? 'Ubah Siswa' : 'Tambah Siswa Baru'"></h4>

                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Nama
                                Lengkap</label>
                            <input type="text" x-model="siswaForm.name" required
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <template x-for="mirip in kemiripanSiswaNama" :key="mirip.id">
                                <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                    <i class="fas fa-triangle-exclamation"></i> Mirip dengan siswa yang sudah ada:
                                    <span class="font-bold" x-text="mirip.name"></span>
                                    <span x-text="mirip.kelas ? '(' + mirip.kelas + ')' : ''"></span>
                                </p>
                            </template>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Panggilan</label>
                                <input type="text" x-model="siswaForm.panggilan"
                                    class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Kelas</label>
                                <input type="text" x-model="siswaForm.kelas"
                                    class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>
                        <template x-if="infoKelas.length > 0">
                            <div
                                class="rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50/70 dark:bg-blue-950/20 p-2.5">
                                <p class="text-[11px] font-bold text-blue-700 dark:text-blue-300 mb-1">
                                    <i class="fas fa-circle-info"></i> Kelas <span x-text="siswaForm.kelas"></span>
                                    sudah ada di:
                                </p>
                                <template x-for="(k, i) in infoKelas" :key="i">
                                    <p class="text-[11px] text-blue-600 dark:text-blue-400">
                                        <span x-text="k.hari"></span>, <span x-text="k.sesi"></span> —
                                        <span x-text="k.mapel"></span> · <span x-text="k.guru"></span> · <span
                                            x-text="k.ruang"></span>
                                    </p>
                                </template>
                            </div>
                        </template>

                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Nomor
                                HP</label>
                            <input type="text" x-model="siswaForm.no_hp" @input="formatPhone" placeholder="+62812..."
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <template x-if="kemiripanNoHp.length > 0">
                                <div
                                    class="mt-1.5 rounded-lg border border-amber-300 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20 p-2.5">
                                    <p class="text-[11px] font-bold text-amber-700 dark:text-amber-300">
                                        <i class="fas fa-people-arrows"></i>
                                        Nomor ini sudah dipakai:
                                        <span x-text="kemiripanNoHp.map(s => s.name).join(', ')"></span>.
                                        Kemungkinan mereka bersaudara — untuk penagihan sebaiknya digabungkan.
                                    </p>
                                </div>
                            </template>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Paket
                                Pembayaran</label>
                            <select x-model="siswaForm.paket_pembayaran"
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <option value="">-- Pilih Paket --</option>
                                <template x-for="p in pakets" :key="p.id">
                                    <option :value="String(p.id)" x-text="formatPaketLabel(p)"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Kemampuan</label>
                            <select x-model="siswaForm.tingkat_kemampuan_id"
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <option value="">-- Pilih Kemampuan --</option>
                                <template x-for="k in kemampuans" :key="k.id">
                                    <option :value="String(k.id)" x-text="'Level ' + k.level + ' — ' + k.keterangan"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400" x-show="kemampuans.length === 0">
                                Belum ada level kemampuan — tambahkan dulu di tab <span class="font-bold">Kemampuan</span>.
                            </p>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary text-sm flex-1" :disabled="isLoading">
                                <span x-text="siswaForm.id ? 'Simpan Perubahan' : 'Tambah Siswa'"></span>
                            </button>
                            <button type="button" x-show="siswaForm.id" @click="resetSiswaForm()"
                                class="btn btn-neutral text-sm">Batal</button>
                        </div>
                    </form>

                    <div>
                        <div class="mb-3 rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-3">
                            <h5 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Import Massal</h5>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-2">
                                Download kerangka, isi datanya, lalu upload lagi. Siswa dengan nama yang sudah ada akan diperbarui, yang belum ada akan ditambahkan baru.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a :href="routes.siswaImportTemplate" class="btn-export text-xs px-3 py-1.5 rounded-md">
                                    <i class="fas fa-download"></i> Download Kerangka
                                </a>
                                <input type="file" x-ref="fileImportSiswa" accept=".xlsx,.xls,.csv" class="hidden" @change="importSiswaMassal($event)">
                                <button type="button" @click="$refs.fileImportSiswa.click()" class="btn btn-accent text-xs px-3 py-1.5 rounded-md" :disabled="isLoading">
                                    <i class="fas fa-upload"></i> Upload &amp; Import
                                </button>
                            </div>
                        </div>

                        <input type="text" x-model="searchSiswa" placeholder="Cari nama, kelas, atau no HP..."
                            class="w-full mb-3 rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <div class="space-y-1.5 max-h-[520px] overflow-y-auto pr-1">
                            <template x-for="s in filteredSiswas" :key="s.id">
                                <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-700 transition-all"
                                    :class="Number(siswaForm.id) === Number(s.id) ?
                                        'ring-2 ring-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20' : ''">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate"
                                            x-text="s.name"></p>
                                        <p class="text-[10px] text-gray-400"
                                            x-text="(s.kelas || '-') + ' · ' + (s.no_hp || 'tanpa HP')"></p>
                                    </div>
                                    <button type="button" @click="editSiswa(s)"
                                        class="btn btn-neutral px-2.5 py-1 text-[11px] rounded-md shrink-0">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </template>
                            <template x-if="filteredSiswas.length === 0">
                                <p class="text-xs text-gray-400 italic text-center py-6">Tidak ada siswa ditemukan.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== GURU ===================== --}}
            <div x-show="activeSection === 'guru'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form @submit.prevent="simpanGuru" class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider"
                            x-text="guruForm.id ? 'Ubah Guru' : 'Tambah Guru Baru'"></h4>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Nama
                                Guru</label>
                            <input type="text" x-model="guruForm.name" required
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <template x-for="mirip in kemiripanGuru" :key="mirip.id">
                                <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                    <i class="fas fa-triangle-exclamation"></i> Sudah ada guru bernama <span
                                        class="font-bold" x-text="mirip.name"></span>.
                                </p>
                            </template>
                        </div>
                        <p class="text-[11px] text-gray-400">Untuk mengisi email dan membuat akun login, buka menu
                            <span class="font-bold">Akun Guru</span>.
                        </p>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary text-sm flex-1" :disabled="isLoading">
                                <span x-text="guruForm.id ? 'Simpan Perubahan' : 'Tambah Guru'"></span>
                            </button>
                            <button type="button" x-show="guruForm.id" @click="resetGuruForm()"
                                class="btn btn-neutral text-sm">Batal</button>
                        </div>
                    </form>

                    <div>
                        <input type="text" x-model="searchGuru" placeholder="Cari nama guru..."
                            class="w-full mb-3 rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <div class="space-y-1.5 max-h-[420px] overflow-y-auto pr-1">
                            <template x-for="g in filteredGurus" :key="g.id">
                                <div
                                    class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate"
                                            x-text="g.name"></p>
                                        <p class="text-[10px]"
                                            :class="g.bisa_dihapus ? 'text-gray-400' : 'text-blue-500 font-semibold'"
                                            x-text="g.jumlah_baris_jadwal + ' baris jadwal' + (g.bisa_dihapus ? '' : ' · sedang dipakai')">
                                        </p>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button type="button" @click="editGuru(g)"
                                            class="btn btn-neutral px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-pen-to-square"></i></button>
                                        <button type="button" @click="hapusGuru(g)" :disabled="!g.bisa_dihapus"
                                            :class="g.bisa_dihapus ? '' : 'opacity-40 cursor-not-allowed'"
                                            class="btn-sacred px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-trash-can"></i></button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredGurus.length === 0">
                                <p class="text-xs text-gray-400 italic text-center py-6">Tidak ada guru ditemukan.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== RUANG ===================== --}}
            <div x-show="activeSection === 'ruang'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form @submit.prevent="simpanRuang" class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider"
                            x-text="ruangForm.id ? 'Ubah Ruang' : 'Tambah Ruang Baru'"></h4>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Nama
                                Ruang</label>
                            <input type="text" x-model="ruangForm.name" required
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <template x-for="mirip in kemiripanRuang" :key="mirip.id">
                                <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                    <i class="fas fa-triangle-exclamation"></i> Sudah ada ruang bernama <span
                                        class="font-bold" x-text="mirip.name"></span>.
                                </p>
                            </template>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary text-sm flex-1" :disabled="isLoading">
                                <span x-text="ruangForm.id ? 'Simpan Perubahan' : 'Tambah Ruang'"></span>
                            </button>
                            <button type="button" x-show="ruangForm.id" @click="resetRuangForm()"
                                class="btn btn-neutral text-sm">Batal</button>
                        </div>
                    </form>

                    <div>
                        <input type="text" x-model="searchRuang" placeholder="Cari nama ruang..."
                            class="w-full mb-3 rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <div class="space-y-1.5 max-h-[420px] overflow-y-auto pr-1">
                            <template x-for="r in filteredRuangs" :key="r.id">
                                <div
                                    class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate"
                                            x-text="r.name"></p>
                                        <p class="text-[10px]"
                                            :class="r.bisa_dihapus ? 'text-gray-400' : 'text-blue-500 font-semibold'"
                                            x-text="r.jumlah_baris_jadwal + ' baris jadwal' + (r.bisa_dihapus ? '' : ' · sedang dipakai')">
                                        </p>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button type="button" @click="editRuang(r)"
                                            class="btn btn-neutral px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-pen-to-square"></i></button>
                                        <button type="button" @click="hapusRuang(r)" :disabled="!r.bisa_dihapus"
                                            :class="r.bisa_dihapus ? '' : 'opacity-40 cursor-not-allowed'"
                                            class="btn-sacred px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-trash-can"></i></button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredRuangs.length === 0">
                                <p class="text-xs text-gray-400 italic text-center py-6">Tidak ada ruang ditemukan.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== SESI ===================== --}}
            <div x-show="activeSection === 'sesi'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form @submit.prevent="simpanSesi" class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider"
                            x-text="sesiForm.id ? 'Ubah Sesi' : 'Tambah Sesi Baru'"></h4>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Nama
                                Sesi</label>
                            <input type="text" x-model="sesiForm.name" required
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <template x-for="mirip in kemiripanSesi" :key="mirip.id">
                                <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                    <i class="fas fa-triangle-exclamation"></i> Sudah ada sesi bernama <span
                                        class="font-bold" x-text="mirip.name"></span>.
                                </p>
                            </template>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Jam
                                    Mulai</label>
                                <input type="time" x-model="sesiForm.start_time" required
                                    class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Jam
                                    Selesai</label>
                                <input type="time" x-model="sesiForm.end_time" required
                                    class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>
                        <template x-if="konflikJamSesi.length > 0">
                            <div
                                class="rounded-lg border border-amber-300 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20 p-2.5 space-y-1">
                                <template x-for="(pesan, i) in konflikJamSesi" :key="i">
                                    <p class="text-[11px] font-bold text-amber-700 dark:text-amber-300">
                                        <i class="fas fa-triangle-exclamation"></i> <span x-text="pesan"></span>
                                    </p>
                                </template>
                            </div>
                        </template>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary text-sm flex-1" :disabled="isLoading">
                                <span x-text="sesiForm.id ? 'Simpan Perubahan' : 'Tambah Sesi'"></span>
                            </button>
                            <button type="button" x-show="sesiForm.id" @click="resetSesiForm()"
                                class="btn btn-neutral text-sm">Batal</button>
                        </div>
                    </form>

                    <div>
                        <input type="text" x-model="searchSesi" placeholder="Cari nama sesi..."
                            class="w-full mb-3 rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <div class="space-y-1.5 max-h-[420px] overflow-y-auto pr-1">
                            <template x-for="s in filteredSesis" :key="s.id">
                                <div
                                    class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate">
                                            <span x-text="s.name"></span>
                                            <span class="font-mono font-normal text-gray-400"
                                                x-text="'(' + String(s.start_time).substring(0,5) + '–' + String(s.end_time).substring(0,5) + ')'"></span>
                                        </p>
                                        <p class="text-[10px]"
                                            :class="s.bisa_dihapus ? 'text-gray-400' : 'text-blue-500 font-semibold'"
                                            x-text="s.jumlah_baris_jadwal + ' baris jadwal' + (s.bisa_dihapus ? '' : ' · sedang dipakai')">
                                        </p>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button type="button" @click="editSesi(s)"
                                            class="btn btn-neutral px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-pen-to-square"></i></button>
                                        <button type="button" @click="hapusSesi(s)" :disabled="!s.bisa_dihapus"
                                            :class="s.bisa_dihapus ? '' : 'opacity-40 cursor-not-allowed'"
                                            class="btn-sacred px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-trash-can"></i></button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredSesis.length === 0">
                                <p class="text-xs text-gray-400 italic text-center py-6">Tidak ada sesi ditemukan.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== MAPEL ===================== --}}
            <div x-show="activeSection === 'mapel'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form @submit.prevent="simpanMapel" class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider"
                            x-text="mapelForm.id ? 'Ubah Mata Pelajaran' : 'Tambah Mata Pelajaran Baru'"></h4>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Nama Mata
                                Pelajaran</label>
                            <input type="text" x-model="mapelForm.name" required
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <template x-for="mirip in kemiripanMapel" :key="mirip.id">
                                <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                    <i class="fas fa-triangle-exclamation"></i> Sudah ada mata pelajaran bernama <span
                                        class="font-bold" x-text="mirip.name"></span>.
                                </p>
                            </template>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary text-sm flex-1" :disabled="isLoading">
                                <span x-text="mapelForm.id ? 'Simpan Perubahan' : 'Tambah Mapel'"></span>
                            </button>
                            <button type="button" x-show="mapelForm.id" @click="resetMapelForm()"
                                class="btn btn-neutral text-sm">Batal</button>
                        </div>
                    </form>

                    <div>
                        <input type="text" x-model="searchMapel" placeholder="Cari nama mata pelajaran..."
                            class="w-full mb-3 rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <div class="space-y-1.5 max-h-[420px] overflow-y-auto pr-1">
                            <template x-for="m in filteredMapels" :key="m.id">
                                <div
                                    class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate"
                                            x-text="m.name"></p>
                                        <p class="text-[10px]"
                                            :class="m.bisa_dihapus ? 'text-gray-400' : 'text-blue-500 font-semibold'"
                                            x-text="m.jumlah_baris_jadwal + ' baris jadwal' + (m.bisa_dihapus ? '' : ' · sedang dipakai')">
                                        </p>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button type="button" @click="editMapel(m)"
                                            class="btn btn-neutral px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-pen-to-square"></i></button>
                                        <button type="button" @click="hapusMapel(m)" :disabled="!m.bisa_dihapus"
                                            :class="m.bisa_dihapus ? '' : 'opacity-40 cursor-not-allowed'"
                                            class="btn-sacred px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-trash-can"></i></button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredMapels.length === 0">
                                <p class="text-xs text-gray-400 italic text-center py-6">Tidak ada mata pelajaran
                                    ditemukan.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== PAKET ===================== --}}
            <div x-show="activeSection === 'paket'" x-cloak>
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Paket Pembayaran</h4>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-3">
                    Daftar saja — untuk menambah atau mengubah paket, buka menu "Kelola Paket" di tab Pembayaran.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @forelse ($pakets as $p)
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-gray-800 dark:text-gray-100">{{ $p['nama_paket'] }}</span>
                                <span class="font-mono text-xs font-bold text-purple-600 dark:text-purple-400">Rp {{ number_format($p['harga'], 0, ',', '.') }}</span>
                            </div>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $p['pertemuan'] }} pertemuan ·
                                <span class="{{ $p['jumlah_siswa'] > 0 ? 'text-blue-600 dark:text-blue-400 font-semibold' : '' }}">{{ $p['jumlah_siswa'] }} siswa memakai</span>
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic text-center py-6 col-span-full">Belum ada paket.</p>
                    @endforelse
                </div>
            </div>

            {{-- ===================== KEMAMPUAN ===================== --}}
            <div x-show="activeSection === 'kemampuan'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form @submit.prevent="simpanKemampuan" class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider"
                            x-text="kemampuanForm.id ? 'Ubah Keterangan' : 'Tambah Level Baru (Level ' + (kemampuans.length + 1) + ')'"></h4>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">Keterangan</label>
                            <input type="text" x-model="kemampuanForm.keterangan" required
                                placeholder="Contoh: Pemula, belum lancar membaca"
                                class="mt-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <p class="text-[11px] text-gray-400" x-show="!kemampuanForm.id">
                            <i class="fas fa-circle-info"></i> Nomor level otomatis lanjut dari yang tertinggi — tidak
                            bisa diloncat atau dipilih manual.
                        </p>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary text-sm flex-1" :disabled="isLoading">
                                <span x-text="kemampuanForm.id ? 'Simpan Perubahan' : 'Tambah Level'"></span>
                            </button>
                            <button type="button" x-show="kemampuanForm.id" @click="resetKemampuanForm()"
                                class="btn btn-neutral text-sm">Batal</button>
                        </div>
                    </form>

                    <div>
                        <div class="space-y-1.5 max-h-[420px] overflow-y-auto pr-1">
                            <template x-for="k in kemampuans" :key="k.id">
                                <div
                                    class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-100 truncate">
                                            Level <span x-text="k.level"></span> — <span x-text="k.keterangan"></span>
                                        </p>
                                        <p class="text-[10px]"
                                            :class="k.jumlah_siswa === 0 ? 'text-gray-400' : 'text-blue-500 font-semibold'"
                                            x-text="k.jumlah_siswa + ' siswa memakai'">
                                        </p>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button type="button" @click="editKemampuan(k)"
                                            class="btn btn-neutral px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-pen-to-square"></i></button>
                                        <button type="button" @click="hapusKemampuan(k)" :disabled="!bisaHapusKemampuan(k)"
                                            :class="bisaHapusKemampuan(k) ? '' : 'opacity-40 cursor-not-allowed'"
                                            :title="bisaHapusKemampuan(k) ? '' : 'Hapus level tertinggi dulu, baru turun satu-satu'"
                                            class="btn-sacred px-2.5 py-1 text-[11px] rounded-md"><i
                                                class="fas fa-trash-can"></i></button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="kemampuans.length === 0">
                                <p class="text-xs text-gray-400 italic text-center py-6">Belum ada level kemampuan.
                                </p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== SLOT KOSONG ===================== --}}
            <div x-show="activeSection === 'ketersediaan'" x-cloak>
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Slot Kosong per Hari &amp;
                    Sesi</h4>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-3">
                    Sebelum menambah kelas di tab Jadwal Pelajaran, lihat dulu ruang dan guru mana yang masih bebas di
                    sini.
                </p>
                <input type="text" x-model="searchKetersediaan" placeholder="Cari nama guru atau ruang yang masih bebas..."
                    class="w-full mb-3 rounded-lg border border-gray-300 dark:border-gray-600 p-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">

                <div class="mb-3 flex gap-1.5 overflow-x-auto pb-1 lg:hidden">
                    <template x-for="hari in ketersediaanGrid.hariOrder" :key="hari">
                        <button type="button" @click="activeDayMobile = hari"
                            :class="activeDayMobile === hari ? 'bg-emerald-600 text-white' :
                                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                            class="shrink-0 rounded-lg px-3 py-2 text-xs font-bold transition-colors"
                            x-text="hari"></button>
                    </template>
                </div>

                <div class="overflow-x-auto shadow-md rounded-lg">
                    <table class="min-w-full w-full border-collapse table-fixed">
                        <thead class="bg-gray-100 dark:bg-gray-700/80">
                            <tr>
                                <th
                                    class="border border-gray-300 dark:border-gray-600 p-3 text-center uppercase text-xs tracking-wider font-semibold text-gray-600 dark:text-white w-28 lg:w-36">
                                    Sesi
                                </th>
                                <template x-for="hari in ketersediaanGrid.hariOrder" :key="hari">
                                    <th :class="hari === activeDayMobile ? '' : 'hidden lg:table-cell'"
                                        class="border border-gray-300 dark:border-gray-600 p-3 text-center uppercase text-xs tracking-wider font-semibold text-gray-600 dark:text-white min-w-[220px]"
                                        x-text="hari"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                            <template x-for="row in ketersediaanGrid.rows" :key="row.sesi">
                                <tr class="even:bg-gray-50/50 dark:even:bg-gray-800/60">
                                    <td
                                        class="border border-gray-200 dark:border-gray-600 p-2 text-center align-middle font-semibold text-gray-700 dark:text-white"
                                        x-text="row.sesi"></td>
                                    <template x-for="hari in ketersediaanGrid.hariOrder" :key="hari">
                                        <td :class="hari === activeDayMobile ? '' : 'hidden lg:table-cell'"
                                            class="border border-gray-200 dark:border-gray-600 p-2.5 align-top">
                                            <template x-if="row.byHari[hari]">
                                                <div class="space-y-2">
                                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded inline-block"
                                                        :class="row.byHari[hari].kelas_berjalan > 0 ?
                                                            'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400' :
                                                            'bg-gray-100 dark:bg-gray-800 text-gray-400'"
                                                        x-text="row.byHari[hari].kelas_berjalan + ' kelas'"></span>
                                                    <div>
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Ruang kosong</span>
                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                            <template x-for="r in row.byHari[hari].ruang_kosong" :key="r.name">
                                                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold"
                                                                    :class="r.match ?
                                                                        'bg-amber-300 dark:bg-amber-700 text-amber-950 dark:text-amber-50 ring-2 ring-amber-500' :
                                                                        'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300'"
                                                                    x-text="r.name"></span>
                                                            </template>
                                                            <template x-if="row.byHari[hari].ruang_kosong.length === 0">
                                                                <span
                                                                    class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-red-50 dark:bg-red-950/30 text-red-500">Penuh</span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Guru bebas</span>
                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                            <template x-for="g in row.byHari[hari].guru_kosong" :key="g.name">
                                                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold"
                                                                    :class="g.match ?
                                                                        'bg-amber-300 dark:bg-amber-700 text-amber-950 dark:text-amber-50 ring-2 ring-amber-500' :
                                                                        'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'"
                                                                    x-text="g.name"></span>
                                                            </template>
                                                            <template x-if="row.byHari[hari].guru_kosong.length === 0">
                                                                <span
                                                                    class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-red-50 dark:bg-red-950/30 text-red-500">Semua
                                                                    terpakai</span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
