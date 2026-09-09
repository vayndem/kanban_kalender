<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Struk Penggajian Guru</title>
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

        .brand-header {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .brand-strip {
            height: 8px;
            background: #1d4ed8;
            border-bottom: 3px solid #f97316;
        }

        .brand-body {
            padding: 8px 12px;
        }

        .brand-kicker {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #f97316;
            margin-bottom: 2px;
        }

        .brand-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }

        .brand-subtitle {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        h1 {
            font-size: 14px;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .meta {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .info-label {
            width: 22%;
            background: #f8fafc;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }

        .dibatalkan {
            margin: 10px 0;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
            padding: 6px 8px;
            font-size: 9px;
            font-weight: bold;
        }

        .rincian {
            margin: 12px 0;
        }

        .rincian th {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            font-size: 8px;
            text-transform: uppercase;
            color: #475569;
            text-align: left;
        }

        .rincian td {
            border: 1px solid #e5e7eb;
            padding: 5px 8px;
        }

        .angka {
            text-align: right;
            white-space: nowrap;
        }

        .baris-total td {
            background: #eff6ff;
            border-color: #bfdbfe;
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .sub-judul {
            margin-top: 14px;
            margin-bottom: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
        }

        .log th {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            text-align: left;
        }

        .log td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            font-size: 9px;
        }

        .col-no {
            width: 8%;
            text-align: center;
        }

        .col-tanggal {
            width: 20%;
        }

        .no-data {
            border: 1px dashed #cbd5e1;
            padding: 14px;
            text-align: center;
            color: #94a3b8;
            font-style: italic;
        }

        .ttd {
            margin-top: 26px;
            width: 100%;
        }

        .ttd td {
            width: 50%;
            font-size: 9px;
            color: #475569;
            vertical-align: top;
        }

        .ttd .ruang {
            height: 46px;
        }

        .ttd .garis {
            border-top: 1px solid #94a3b8;
            width: 62%;
            padding-top: 3px;
        }

        .footer-note {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>

<body>

    <div class="brand-header">
        <div class="brand-strip"></div>
        <div class="brand-body">
            <div class="brand-kicker">Bukti Penggajian</div>
            <div class="brand-name">E-Ling Course</div>
            <div class="brand-subtitle">Gaji bawaan ditambah gaji per kehadiran yang belum pernah digaji.</div>
        </div>
    </div>

    <h1>Struk Penggajian Guru</h1>
    <div class="meta">Dicetak pada: {{ $dicetakPada }}</div>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama Guru</td>
            <td><strong>{{ $struk->guru?->name ?? '-' }}</strong></td>
            <td class="info-label">No. Struk</td>
            <td>#{{ $struk->id }}</td>
        </tr>
        <tr>
            <td class="info-label">Dijalankan</td>
            <td>{{ $struk->dijalankan_pada?->translatedFormat('d F Y, H:i') ?? '-' }}</td>
            <td class="info-label">Oleh</td>
            <td>{{ $struk->dijalankanOleh?->name ?? '-' }}</td>
        </tr>
    </table>

    @if ($struk->dibatalkan_pada)
        <div class="dibatalkan">
            STRUK INI DIBATALKAN pada {{ $struk->dibatalkan_pada->translatedFormat('d F Y, H:i') }}
            @if ($struk->dibatalkanOleh)
                oleh {{ $struk->dibatalkanOleh->name }}
            @endif
            @if ($struk->alasan_batal)
                &mdash; {{ $struk->alasan_batal }}
            @endif
        </div>
    @endif

    <table class="rincian">
        <thead>
            <tr>
                <th>Komponen</th>
                <th style="width: 18%; text-align: center;">Jumlah</th>
                <th style="width: 22%; text-align: right;">Tarif</th>
                <th style="width: 25%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji bawaan</td>
                <td style="text-align: center;">1</td>
                <td class="angka">Rp {{ number_format($struk->gaji_bawaan, 0, ',', '.') }}</td>
                <td class="angka">Rp {{ number_format($struk->gaji_bawaan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Gaji per kehadiran</td>
                <td style="text-align: center;">{{ $struk->jumlah_kehadiran }}</td>
                <td class="angka">Rp {{ number_format($struk->gaji_per_kehadiran, 0, ',', '.') }}</td>
                <td class="angka">Rp {{ number_format($struk->jumlah_kehadiran * $struk->gaji_per_kehadiran, 0, ',', '.') }}</td>
            </tr>
            <tr class="baris-total">
                <td colspan="3">TOTAL DIBAYAR</td>
                <td class="angka">Rp {{ number_format($struk->total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="sub-judul">Log Kelas Yang Diajar ({{ count($logKelas) }})</div>

    @if (count($logKelas) === 0)
        <div class="no-data">Tidak ada kehadiran pada struk ini &mdash; hanya gaji bawaan.</div>
    @else
        <table class="log">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-tanggal">Tanggal</th>
                    <th>Materi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logKelas as $i => $item)
                    <tr>
                        <td class="col-no">{{ $i + 1 }}</td>
                        <td class="col-tanggal">
                            {{ $item['tanggal'] ? \Carbon\Carbon::parse($item['tanggal'])->translatedFormat('d M Y') : '-' }}
                        </td>
                        <td>{{ $item['materi'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="ttd">
        <tr>
            <td>Diserahkan oleh,<div class="ruang"></div>
                <div class="garis">Admin E-Ling Course</div>
            </td>
            <td>Diterima oleh,<div class="ruang"></div>
                <div class="garis">{{ $struk->guru?->name ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Tarif pada struk ini dibekukan saat struk terbit, sehingga perubahan gaji di kemudian hari tidak mengubah
        angka di atas. Koreksi dilakukan dengan membatalkan struk lalu menerbitkan yang baru.
    </div>

</body>

</html>
