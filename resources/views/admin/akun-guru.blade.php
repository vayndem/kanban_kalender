<x-admin-layout activeTab="akun_guru">
    <div x-data="akunGuruHandler({
        routes: {
            buatAkunBase: @js(url('admin/akun-guru/guru')),
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
                    <i class="fas fa-user-shield text-emerald-500"></i> Akun Guru
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mt-0.5 text-xs md:text-sm">
                    Kelola email dan akun login guru di sini. Untuk menambah/mengubah data guru, ruang, sesi, mata
                    pelajaran, atau siswa, buka menu <span class="font-bold">Workshop</span>.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-6">
                <div
                    class="rounded-xl border border-emerald-200/70 dark:border-emerald-900/50 bg-emerald-50/70 dark:bg-emerald-950/20 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                        <i class="fas fa-chalkboard-user mr-1"></i>Total Guru
                    </p>
                    <p class="mt-1 text-xl font-black text-emerald-800 dark:text-emerald-200">{{ $ringkasan['guru'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-blue-200/70 dark:border-blue-900/50 bg-blue-50/70 dark:bg-blue-950/20 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">
                        <i class="fas fa-user-check mr-1"></i>Punya Akun
                    </p>
                    <p class="mt-1 text-xl font-black text-blue-800 dark:text-blue-200">{{ $ringkasan['guru_berakun'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-amber-200/70 dark:border-amber-900/50 bg-amber-50/70 dark:bg-amber-950/20 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300">
                        <i class="fas fa-envelope-circle-check mr-1"></i>Belum Ada Email
                    </p>
                    <p class="mt-1 text-xl font-black text-amber-800 dark:text-amber-200">
                        {{ $ringkasan['guru_tanpa_email'] }}</p>
                </div>
            </div>

            @if ($ringkasan['guru_tanpa_email'] > 0)
                <div
                    class="mb-6 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20 px-4 py-3.5 flex items-start gap-2.5">
                    <i class="fas fa-circle-exclamation text-amber-500 mt-0.5"></i>
                    <p class="text-xs text-amber-800 dark:text-amber-200 leading-relaxed">
                        <span class="font-black">{{ $ringkasan['guru_tanpa_email'] }} guru belum punya email.</span>
                        Guru hanya bisa dibuatkan akun login setelah emailnya diisi. Isi lewat tabel di bawah.
                        Guru tanpa akun tetap bisa dijadwalkan seperti biasa.
                    </p>
                </div>
            @endif

            <div class="overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Nama</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Beban
                                Mengajar</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Akun</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($gurus as $g)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/20">
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ $g['name'] }}</td>
                                <td class="px-4 py-3">
                                    @if ($g['email'])
                                        <span
                                            class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $g['email'] }}</span>
                                    @else
                                        <span class="text-[11px] italic text-amber-600 dark:text-amber-400">belum
                                            diisi</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $g['jumlah_slot'] }}
                                        slot</span>
                                    <span class="text-[11px] text-gray-400"> · {{ $g['jumlah_baris_jadwal'] }} baris
                                        jadwal</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($g['punya_akun'])
                                        <span
                                            class="text-[10px] font-black px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                            <i class="fas fa-check"></i> AKTIF
                                        </span>
                                    @else
                                        <span
                                            class="text-[10px] font-black px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">BELUM
                                            ADA</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap space-x-1">
                                    <button
                                        @click="ubahEmail({{ $g['id'] }}, @js($g['name']), @js($g['email']))"
                                        :disabled="isLoading"
                                        class="btn-neutral px-3 py-1.5 text-[11px] rounded-md disabled:opacity-50">
                                        <i class="fas fa-envelope"></i> Email
                                    </button>
                                    @if (!$g['punya_akun'])
                                        <button
                                            @click="buatAkun({{ $g['id'] }}, @js($g['name']), @js($g['email']))"
                                            :disabled="isLoading"
                                            class="btn-primary px-3 py-1.5 text-[11px] rounded-md disabled:opacity-50">
                                            <i class="fas fa-user-plus"></i> Buat Akun
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-400 italic text-xs">
                                    Belum ada guru terdaftar. Tambahkan lewat menu Workshop.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-admin-layout>
