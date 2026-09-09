<x-admin-layout activeTab="payroll">
    <div x-data="payrollHandler({
        initialRingkasan: @js($ringkasan),
        initialRiwayat: @js($riwayat),
        routes: {
            tarifBase: @js(url('admin/payroll/guru')),
            jalankanBase: @js(url('admin/payroll/guru')),
            jalankanSemua: @js(route('admin.payroll.jalankanSemua')),
            strukBase: @js(url('admin/payroll/struk')),
        },
    })">

        <div x-show="isLoading" x-cloak
            class="fixed inset-0 z-[200] flex cursor-wait items-center justify-center bg-black/40 backdrop-blur-xs">
            <div class="app-card flex items-center gap-3 px-6 py-5">
                <i class="fas fa-circle-notch fa-spin text-xl text-primary"></i>
                <p class="text-sm font-bold text-base-content">Sedang diproses...</p>
            </div>
        </div>

        <div class="app-card app-card-pad">
            <div class="mb-6 flex flex-col gap-4 border-b border-base-300 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="brand-chip h-11 w-11 text-lg"><i class="fas fa-money-check-dollar"></i></span>
                    <div>
                        <h3 class="text-xl font-black tracking-tight text-base-content sm:text-2xl">Payroll Guru</h3>
                        <p class="mt-0.5 text-xs text-base-content/70 sm:text-sm">
                            Gaji bawaan dibayar penuh setiap kali dijalankan, ditambah gaji per kehadiran yang belum
                            pernah digaji.
                        </p>
                    </div>
                </div>

                <button type="button" @click="jalankanSemua()" :disabled="isLoading"
                    class="btn btn-sacred btn-sm w-full shrink-0 sm:w-auto">
                    <i class="fas fa-circle-check"></i> Siap Semua
                </button>
            </div>

            <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="app-stat">
                    <div class="app-stat-value" x-text="daftar.length"></div>
                    <div class="app-stat-label">Guru Ditampilkan</div>
                </div>
                <div class="app-stat">
                    <div class="app-stat-value text-warning"
                        x-text="ringkasan.reduce((j, g) => j + g.kehadiran_belum_dibayar, 0)"></div>
                    <div class="app-stat-label">Kehadiran Belum Digaji</div>
                </div>
                <div class="app-stat">
                    <div class="app-stat-value text-primary" x-text="rupiah(totalPerkiraan)"></div>
                    <div class="app-stat-label">Perkiraan Total Berjalan</div>
                </div>
            </div>

            <label class="relative mb-4 block w-full sm:max-w-xs">
                <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-base-content/60"></i>
                <input type="search" x-model="cari" placeholder="Cari nama guru..." class="app-input pl-10">
            </label>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-3">
                <template x-for="guru in daftar" :key="guru.id">
                    <div class="app-card-hover overflow-hidden">
                        <div class="flex items-start justify-between gap-3 border-b border-base-300 bg-base-200/60 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-black text-base-content" x-text="guru.nama"></p>
                                <p class="text-xs text-base-content/70">
                                    <span x-text="guru.kehadiran_belum_dibayar"></span> kehadiran belum digaji
                                </p>
                            </div>
                            <span class="badge badge-primary badge-sm shrink-0 font-bold"
                                x-text="rupiah(guru.perkiraan_total)"></span>
                        </div>

                        <div class="space-y-3 p-4">
                            <template x-if="editId !== guru.id">
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-base-content/70">Gaji bawaan</span>
                                        <span class="font-bold text-base-content" x-text="rupiah(guru.gaji_bawaan)"></span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-base-content/70">Per kehadiran</span>
                                        <span class="font-bold text-base-content" x-text="rupiah(guru.gaji_per_kehadiran)"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="editId === guru.id">
                                <div class="space-y-3">
                                    <div>
                                        <label class="app-label">Gaji Bawaan</label>
                                        <input type="number" min="0" x-model.number="formTarif.gaji_bawaan"
                                            class="app-input">
                                    </div>
                                    <div>
                                        <label class="app-label">Gaji Per Kehadiran</label>
                                        <input type="number" min="0" x-model.number="formTarif.gaji_per_kehadiran"
                                            class="app-input">
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" @click="simpanTarif(guru)" class="btn btn-primary btn-sm flex-1">
                                            <i class="fas fa-floppy-disk"></i> Simpan
                                        </button>
                                        <button type="button" @click="batalEdit()" class="btn btn-neutral btn-sm">Batal</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="editId !== guru.id">
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <button type="button" @click="bukaEdit(guru)" class="btn btn-accent btn-sm flex-1">
                                        <i class="fas fa-pen"></i> Ubah Tarif
                                    </button>
                                    <button type="button" @click="jalankan(guru)" :disabled="isLoading"
                                        class="btn btn-sacred btn-sm flex-1">
                                        <i class="fas fa-circle-check"></i> Siap Lakukan
                                    </button>
                                </div>
                            </template>

                            <template x-if="guru.struk_terakhir">
                                <button type="button" @click="bukaStruk(guru.struk_terakhir.id)"
                                    class="w-full rounded-field border border-base-300 bg-base-200/60 px-3 py-2 text-left text-xs transition hover:border-primary">
                                    <span class="font-bold text-base-content">Struk terakhir:</span>
                                    <span class="text-base-content/70"
                                        x-text="rupiah(guru.struk_terakhir.total) + ' · ' + guru.struk_terakhir.dijalankan_pada"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <template x-if="daftar.length === 0">
                <div class="app-empty mt-4">
                    <div class="app-empty-icon"><i class="fas fa-user-slash"></i></div>
                    <p class="app-empty-title">Tidak ada guru yang cocok.</p>
                    <p class="app-empty-text">Ubah kata pencarian, atau tambahkan guru lewat menu Workshop.</p>
                </div>
            </template>
        </div>

        <div class="app-card app-card-pad mt-6">
            <h4 class="app-section-head">Riwayat Penggajian</h4>

            <template x-if="riwayat.length === 0">
                <div class="app-empty">
                    <div class="app-empty-icon"><i class="fas fa-receipt"></i></div>
                    <p class="app-empty-title">Belum ada struk yang diterbitkan.</p>
                </div>
            </template>

            <template x-if="riwayat.length > 0">
                <div class="app-table-wrap">
                    <table class="table">
                        <thead>
                            <tr class="bg-base-200 text-base-content/70">
                                <th>Guru</th>
                                <th class="text-center">Kehadiran</th>
                                <th class="text-right">Total</th>
                                <th>Dijalankan</th>
                                <th class="text-center">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="baris in riwayat" :key="baris.id">
                                <tr :class="baris.dibatalkan ? 'opacity-60' : ''">
                                    <td class="font-bold" x-text="baris.guru"></td>
                                    <td class="text-center" x-text="baris.jumlah_kehadiran"></td>
                                    <td class="text-right font-bold" x-text="rupiah(baris.total)"></td>
                                    <td class="text-xs text-base-content/70" x-text="baris.dijalankan_pada"></td>
                                    <td class="text-center">
                                        <span class="badge badge-sm font-bold"
                                            :class="baris.dibatalkan ? 'badge-ghost' : 'badge-success'"
                                            x-text="baris.dibatalkan ? 'Dibatalkan' : 'Aktif'"></span>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" @click="bukaStruk(baris.id)"
                                            class="icon-action-primary" title="Lihat struk">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        <template x-if="strukTerbuka">
            <div x-show="strukTerbuka" x-transition.opacity @keydown.escape.window="tutupStruk()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
                @click="tutupStruk()">
                <div @click.stop x-transition class="responsive-modal-panel max-w-lg">
                    <div class="modal-header-brand">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-white/75">Struk Penggajian</p>
                            <h3 class="mt-1 truncate text-xl font-black" x-text="strukTerbuka.guru"></h3>
                            <p class="mt-1 text-sm opacity-90" x-text="strukTerbuka.dijalankan_pada"></p>
                        </div>
                        <button type="button" @click="tutupStruk()" aria-label="Tutup"
                            class="btn btn-circle btn-ghost btn-sm shrink-0 bg-white/15 text-current hover:bg-white/25">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto p-5">
                        <template x-if="strukTerbuka.dibatalkan_pada">
                            <div class="alert alert-error mb-4 text-sm">
                                <i class="fas fa-ban"></i>
                                <span>
                                    Dibatalkan <span x-text="strukTerbuka.dibatalkan_pada"></span>
                                    <template x-if="strukTerbuka.alasan_batal">
                                        <span> — <span x-text="strukTerbuka.alasan_batal"></span></span>
                                    </template>
                                </span>
                            </div>
                        </template>

                        <div class="space-y-2 rounded-box border border-base-300 p-4 text-sm">
                            <div class="flex justify-between gap-2">
                                <span class="text-base-content/70">Gaji bawaan</span>
                                <span class="font-bold" x-text="rupiah(strukTerbuka.gaji_bawaan)"></span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-base-content/70">
                                    <span x-text="strukTerbuka.jumlah_kehadiran"></span> kehadiran ×
                                    <span x-text="rupiah(strukTerbuka.gaji_per_kehadiran)"></span>
                                </span>
                                <span class="font-bold"
                                    x-text="rupiah(strukTerbuka.jumlah_kehadiran * strukTerbuka.gaji_per_kehadiran)"></span>
                            </div>
                            <div class="mt-2 flex justify-between gap-2 border-t border-base-300 pt-2">
                                <span class="font-black text-base-content">Total Dibayar</span>
                                <span class="font-black text-primary" x-text="rupiah(strukTerbuka.total)"></span>
                            </div>
                        </div>

                        <p class="mb-2 mt-5 text-xs font-black uppercase tracking-wider text-base-content/60">
                            Log Kelas Yang Diajar (<span x-text="strukTerbuka.log_kelas.length"></span>)
                        </p>

                        <template x-if="strukTerbuka.log_kelas.length === 0">
                            <p class="p-2 text-xs italic text-base-content/60">Tidak ada kehadiran pada struk ini —
                                hanya gaji bawaan.</p>
                        </template>

                        <div class="space-y-1.5">
                            <template x-for="(item, idx) in strukTerbuka.log_kelas" :key="idx">
                                <div class="flex items-center gap-2 rounded-field bg-base-200/70 px-2.5 py-1.5 text-xs">
                                    <i class="fas fa-chalkboard text-[11px] text-primary"></i>
                                    <span class="min-w-0 flex-1 truncate font-bold text-base-content" x-text="item.materi"></span>
                                    <span class="ml-auto shrink-0 text-base-content/60" x-text="item.tanggal"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-base-300 bg-base-200 p-4">
                        <a :href="`{{ url('penggajian') }}/${strukTerbuka.id}/struk-pdf`"
                            class="btn btn-export btn-sm">
                            <i class="fas fa-file-pdf"></i> Unduh Struk
                        </a>
                        <template x-if="!strukTerbuka.dibatalkan_pada">
                            <button type="button" @click="batalkanStruk(strukTerbuka)" class="btn btn-sacred btn-sm">
                                <i class="fas fa-ban"></i> Batalkan Struk
                            </button>
                        </template>
                        <button type="button" @click="tutupStruk()" class="btn btn-neutral btn-sm">Tutup</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
