<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Rapor Perkembangan Siswa</title>
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

        .ringkasan {
            margin: 12px 0;
        }

        .ringkasan td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            text-align: center;
            width: 25%;
        }

        .ringkasan .angka {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }

        .ringkasan .label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            margin-top: 2px;
        }

        .tren-naik {
            color: #059669;
            font-weight: bold;
        }

        .tren-turun {
            color: #dc2626;
            font-weight: bold;
        }

        .tren-stabil {
            color: #2563eb;
            font-weight: bold;
        }

        .mapel-block {
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .mapel-header {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 5px 8px;
        }

        .mapel-nama {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .mapel-meta {
            font-size: 8px;
            color: #475569;
            margin-top: 1px;
        }

        .pertemuan th {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            font-size: 8px;
            text-transform: uppercase;
            color: #475569;
            text-align: left;
        }

        .pertemuan td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            font-size: 9px;
        }

        .col-tanggal {
            width: 14%;
        }

        .col-hadir {
            width: 10%;
            text-align: center;
        }

        .col-nilai {
            width: 8%;
            text-align: center;
        }

        .col-guru {
            width: 20%;
        }

        .badge-hadir {
            color: #059669;
            font-weight: bold;
        }

        .badge-absen {
            color: #dc2626;
            font-weight: bold;
        }

        .sub-materi {
            color: #6b7280;
            font-size: 8px;
        }

        .no-data {
            border: 1px dashed #cbd5e1;
            padding: 20px;
            text-align: center;
            color: #94a3b8;
            font-style: italic;
            margin-top: 12px;
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
            <div class="brand-kicker">Laporan Perkembangan</div>
            <div class="brand-name">E-Ling Course</div>
            <div class="brand-subtitle">Rekap kehadiran dan nilai per pertemuan yang sudah diajarkan.</div>
        </div>
    </div>

    <h1>Rapor Perkembangan Siswa</h1>
    <div class="meta">Dicetak pada: {{ $dicetakPada }}</div>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama</td>
            <td>
                <strong>{{ $rapor['siswa']['nama'] }}</strong>
                @if ($rapor['siswa']['panggilan'])
                    ({{ $rapor['siswa']['panggilan'] }})
                @endif
            </td>
            <td class="info-label">Kelas</td>
            <td>{{ $rapor['siswa']['kelas'] ?: '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Kemampuan</td>
            <td>{{ $rapor['siswa']['kemampuan'] ?: 'Belum ditentukan' }}</td>
            <td class="info-label">Periode</td>
            <td>
                @if ($rapor['periode']['dari'] || $rapor['periode']['sampai'])
                    {{ $rapor['periode']['dari'] ?: 'awal' }} s/d {{ $rapor['periode']['sampai'] ?: 'sekarang' }}
                @else
                    Seluruh riwayat
                @endif
            </td>
        </tr>
    </table>

    <table class="ringkasan">
        <tr>
            <td>
                <div class="angka">{{ $rapor['ringkasan']['total_pertemuan'] }}</div>
                <div class="label">Pertemuan</div>
            </td>
            <td>
                <div class="angka">{{ $rapor['ringkasan']['persen_kehadiran'] }}%</div>
                <div class="label">Kehadiran</div>
            </td>
            <td>
                <div class="angka">{{ $rapor['ringkasan']['rata_nilai'] ?? '-' }}</div>
                <div class="label">Rata-rata Nilai</div>
            </td>
            <td>
                <div class="angka">
                    @if ($rapor['ringkasan']['tren'] === 'naik')
                        <span class="tren-naik">Naik</span>
                    @elseif ($rapor['ringkasan']['tren'] === 'turun')
                        <span class="tren-turun">Turun</span>
                    @elseif ($rapor['ringkasan']['tren'] === 'stabil')
                        <span class="tren-stabil">Stabil</span>
                    @else
                        -
                    @endif
                </div>
                <div class="label">Tren Nilai</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="angka">{{ $rapor['ringkasan']['hadir'] }}</div>
                <div class="label">Hadir</div>
            </td>
            <td>
                <div class="angka">{{ $rapor['ringkasan']['tidak_hadir'] }}</div>
                <div class="label">Tidak Hadir</div>
            </td>
            <td>
                <div class="angka">{{ $rapor['ringkasan']['nilai_terendah'] ?? '-' }}</div>
                <div class="label">Nilai Terendah</div>
            </td>
            <td>
                <div class="angka">{{ $rapor['ringkasan']['nilai_tertinggi'] ?? '-' }}</div>
                <div class="label">Nilai Tertinggi</div>
            </td>
        </tr>
    </table>

    @if (empty($rapor['per_mapel']))
        <div class="no-data">
            Belum ada pertemuan yang tercatat untuk siswa ini pada periode tersebut.
        </div>
    @else
        @foreach ($rapor['per_mapel'] as $mapel)
            <div class="mapel-block">
                <div class="mapel-header">
                    <div class="mapel-nama">{{ $mapel['mapel'] }}</div>
                    <div class="mapel-meta">
                        Guru kelas: {{ $mapel['guru'] }} &middot;
                        {{ $mapel['hadir'] }} dari {{ $mapel['jumlah_pertemuan'] }} pertemuan hadir &middot;
                        Rata-rata nilai: {{ $mapel['rata_nilai'] ?? '-' }}
                    </div>
                </div>
                <table class="pertemuan">
                    <thead>
                        <tr>
                            <th class="col-tanggal">Tanggal</th>
                            <th>Materi</th>
                            <th class="col-hadir">Hadir</th>
                            <th class="col-nilai">Nilai</th>
                            <th class="col-guru">Diajar Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mapel['pertemuan'] as $p)
                            <tr>
                                <td class="col-tanggal">{{ \Carbon\Carbon::parse($p['tanggal'])->translatedFormat('d M Y') }}</td>
                                <td>
                                    {{ $p['materi'] }}
                                    @if ($p['sub_materi'])
                                        <div class="sub-materi">{{ $p['sub_materi'] }}</div>
                                    @endif
                                </td>
                                <td class="col-hadir">
                                    @if ($p['hadir'])
                                        <span class="badge-hadir">Ya</span>
                                    @else
                                        <span class="badge-absen">Tidak</span>
                                    @endif
                                </td>
                                <td class="col-nilai">{{ $p['nilai'] ?? '-' }}</td>
                                <td class="col-guru">{{ $p['diajar_oleh'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    <div class="footer-note">
        Nilai memakai skala 1-5. Tren dihitung dengan membandingkan rata-rata nilai paruh awal dan paruh akhir periode,
        dan baru muncul setelah minimal 4 pertemuan dinilai.
    </div>

</body>

</html>
