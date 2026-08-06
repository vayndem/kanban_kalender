<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Jadwal & Catatan Operasional</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 10mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #333;
        }

        .brand-header {
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .brand-strip {
            height: 10px;
            background: #1d4ed8;
            border-bottom: 4px solid #f97316;
        }

        .brand-body {
            padding: 12px 14px 10px;
            text-align: left;
        }

        .brand-kicker {
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #f97316;
            margin-bottom: 3px;
        }

        .brand-name {
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
        }

        .brand-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: 8pt;
        }

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

        .export-info {
            font-size: 8pt;
            color: #64748b;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #666;
            padding: 3px;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #eee;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            height: 35px;
            vertical-align: middle;
        }

        .sesi-col {
            width: 60px;
            text-align: center;
            background-color: #f8f8f8;
            font-weight: bold;
            font-size: 8pt;
        }

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
            color: #000;
            margin-bottom: 1px;
        }

        .guru, .ruang {
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
            font-weight: bold;
            text-decoration: underline;
        }

        .page-break {
            page-break-before: always;
        }

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
            color: #1e40af;
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
            border-left: 4px solid #f59e0b;
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

    <div class="brand-header">
        <div class="brand-strip"></div>
        <div class="brand-body">
            <div class="brand-kicker">Brand Report</div>
            <div class="brand-name">E-Ling Course</div>
            <div class="brand-subtitle">Ringkasan jadwal pelajaran dan catatan operasional siswa.</div>
        </div>
    </div>

    <div class="header-container">
        <h2>Laporan Jadwal Pelajaran</h2>
        @if($searchQuery)
            <div class="search-info">Filter: "{{ $searchQuery }}"</div>
        @else
            <div class="search-info">Menampilkan seluruh jadwal aktif sesuai data sistem.</div>
        @endif
        <div class="export-info">Diekspor pada {{ now()->translatedFormat('d F Y, H:i') }}</div>
    </div>

    <table>
        <thead>
            @php
                $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
                $dayOffsets = [
                    'Senin'  => 0,
                    'Selasa' => 1,
                    'Rabu'   => 2,
                    'Kamis'  => 3,
                    'Jumat'  => 4,
                    'Sabtu'  => 5,
                ];
            @endphp

            <tr>
                <th style="width: 60px;">Waktu</th>
                @foreach ($haris as $hari)
                    <th>
                        {{ $hari->name }}
                        @php
                            $offset = $dayOffsets[$hari->name] ?? 0;
                            $date = $startOfWeek->copy()->addDays($offset);
                        @endphp

                        <div style="font-size: 7pt; font-weight: normal; margin-top: 2px; color: #555;">
                            {{ $date->translatedFormat('d F Y') }}
                        </div>
                    </th>
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
                                    <div class="card" style="border-left: 3px solid {{ $groupedClass['mapel']->border_color ?? '#000' }};">
                                        <div class="mapel">{{ $groupedClass['mapel']->name }}</div>
                                        <span class="guru">{{ $groupedClass['guru']->name }}</span>
                                        <span class="ruang">R: {{ $groupedClass['ruang']->name }}</span>

                                        <ol class="siswa-list">
                                            @foreach ($groupedClass['siswa_list'] as $siswa)
                                                <li class="{{ ($siswa->tandas && $siswa->tandas->count() > 0) ? 'tanda-indicator' : '' }}">
                                                    {{ $siswa->formatted_name_class }}
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

    <div class="page-break"></div>

    <div class="brand-header">
        <div class="brand-strip"></div>
        <div class="brand-body">
            <div class="brand-kicker">Brand Report</div>
            <div class="brand-name">E-Ling Course</div>
            <div class="brand-subtitle">Dokumen lanjutan untuk catatan operasional siswa.</div>
        </div>
    </div>

    <div class="header-container">
        <h2>Catatan Operasional Siswa</h2>
        <div class="search-info">Daftar siswa yang memiliki tanda atau catatan khusus pada sistem.</div>
        <div class="export-info">Diekspor pada {{ now()->translatedFormat('d F Y, H:i') }}</div>
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

                @foreach($siswa->tandas as $tanda)
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
