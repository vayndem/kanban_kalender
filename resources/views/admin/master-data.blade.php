<x-admin-layout activeTab="master_data">
    <div x-data="masterDataHandler({
        routes: {
            buatAkunBase: @js(url('admin/master-data/guru')),
        },
    })">

        <div x-show="isLoading" x-cloak
            class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-[2px] cursor-wait">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl px-6 py-5 flex items-center gap-3 border dark:border-gray-700">
                <i class="fas fa-circle-notch fa-spin text-emerald-500 text-xl"></i>
                <p class="text-sm font-bold text-gray-900 dark:text-white">Sedang diproses...</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 md:p-6 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700">

            <div class="border-b border-gray-50 dark:border-gray-700/50 pb-4 mb-6">
                <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-sliders text-emerald-500"></i> Master Data
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mt-0.5 text-xs md:text-sm">
                    Semua bahan untuk menyusun jadwal ada di halaman ini — guru, ruang, sesi, mata pelajaran, paket,
                    beserta slot mana yang masih kosong.
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3 mb-6">
                @php
                    $kartu = [
                        ['Guru', $ringkasan['guru'], 'fa-chalkboard-user', 'emerald'],
                        ['Ruang', $ringkasan['ruang'], 'fa-door-open', 'blue'],
                        ['Sesi', $ringkasan['sesi'], 'fa-clock', 'purple'],
                        ['Mapel', $ringkasan['mapel'], 'fa-book', 'amber'],
                        ['Paket', $ringkasan['paket'], 'fa-box', 'rose'],
                        ['Siswa', $ringkasan['siswa'], 'fa-users', 'cyan'],
                        ['Kelas', $ringkasan['kelas'], 'fa-layer-group', 'slate'],
                    ];
                @endphp
                @foreach ($kartu as [$label, $nilai, $ikon, $warna])
                    <div class="rounded-xl border border-{{ $warna }}-200/70 dark:border-{{ $warna }}-900/50 bg-{{ $warna }}-50/70 dark:bg-{{ $warna }}-950/20 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-{{ $warna }}-700 dark:text-{{ $warna }}-300">
                            <i class="fas {{ $ikon }} mr-1"></i>{{ $label }}
                        </p>
                        <p class="mt-1 text-xl font-black text-{{ $warna }}-800 dark:text-{{ $warna }}-200">{{ $nilai }}</p>
                    </div>
                @endforeach
            </div>

            @if ($ringkasan['guru_tanpa_email'] > 0)
                <div class="mb-6 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20 px-4 py-3.5 flex items-start gap-2.5">
                    <i class="fas fa-circle-exclamation text-amber-500 mt-0.5"></i>
                    <p class="text-xs text-amber-800 dark:text-amber-200 leading-relaxed">
                        <span class="font-black">{{ $ringkasan['guru_tanpa_email'] }} guru belum punya email.</span>
                        Guru hanya bisa dibuatkan akun login setelah emailnya diisi. Isi lewat tabel guru di bawah.
                        Guru tanpa akun tetap bisa dijadwalkan seperti biasa.
                    </p>
                </div>
            @endif

            <div class="mb-8">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">
                    Guru &amp; Akun Login
                    <span class="ml-1 text-emerald-500">{{ $ringkasan['guru_berakun'] }}/{{ $ringkasan['guru'] }} punya akun</span>
                </h4>

                <div class="overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-xl">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Nama</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Beban Mengajar</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Akun</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($gurus as $g)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/20">
                                    <td class="px-4 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ $g['name'] }}</td>
                                    <td class="px-4 py-3">
                                        @if ($g['email'])
                                            <span class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $g['email'] }}</span>
                                        @else
                                            <span class="text-[11px] italic text-amber-600 dark:text-amber-400">belum diisi</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $g['jumlah_slot'] }} slot</span>
                                        <span class="text-[11px] text-gray-400"> · {{ $g['jumlah_baris_jadwal'] }} baris jadwal</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($g['punya_akun'])
                                            <span class="text-[10px] font-black px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                                                <i class="fas fa-check"></i> AKTIF
                                            </span>
                                        @else
                                            <span class="text-[10px] font-black px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">BELUM ADA</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap space-x-1">
                                        <button @click="ubahEmail({{ $g['id'] }}, @js($g['name']), @js($g['email']))"
                                            :disabled="isLoading"
                                            class="btn-neutral px-3 py-1.5 text-[11px] rounded-md disabled:opacity-50">
                                            <i class="fas fa-envelope"></i> Email
                                        </button>
                                        @if (! $g['punya_akun'])
                                            <button @click="buatAkun({{ $g['id'] }}, @js($g['name']), @js($g['email']))"
                                                :disabled="isLoading"
                                                class="btn-primary px-3 py-1.5 text-[11px] rounded-md disabled:opacity-50">
                                                <i class="fas fa-user-plus"></i> Buat Akun
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-gray-400 italic text-xs">Belum ada guru terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-8">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Slot Kosong per Hari &amp; Sesi</h4>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-3">
                    Sebelum menambah kelas, lihat di sini ruang dan guru mana yang masih bebas — tidak perlu membuka halaman jadwal.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach ($ketersediaan as $slot)
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 p-3.5">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-black text-gray-800 dark:text-gray-100">
                                    {{ $slot['hari'] }} · {{ $slot['sesi'] }}
                                </p>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $slot['kelas_berjalan'] > 0 ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-400' }}">
                                    {{ $slot['kelas_berjalan'] }} kelas
                                </span>
                            </div>

                            <div class="space-y-1.5">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Ruang kosong</span>
                                    @if ($slot['ruang_kosong']->isEmpty())
                                        <p class="text-[11px] font-bold text-red-500">Penuh — tidak ada ruang tersisa</p>
                                    @else
                                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                            {{ $slot['ruang_kosong']->implode(', ') }}
                                        </p>
                                    @endif
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Guru bebas</span>
                                    @if ($slot['guru_kosong']->isEmpty())
                                        <p class="text-[11px] font-bold text-red-500">Semua guru terpakai</p>
                                    @else
                                        <p class="text-[11px] text-gray-600 dark:text-gray-300">
                                            {{ $slot['guru_kosong']->implode(', ') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Ruang</h4>
                    <div class="space-y-2">
                        @forelse ($ruangs as $r)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3 flex items-center justify-between">
                                <span class="font-bold text-sm">{{ $r['name'] }}</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                    terpakai {{ $r['slot_terpakai'] }}/{{ $r['slot_total'] }} slot ·
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $r['slot_kosong'] }} kosong</span>
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic py-4 text-center">Belum ada ruang.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Sesi</h4>
                    <div class="space-y-2">
                        @forelse ($sesis as $s)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3 flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-sm">{{ $s['name'] }}</span>
                                    <span class="ml-2 text-[11px] font-mono text-gray-500 dark:text-gray-400">
                                        {{ \Illuminate\Support\Str::of($s['start_time'])->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($s['end_time'])->substr(0, 5) }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $s['jumlah_baris_jadwal'] }} baris jadwal</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic py-4 text-center">Belum ada sesi.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Mata Pelajaran</h4>
                    <div class="space-y-2">
                        @forelse ($mapels as $m)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3 flex items-center justify-between">
                                <span class="font-bold text-sm">{{ $m['name'] }}</span>
                                <span class="text-[11px] {{ $m['bisa_dihapus'] ? 'text-gray-400' : 'text-blue-600 dark:text-blue-400 font-semibold' }}">
                                    {{ $m['jumlah_baris_jadwal'] }} baris jadwal
                                    @if (! $m['bisa_dihapus']) · sedang dipakai @endif
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic py-4 text-center">Belum ada mata pelajaran.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Paket</h4>
                    <div class="space-y-2">
                        @forelse ($pakets as $p)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm">{{ $p['nama_paket'] }}</span>
                                    <span class="font-mono text-xs font-bold text-purple-600 dark:text-purple-400">
                                        Rp {{ number_format($p['harga'], 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $p['pertemuan'] }} pertemuan ·
                                    <span class="{{ $p['jumlah_siswa'] > 0 ? 'text-blue-600 dark:text-blue-400 font-semibold' : '' }}">
                                        {{ $p['jumlah_siswa'] }} siswa memakai
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic py-4 text-center">Belum ada paket.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
