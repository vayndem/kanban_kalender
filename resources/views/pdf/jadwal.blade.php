<!DOCTYPE html>
<html>

<head>
    <title>Jadwal Pelajaran & Catatan</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 10mm;
        }

        body {
            font-family: sans-serif;
            font-size: 9pt;
            /* Font diperkecil agar muat */
            color: #333;
        }

        /* --- HALAMAN 1: JADWAL COMPACT --- */
        .header-container {
            text-align: center;
            margin-bottom: 10px;
        }

        h2 {
            margin: 0;
            text-transform: uppercase;
            font-size: 14pt;
        }

        .search-info {
            font-size: 9pt;
            color: #666;
            font-style: italic;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Penting agar kolom rata */
        }

        th,
        td {
            border: 1px solid #666;
            padding: 3px;
            /* Padding diperkecil */
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #eee;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            height: 25px;
        }

        .sesi-col {
            width: 60px;
            /* Dipersempit */
            text-align: center;
            background-color: #f8f8f8;
            font-weight: bold;
            font-size: 8pt;
        }

        /* Card Jadwal Compact */
        .card {
            border: 1px solid #ddd;
            padding: 2px 4px;
            margin-bottom: 3px;
            border-radius: 2px;
            background-color: #fff;
            page-break-inside: avoid;
        }

        .mapel {
            font-weight: bold;
            font-size: 8pt;
            /* Font isi diperkecil */
            color: #000;
            margin-bottom: 1px;
        }

        .guru,
        .ruang {
            font-size: 7pt;
            color: #444;
            display: block;
            line-height: 1.1;
        }

        .siswa-list {
            margin-top: 2px;
            font-size: 7pt;
            padding-left: 10px;
            margin-bottom: 0;
            line-height: 1.1;
        }

        .tanda-indicator {
            color: #d97706;
            /* Warna oranye */
            font-weight: bold;
            text-decoration: underline;
        }

        /* --- PEMISAH HALAMAN --- */
        .page-break {
            page-break-before: always;
        }

        /* --- HALAMAN 2: CATATAN SISWA --- */
        .notes-container {
            width: 100%;
        }

        .student-note-card {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            background-color: #fff;
            border-radius: 5px;
            page-break-inside: avoid;
        }

        .student-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .student-avatar {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: #dbeafe;
            /* Blue 100 */
            color: #1e40af;
            /* Blue 800 */
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
            font-size: 12pt;
        }

        .student-info h3 {
            margin: 0;
            font-size: 11pt;
            color: #111;
        }

        .student-info span {
            font-size: 9pt;
            color: #666;
        }

        .note-item {
            background-color: #fffbeb;
            /* Yellow 50 (mirip gambar) */
            border-left: 4px solid #f59e0b;
            /* Yellow 500 */
            padding: 8px;
            margin-bottom: 5px;
            font-size: 9pt;
        }

        .note-content {
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 2px;
        }

        .note-date {
            font-size: 8pt;
            color: #777;
        }

        .no-notes {
            text-align: center;
            color: #888;
            font-style: italic;
            margin-top: 50px;
        }
    </style>
</head>

<body>

    {{-- HALAMAN 1: MATRIX JADWAL --}}
    <div class="header-container">
        <h2>Jadwal Pelajaran</h2>
        @if ($searchQuery)
            <div class="search-info">Filter: "{{ $searchQuery }}"</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 60px;">Waktu</th>
                @foreach ($haris as $hari)
                    <th>{{ $hari->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($sesis as $sesi)
                <tr>
                    <td class="sesi-col">
                        {{ $sesi->name }}<br>
                        <span style="font-weight: normal; font-size: 7pt;">
                            {{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}
                        </span>
                    </td>

                    @foreach ($haris as $hari)
                        <td>
                            @if (isset($jadwals[$hari->id][$sesi->id]))
                                @foreach ($jadwals[$hari->id][$sesi->id] as $groupedClass)
                                    <div class="card"
                                        style="border-left: 3px solid {{ $groupedClass['mapel']->border_color ?? '#000' }};">
                                        <div class="mapel">{{ $groupedClass['mapel']->name }}</div>
                                        <span class="guru">{{ $groupedClass['guru']->name }}</span>
                                        <span class="ruang">R: {{ $groupedClass['ruang']->name }}</span>

                                        <ol class="siswa-list">
                                            @foreach ($groupedClass['siswa_list'] as $siswa)
                                                <li
                                                    class="{{ $siswa->tandas && $siswa->tandas->count() > 0 ? 'tanda-indicator' : '' }}">
                                                    {{ $siswa->name }}
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @endforeach
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- HALAMAN 2: DETAIL CATATAN --}}
    <div class="page-break"></div>

    <div class="header-container">
        <h2>Detail Catatan Siswa</h2>
        <div class="search-info">Daftar siswa yang memiliki tanda/catatan khusus</div>
    </div>

    <div class="notes-container">
        @forelse($studentsWithNotes as $siswa)
            <div class="student-note-card">
                <table style="width: 100%; border: none; margin-bottom: 5px;">
                    <tr style="border: none;">
                        <td style="width: 40px; border: none; padding: 0;">
                            <div class="student-avatar">
                                {{ substr($siswa->name, 0, 1) }}
                            </div>
                        </td>
                        <td style="border: none; padding: 0;">
                            <div class="student-info">
                                <h3>{{ $siswa->name }}</h3>
                                <span>{{ $siswa->kelas ?? 'Siswa Terdaftar' }}</span>
                            </div>
                        </td>
                    </tr>
                </table>

                @foreach ($siswa->tandas as $tanda)
                    <div class="note-item">
                        <span class="note-content">{{ $tanda->keterangan }}</span>
                        <span class="note-date">
                            {{ \Carbon\Carbon::parse($tanda->created_at)->format('d/m/Y') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="no-notes">
                <p>Tidak ada catatan siswa ditemukan pada data jadwal ini.</p>
            </div>
        @endforelse
    </div>

</body>

</html>
