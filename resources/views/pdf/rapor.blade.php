<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Progress Monthly Report - {{ $rapor['siswa']['nama'] }}</title>
    <style>
        @page { margin: 22mm 18mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #1f2937;
            line-height: 1.45;
        }

        .judul-atas {
            text-align: center;
            margin-bottom: 18px;
        }

        .judul-kecil {
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #2563eb;
        }

        .judul-besar {
            font-size: 24px;
            font-weight: bold;
            color: #1d4ed8;
            margin-top: 2px;
        }

        .judul-garis {
            height: 3px;
            background-color: #1d4ed8;
            width: 90px;
            margin: 8px auto 0 auto;
        }

        .bagian {
            margin-top: 16px;
        }

        .bagian-judul {
            font-weight: bold;
            font-size: 11.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .identitas td {
            padding: 2px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .identitas .label { width: 110px; }
        .identitas .pemisah { width: 12px; }

        .tabel-nilai th,
        .tabel-nilai td {
            border: 1px solid #111827;
            padding: 5px 7px;
            vertical-align: top;
        }

        .tabel-nilai th {
            background-color: #eff6ff;
            font-size: 10.5px;
            text-align: center;
        }

        .kol-aspek { width: 27%; }
        .kol-indikator { width: 44%; }
        .kol-skor { width: 29%; text-align: center; }

        .persen {
            font-weight: bold;
            font-size: 13px;
        }

        .predikat {
            font-size: 9px;
            color: #4b5563;
        }

        .bar-luar {
            width: 100%;
            height: 7px;
            background-color: #e5e7eb;
            margin-top: 3px;
        }

        .bar-dalam {
            height: 7px;
            background-color: #2563eb;
        }

        .panduan {
            margin-top: 8px;
            font-size: 9.5px;
        }

        .panduan td {
            padding: 2px 10px 2px 0;
        }

        .kotak-isian {
            border: 1px solid #9ca3af;
            min-height: 52px;
            padding: 6px 8px;
            font-size: 10.5px;
            white-space: pre-line;
        }

        .garis-titik {
            border-bottom: 1px dotted #6b7280;
            height: 15px;
        }

        .tabel-materi th,
        .tabel-materi td {
            border: 1px solid #111827;
            padding: 5px 7px;
        }

        .tabel-materi th {
            background-color: #eff6ff;
            text-align: center;
        }

        .ttd {
            margin-top: 26px;
            text-align: right;
            font-size: 10.5px;
        }

        .ttd-garis {
            display: inline-block;
            border-top: 1px solid #111827;
            width: 170px;
            margin-top: 46px;
            padding-top: 3px;
        }

        .kaki {
            margin-top: 14px;
            font-size: 8.5px;
            color: #6b7280;
            text-align: center;
        }

        .ringkas {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 7px 9px;
            margin-top: 10px;
            font-size: 10.5px;
        }
    </style>
</head>

<body>
    <div class="judul-atas">
        <div class="judul-kecil">Progress Monthly Report</div>
        <div class="judul-besar">E-Ling Course</div>
        <div class="judul-garis"></div>
    </div>

    <div class="bagian-judul">Student Information</div>
    <table class="identitas">
        <tr>
            <td class="label">Student Name</td>
            <td class="pemisah">:</td>
            <td><strong>{{ $rapor['siswa']['nama'] }}</strong></td>
        </tr>
        <tr>
            <td class="label">Class</td>
            <td class="pemisah">:</td>
            <td>{{ $rapor['siswa']['kelas'] ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Level</td>
            <td class="pemisah">:</td>
            <td>{{ $rapor['siswa']['kemampuan'] ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Month</td>
            <td class="pemisah">:</td>
            <td>{{ $rapor['periode']['label'] }}</td>
        </tr>
        <tr>
            <td class="label">Teacher</td>
            <td class="pemisah">:</td>
            <td>{{ $rapor['guru'] }}</td>
        </tr>
    </table>

    <div class="ringkas">
        Dihitung dari <strong>{{ $rapor['ringkasan']['total_pertemuan'] }}</strong> pertemuan terpilih &middot;
        hadir <strong>{{ $rapor['ringkasan']['hadir'] }}</strong>
        ({{ $rapor['ringkasan']['persen_kehadiran'] }}%) &middot;
        nilai keseluruhan
        <strong>{{ $rapor['ringkasan']['persen'] !== null ? $rapor['ringkasan']['persen'].'%' : '-' }}</strong>
        ({{ $rapor['ringkasan']['predikat'] }})
    </div>

    <div class="bagian">
        <div class="bagian-judul">A. Monthly Learning Progress</div>
        @if (empty($rapor['per_aspek']))
            <div class="kotak-isian">Belum ada aspek yang dinilai pada pertemuan yang dipilih.</div>
        @else
            <table class="tabel-nilai">
                <thead>
                    <tr>
                        <th class="kol-aspek">Assessment Aspect</th>
                        <th class="kol-indikator">Indicator</th>
                        <th class="kol-skor">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rapor['per_aspek'] as $aspek)
                        <tr>
                            <td>{{ $aspek['nama'] }}</td>
                            <td>{{ $aspek['indikator'] }}</td>
                            <td class="kol-skor">
                                <span class="persen">{{ $aspek['persen'] }}%</span><br>
                                <span class="predikat">{{ $aspek['predikat'] }}</span>
                                <div class="bar-luar">
                                    <div class="bar-dalam" style="width: {{ $aspek['persen'] }}%;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <table class="panduan">
            <tr>
                <td><strong>Score Guide:</strong></td>
                <td>0-20% Needs Support</td>
                <td>21-40% Beginning</td>
                <td>41-60% Good Progress</td>
                <td>61-80% Very Good</td>
                <td>81-100% Excellent</td>
            </tr>
        </table>
    </div>

    @foreach ([
        ['B. Student Strength', 'kekuatan'],
        ['C. Area to Improve', 'perbaikan'],
        ['D. Teacher\'s Comment', 'komentar'],
        ['E. Plan Activities for Next Month', 'rencana'],
    ] as [$judulBagian, $kunci])
        <div class="bagian">
            <div class="bagian-judul">{{ $judulBagian }}</div>
            @if (filled($catatan[$kunci] ?? null))
                <div class="kotak-isian">{{ $catatan[$kunci] }}</div>
            @else
                <div class="garis-titik"></div>
                <div class="garis-titik"></div>
                <div class="garis-titik"></div>
            @endif
        </div>
    @endforeach

    <div class="bagian">
        <div class="bagian-judul">Materials Learned This Month</div>
        <table class="tabel-materi">
            <thead>
                <tr>
                    <th style="width: 55%;">Topic</th>
                    <th>Achievement / Result</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rapor['materi'] as $materi)
                    <tr>
                        <td>{{ $materi['topik'] }}</td>
                        <td>{{ $materi['hasil'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="ttd">
        <div class="ttd-garis">{{ $rapor['guru'] }}</div>
        <div style="font-size: 9px; color: #6b7280;">Teacher Signature</div>
    </div>

    <div class="kaki">Dicetak {{ $dicetakPada }} &middot; E-Ling Course</div>
</body>

</html>
