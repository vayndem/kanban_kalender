<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Data Siswa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
            background: #fff;
        }

        .page-header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .page-header h1 {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 0.5px;
        }

        .page-header .meta {
            font-size: 8px;
            color: #6b7280;
            margin-top: 3px;
        }

        .filter-label {
            display: inline-block;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 8px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 6px;
        }

        .summary {
            font-size: 8px;
            color: #6b7280;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        thead tr {
            background: #1e3a8a;
            color: #fff;
        }

        thead th {
            padding: 6px 8px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:nth-child(odd) {
            background: #ffffff;
        }

        tbody td {
            padding: 6px 8px;
            vertical-align: top;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }

        .no-col {
            width: 4%;
            color: #9ca3af;
        }

        .nama-col {
            width: 18%;
        }

        .kelas-col {
            width: 8%;
        }

        .hp-col {
            width: 13%;
            color: #374151;
        }

        .paket-col {
            width: 12%;
        }

        .jadwal-col {
            width: 45%;
        }

        .nama-main {
            font-weight: bold;
            color: #111827;
            font-size: 9px;
        }

        .nama-panggilan {
            color: #6b7280;
            font-size: 8px;
        }

        .paket-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 8px;
            font-weight: bold;
        }

        .jadwal-item {
            margin-bottom: 4px;
            padding-bottom: 4px;
            border-bottom: 1px dashed #e5e7eb;
        }

        .jadwal-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .jadwal-mapel {
            font-weight: bold;
            color: #111827;
            font-size: 9px;
        }

        .jadwal-detail {
            color: #6b7280;
            font-size: 8px;
            margin-top: 1px;
        }

        .empty-jadwal {
            color: #d1d5db;
            font-style: italic;
            font-size: 8px;
        }

        .page-footer {
            margin-top: 16px;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            font-size: 7px;
            color: #9ca3af;
            display: flex;
            justify-content: space-between;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>

<body>

    <div class="page-header">
        <h1>Data Master Siswa</h1>
        <div class="meta">Diekspor pada: {{ $exportedAt }}</div>
        <div class="filter-label">{{ $filterLabel }}</div>
        <div class="summary">Total: {{ $siswas->count() }} siswa</div>
    </div>

    @if ($siswas->isEmpty())
        <div class="no-data">Tidak ada data siswa yang sesuai dengan filter yang dipilih.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th class="no-col">No</th>
                    <th class="nama-col">Nama</th>
                    <th class="kelas-col">Kelas</th>
                    <th class="hp-col">No. HP</th>
                    <th class="paket-col">Paket</th>
                    <th class="jadwal-col">Jadwal Kelas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($siswas as $i => $siswa)
                    <tr>
                        <td class="no-col">{{ $i + 1 }}</td>

                        <td class="nama-col">
                            <div class="nama-main">{{ $siswa->name }}</div>
                            @if ($siswa->panggilan)
                                <div class="nama-panggilan">({{ $siswa->panggilan }})</div>
                            @endif
                        </td>

                        <td class="kelas-col">{{ $siswa->kelas ?: '-' }}</td>

                        <td class="hp-col">{{ $siswa->no_hp ?: '-' }}</td>

                        <td class="paket-col">
                            @if ($siswa->paket)
                                <span class="paket-badge">{{ $siswa->paket->nama_paket }}</span>
                                @if ($siswa->paket->pertemuan)
                                    <div style="color:#6b7280;font-size:7px;margin-top:2px;">
                                        {{ $siswa->paket->pertemuan }}x/periode
                                    </div>
                                @endif
                            @else
                                <span style="color:#d1d5db;">-</span>
                            @endif
                        </td>

                        <td class="jadwal-col">
                            @forelse($siswa->jadwals as $jadwal)
                                <div class="jadwal-item">
                                    <div class="jadwal-mapel">
                                        {{ $jadwal->mataPelajaran?->name ?? 'N/A' }}
                                    </div>
                                    <div class="jadwal-detail">
                                        {{ ucfirst($jadwal->hari?->name ?? ($jadwal->hari?->nama ?? '-')) }}
                                        •
                                        {{ $jadwal->sesi?->name ?? ($jadwal->sesi?->nama_sesi ?? '-') }}
                                        @if ($jadwal->sesi?->start_time && $jadwal->sesi?->end_time)
                                            ({{ \Carbon\Carbon::parse($jadwal->sesi->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($jadwal->sesi->end_time)->format('H:i') }})
                                        @endif
                                        • {{ $jadwal->guru?->name ?? '-' }}
                                        • {{ $jadwal->ruang?->name ?? '-' }}
                                    </div>
                                </div>
                            @empty
                                <span class="empty-jadwal">Belum ada jadwal</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-footer">
        <span>Sistem Manajemen Kelas — PT. Muliaoffset Packindo</span>
        <span>{{ $exportedAt }}</span>
    </div>

</body>

</html>
