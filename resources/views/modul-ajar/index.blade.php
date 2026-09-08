<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Modul Ajar — {{ config('app.name', 'E-Ling Course') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="app-canvas min-h-screen text-base-content">

        <x-portal-nav icon="fa-book-open-reader" title="Modul Ajar" wide
            :subtitle="$isAdmin ? 'Semua kelas' : 'Kelas ' . ($guru->name ?? '-') . ' saja'">
            <a href="{{ route('absen.index') }}" class="btn btn-ghost btn-sm text-xs">
                <i class="fas fa-clipboard-user"></i><span class="hidden sm:inline"> Absen</span>
            </a>
            <a href="{{ $isAdmin ? route('dashboard') : route('guru.jadwal') }}" class="btn btn-ghost btn-sm text-xs">
                <i class="fas fa-arrow-left"></i><span class="hidden sm:inline"> {{ $isAdmin ? 'Dashboard' : 'Jadwal Saya' }}</span>
            </a>
        </x-portal-nav>

        <main class="py-6 sm:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="modulAjarHandler({
                isAdmin: @js($isAdmin),
                initialKelasList: @js($kelasList),
                routes: {
                    headerBase: @js(url('modul-ajar/header')),
                    kelolaDetailBase: @js(url('modul-ajar')),
                    detailBase: @js(url('modul-ajar/detail')),
                },
            })">

                <div x-show="isLoading" x-cloak
                    class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 backdrop-blur-[2px] cursor-wait">
                    <div class="bg-base-100 rounded-2xl shadow-2xl px-6 py-5 flex items-center gap-3 border">
                        <i class="fas fa-circle-notch fa-spin text-success text-xl"></i>
                        <p class="text-sm font-bold text-base-content">Sedang diproses...</p>
                    </div>
                </div>

                <div class="bg-base-100 p-4 md:p-6 rounded-xl shadow-lg border border-base-300 mb-6">
                    <h3 class="text-lg md:text-xl font-bold text-base-content flex items-center gap-2">
                        <i class="fas fa-book-open-reader text-success"></i> Modul Ajar
                    </h3>
                    <p class="text-base-content/60 mt-0.5 text-xs md:text-sm">
                        Klik kelas untuk mengisi tujuan pembelajaran dan rincian materi. Kotak abu-abu berarti sudah ada modul ajar.
                        Untuk mulai mengajar, tandai tidak bisa hadir, atau menilai anak-anak, buka menu
                        <a href="{{ route('absen.index') }}" class="font-bold text-success hover:underline">Absen</a>.
                    </p>
                </div>

                <div class="overflow-x-auto shadow-md rounded-lg">
                    <table class="min-w-full w-full border-collapse table-fixed">
                        <thead class="bg-base-200">
                            <tr>
                                <th class="sticky left-0 z-10 border border-base-300 bg-base-200 p-3 text-center uppercase text-xs tracking-wider font-semibold text-base-content/70 w-24 lg:w-32">
                                    Sesi
                                </th>
                                @foreach ($haris as $hari)
                                    <th class="border border-base-300 p-3 text-center uppercase text-xs tracking-wider font-semibold text-base-content/70 min-w-[200px]">
                                        {{ $hari->name }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-base-100">
                            @foreach ($sesis as $sesi)
                                <tr class="even:bg-base-200/50">
                                    <td class="sticky left-0 z-10 border border-base-300 bg-base-100 p-2 text-center align-middle font-semibold text-base-content/80">
                                        {{ $sesi->name }}
                                        <span class="block text-xs text-base-content/60 font-normal">
                                            {{ \Illuminate\Support\Str::of($sesi->start_time)->substr(0, 5) }} –
                                            {{ \Illuminate\Support\Str::of($sesi->end_time)->substr(0, 5) }}
                                        </span>
                                    </td>

                                    @foreach ($haris as $hari)
                                        <td class="border border-base-300 p-2 align-top h-40">
                                            <template x-for="kelas in kelasDi({{ $hari->id }}, {{ $sesi->id }})" :key="kelas.kode_kelas">
                                                <div @click="openKelas(kelas)"
                                                    class="group relative p-2.5 mb-2 rounded-lg shadow-sm border-l-4 text-sm cursor-pointer transition-all duration-200 ease-out hover:shadow-xl hover:-translate-y-0.5"
                                                    :class="kelas.ada_header ? 'border-success/40 bg-base-200' : 'border-success/40 bg-base-100'">

                                                    <span x-show="kelas.ada_header"
                                                        class="absolute top-1 right-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-success px-1 text-[11px] font-black text-white"
                                                        x-text="kelas.jumlah_detail"></span>

                                                    <strong class="block font-bold text-base-content truncate" x-text="kelas.mapel"></strong>
                                                    <span class="block text-base-content/70 mt-1" x-text="kelas.guru"></span>
                                                    <span class="block text-base-content/60 text-xs mt-1" x-text="'Ruang: ' + kelas.ruang"></span>
                                                </div>
                                            </template>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Modal isi modul ajar --}}
                <template x-if="selectedKelas">
                    <div x-show="selectedKelas" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs" @click="closeModal()">
                        <div @click.stop x-transition class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-base-300 bg-base-100 shadow-2xl">
                            <div class="modal-header-brand sticky top-0 z-10">
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-white/75" x-text="selectedKelas.guru + ' · ' + selectedKelas.ruang"></p>
                                    <h3 class="mt-1 text-xl font-black truncate" x-text="selectedKelas.mapel"></h3>
                                </div>
                                <button type="button" @click="closeModal()" class="shrink-0 rounded-full bg-white/15 px-3 py-2 text-sm font-bold hover:bg-white/25">Tutup</button>
                            </div>

                            <div class="p-5 space-y-5">
                                {{-- Header --}}
                                <template x-if="bisaUbahHeader">
                                    <form @submit.prevent="simpanHeader" class="space-y-3">
                                        <h4 class="text-xs font-bold text-base-content/60 uppercase tracking-wider">Modul Ajar</h4>
                                        <div>
                                            <label class="block text-xs font-semibold text-base-content/60">Tujuan Pembelajaran</label>
                                            <textarea x-model="headerForm.tujuan_pembelajaran" required rows="2"
                                                class="mt-1 w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-base-content/60">Kompetensi Awal</label>
                                            <textarea x-model="headerForm.kompetensi_awal" required rows="2"
                                                class="mt-1 w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-base-content/60">Model/Metode Pembelajaran</label>
                                            <input type="text" x-model="headerForm.model_pembelajaran" required
                                                class="mt-1 w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-base-content/60">Sarana/Media</label>
                                            <textarea x-model="headerForm.sarana_media" required rows="2"
                                                class="mt-1 w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-sm focus:ring-2 focus:ring-primary focus:outline-hidden"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary text-sm w-full" :disabled="isLoading">
                                            <span x-text="selectedKelas.ada_header ? 'Simpan Perubahan' : 'Simpan Modul Ajar'"></span>
                                        </button>
                                    </form>
                                </template>
                                <template x-if="!bisaUbahHeader">
                                    <div class="space-y-2 rounded-lg border border-base-300 p-3">
                                        <h4 class="text-xs font-bold text-base-content/60 uppercase tracking-wider">Modul Ajar</h4>
                                        <p class="text-xs text-base-content/70"><span class="font-bold">Tujuan:</span> <span x-text="headerForm.tujuan_pembelajaran"></span></p>
                                        <p class="text-xs text-base-content/70"><span class="font-bold">Kompetensi Awal:</span> <span x-text="headerForm.kompetensi_awal"></span></p>
                                        <p class="text-xs text-base-content/70"><span class="font-bold">Model/Metode:</span> <span x-text="headerForm.model_pembelajaran"></span></p>
                                        <p class="text-xs text-base-content/70"><span class="font-bold">Sarana/Media:</span> <span x-text="headerForm.sarana_media"></span></p>
                                        <p class="text-xs italic text-base-content/60">Sudah diisi — hanya admin yang bisa mengubah.</p>
                                    </div>
                                </template>

                                {{-- Detail --}}
                                <template x-if="selectedKelas.ada_header">
                                    <div class="space-y-3 border-t border-base-300 pt-4">
                                        <h4 class="text-xs font-bold text-base-content/60 uppercase tracking-wider">Rincian Materi</h4>

                                        <div class="space-y-2">
                                            <template x-for="d in (selectedKelas.modul_ajar.details || [])" :key="d.id">
                                                <div class="rounded-lg border p-2.5"
                                                    :class="d.diajarkan_oleh_guru_id ? 'border-success/40 bg-success/10' : 'border-base-300'">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-bold text-base-content">
                                                                <i x-show="d.diajarkan_oleh_guru_id" class="fas fa-circle-check text-success mr-1"></i>
                                                                <span x-text="d.materi"></span>
                                                            </p>
                                                            <p x-show="d.sub_materi" class="text-xs text-base-content/60" x-text="d.sub_materi"></p>
                                                            <p x-show="d.diajarkan_oleh_guru_id" class="text-xs text-success mt-1">
                                                                Diajarkan oleh <span x-text="d.diajarkan_oleh_guru?.name"></span> pada <span x-text="d.tanggal_diajarkan"></span>
                                                            </p>
                                                        </div>
                                                        <div x-show="isAdmin" class="flex gap-1 shrink-0">
                                                            <button type="button" @click="editDetail(d)" class="btn btn-neutral px-2 py-1 text-xs rounded-md"><i class="fas fa-pen-to-square"></i></button>
                                                            <button type="button" @click="hapusDetail(d)" class="btn btn-sacred px-2 py-1 text-xs rounded-md"><i class="fas fa-trash-can"></i></button>
                                                        </div>
                                                    </div>
                                                    <dl class="mt-1.5 grid grid-cols-1 gap-1 text-xs text-base-content/60">
                                                        <p x-show="d.cara_mengajar"><span class="font-semibold">Cara mengajar:</span> <span x-text="d.cara_mengajar"></span></p>
                                                        <p x-show="d.tugas"><span class="font-semibold">Tugas:</span> <span x-text="d.tugas"></span></p>
                                                        <p x-show="d.tujuan"><span class="font-semibold">Tujuan:</span> <span x-text="d.tujuan"></span></p>
                                                        <p x-show="d.hasil_akhir_pembelajaran"><span class="font-semibold">Hasil akhir:</span> <span x-text="d.hasil_akhir_pembelajaran"></span></p>
                                                        <p x-show="d.keterangan"><span class="font-semibold">Keterangan:</span> <span x-text="d.keterangan"></span></p>
                                                    </dl>
                                                </div>
                                            </template>
                                            <template x-if="(selectedKelas.modul_ajar.details || []).length === 0">
                                                <p class="text-xs text-base-content/60 italic text-center py-3">Belum ada rincian materi.</p>
                                            </template>
                                        </div>

                                        <form @submit.prevent="simpanDetail" class="space-y-2 rounded-lg border border-dashed border-base-300 p-3">
                                            <h5 class="text-xs font-bold text-base-content/60 uppercase tracking-wider" x-text="editingDetailId ? 'Ubah Materi' : 'Tambah Materi'"></h5>
                                            <input type="text" x-model="detailForm.materi" required placeholder="Materi"
                                                class="w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden">
                                            <input type="text" x-model="detailForm.sub_materi" placeholder="Sub materi (opsional)"
                                                class="w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden">
                                            <textarea x-model="detailForm.cara_mengajar" placeholder="Cara mengajar (opsional)" rows="2"
                                                class="w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden"></textarea>
                                            <textarea x-model="detailForm.tugas" placeholder="Tugas (opsional)" rows="2"
                                                class="w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden"></textarea>
                                            <textarea x-model="detailForm.tujuan" placeholder="Tujuan (opsional)" rows="2"
                                                class="w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden"></textarea>
                                            <textarea x-model="detailForm.hasil_akhir_pembelajaran" placeholder="Hasil akhir pembelajaran (opsional)" rows="2"
                                                class="w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden"></textarea>
                                            <textarea x-model="detailForm.keterangan" placeholder="Keterangan (opsional)" rows="2"
                                                class="w-full rounded-lg border border-base-300 p-2 bg-base-100 text-base-content text-xs focus:ring-2 focus:ring-primary focus:outline-hidden"></textarea>
                                            <div class="flex gap-2">
                                                <button type="submit" class="btn btn-primary text-xs flex-1" :disabled="isLoading">
                                                    <span x-text="editingDetailId ? 'Simpan Perubahan' : 'Tambah Materi'"></span>
                                                </button>
                                                <button type="button" x-show="editingDetailId" @click="resetDetailForm()" class="btn btn-neutral text-xs">Batal</button>
                                            </div>
                                        </form>
                                    </div>
                                </template>
                                <template x-if="!selectedKelas.ada_header">
                                    <p class="text-xs text-base-content/60 italic text-center py-2">Isi modul ajar dulu sebelum menambah rincian materi.</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </main>
    </div>
</body>

</html>
