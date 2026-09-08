<x-admin-layout activeTab="akun_guru">
    <div x-data="akunGuruHandler({
        routes: {
            buatAkunBase: @js(url('admin/akun-guru/guru')),
        },
    })">

        <div x-show="isLoading" x-cloak
            class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-[2px] cursor-wait">
            <div
                class="bg-base-100 rounded-2xl shadow-2xl px-6 py-5 flex items-center gap-3 border">
                <i class="fas fa-circle-notch fa-spin text-success text-xl"></i>
                <p class="text-sm font-bold text-base-content">Sedang diproses...</p>
            </div>
        </div>

        <div
            class="bg-base-100 p-4 md:p-6 rounded-xl shadow-lg border border-base-300">

            <div class="border-b border-base-300 pb-4 mb-6">
                <h3 class="text-lg md:text-xl font-bold text-base-content flex items-center gap-2">
                    <i class="fas fa-user-shield text-success"></i> Akun Guru
                </h3>
                <p class="text-base-content/60 mt-0.5 text-xs md:text-sm">
                    Kelola email dan akun login guru di sini. Untuk menambah/mengubah data guru, ruang, sesi, mata
                    pelajaran, atau siswa, buka menu <span class="font-bold">Workshop</span>.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                <div
                    class="rounded-xl border border-success/40 bg-success/10 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-success">
                        <i class="fas fa-chalkboard-user mr-1"></i>Total Guru
                    </p>
                    <p class="mt-1 text-xl font-black text-success">{{ $ringkasan['guru'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-primary/40 bg-primary/10 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-primary">
                        <i class="fas fa-user-check mr-1"></i>Punya Akun
                    </p>
                    <p class="mt-1 text-xl font-black text-primary">{{ $ringkasan['guru_berakun'] }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-amber-200/70 dark:border-amber-900/50 bg-amber-50/70 dark:bg-amber-950/20 p-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-warning">
                        <i class="fas fa-envelope-circle-check mr-1"></i>Belum Ada Email
                    </p>
                    <p class="mt-1 text-xl font-black text-warning">
                        {{ $ringkasan['guru_tanpa_email'] }}</p>
                </div>
            </div>

            @if ($ringkasan['guru_tanpa_email'] > 0)
                <div
                    class="mb-6 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20 px-4 py-3.5 flex items-start gap-2.5">
                    <i class="fas fa-circle-exclamation text-warning mt-0.5"></i>
                    <p class="text-xs text-warning leading-relaxed">
                        <span class="font-black">{{ $ringkasan['guru_tanpa_email'] }} guru belum punya email.</span>
                        Guru hanya bisa dibuatkan akun login setelah emailnya diisi. Isi lewat tabel di bawah.
                        Guru tanpa akun tetap bisa dijadwalkan seperti biasa.
                    </p>
                </div>
            @endif

            <div class="hidden sm:block overflow-x-auto border border-base-300 rounded-xl">
                <table class="min-w-full divide-y divide-base-300 text-sm">
                    <thead class="bg-base-200 text-left">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-base-content/60 uppercase tracking-wider">Nama</th>
                            <th class="px-4 py-3 text-xs font-bold text-base-content/60 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-xs font-bold text-base-content/60 uppercase tracking-wider">Beban
                                Mengajar</th>
                            <th class="px-4 py-3 text-xs font-bold text-base-content/60 uppercase tracking-wider">Akun</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-base-content/60 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-base-100 divide-y divide-base-300">
                        @forelse ($gurus as $g)
                            <tr class="hover:bg-base-200/70">
                                <td class="px-4 py-3 font-bold text-base-content whitespace-nowrap">
                                    {{ $g['name'] }}</td>
                                <td class="px-4 py-3">
                                    @if ($g['email'])
                                        <span
                                            class="font-mono text-xs text-base-content/80">{{ $g['email'] }}</span>
                                    @else
                                        <span class="text-xs italic text-warning">belum
                                            diisi</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="text-xs font-semibold text-base-content/80">{{ $g['jumlah_slot'] }}
                                        slot</span>
                                    <span class="text-xs text-base-content/60"> · {{ $g['jumlah_baris_jadwal'] }} baris
                                        jadwal</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($g['punya_akun'])
                                        <span
                                            class="text-[11px] font-black px-2 py-1 rounded bg-success/10 text-success">
                                            <i class="fas fa-check"></i> AKTIF
                                        </span>
                                    @else
                                        <span
                                            class="text-[11px] font-black px-2 py-1 rounded bg-base-200 text-base-content/60">BELUM
                                            ADA</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col gap-1.5 sm:flex-row sm:justify-center sm:gap-2">
                                        <button
                                            @click="ubahEmail({{ $g['id'] }}, @js($g['name']), @js($g['email']))"
                                            :disabled="isLoading"
                                            class="btn btn-neutral px-3 py-2 text-xs rounded-md disabled:opacity-50">
                                            <i class="fas fa-envelope"></i> Email
                                        </button>
                                        @if (!$g['punya_akun'])
                                            <button
                                                @click="buatAkun({{ $g['id'] }}, @js($g['name']), @js($g['email']))"
                                                :disabled="isLoading"
                                                class="btn btn-primary px-3 py-2 text-xs rounded-md disabled:opacity-50">
                                                <i class="fas fa-user-plus"></i> Buat Akun
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-base-content/60 italic text-xs">
                                    Belum ada guru terdaftar. Tambahkan lewat menu Workshop.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="sm:hidden divide-y divide-base-300 rounded-xl border border-base-300">
                @forelse ($gurus as $g)
                    <div class="p-4 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-bold text-base-content truncate">{{ $g['name'] }}</p>
                            @if ($g['punya_akun'])
                                <span
                                    class="shrink-0 text-[11px] font-black px-2 py-1 rounded bg-success/10 text-success">
                                    <i class="fas fa-check"></i> AKTIF
                                </span>
                            @else
                                <span
                                    class="shrink-0 text-[11px] font-black px-2 py-1 rounded bg-base-200 text-base-content/60">BELUM
                                    ADA</span>
                            @endif
                        </div>
                        <p class="text-xs">
                            @if ($g['email'])
                                <span class="font-mono text-base-content/80">{{ $g['email'] }}</span>
                            @else
                                <span class="italic text-warning">Email belum diisi</span>
                            @endif
                        </p>
                        <p class="text-xs text-base-content/60">
                            <span class="font-semibold text-base-content/80">{{ $g['jumlah_slot'] }} slot</span>
                            · {{ $g['jumlah_baris_jadwal'] }} baris jadwal
                        </p>
                        <div class="flex flex-col gap-1.5 pt-1">
                            <button
                                @click="ubahEmail({{ $g['id'] }}, @js($g['name']), @js($g['email']))"
                                :disabled="isLoading"
                                class="btn btn-neutral w-full py-2 text-xs rounded-md disabled:opacity-50">
                                <i class="fas fa-envelope"></i> Email
                            </button>
                            @if (!$g['punya_akun'])
                                <button
                                    @click="buatAkun({{ $g['id'] }}, @js($g['name']), @js($g['email']))"
                                    :disabled="isLoading"
                                    class="btn btn-primary w-full py-2 text-xs rounded-md disabled:opacity-50">
                                    <i class="fas fa-user-plus"></i> Buat Akun
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-base-content/60 italic text-xs">
                        Belum ada guru terdaftar. Tambahkan lewat menu Workshop.
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-admin-layout>
