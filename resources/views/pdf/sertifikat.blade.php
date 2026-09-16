<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $judul }} - {{ $rapor['siswa']['nama'] }}</title>
    <style>
        @page { margin: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            color: #14303f;
        }

        .lembar {
            position: relative;
            width: 297mm;
            height: 210mm;
        }

        .pita-atas {
            height: 14mm;
            background-color: #0f766e;
        }

        .pita-atas-tipis {
            height: 3mm;
            background-color: #f59e0b;
        }

        .pita-bawah {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 14mm;
            background-color: #0f766e;
        }

        .pita-bawah-tipis {
            position: absolute;
            bottom: 14mm;
            left: 0;
            right: 0;
            height: 3mm;
            background-color: #f59e0b;
        }

        .bingkai {
            position: absolute;
            top: 22mm;
            left: 16mm;
            right: 16mm;
            bottom: 25mm;
            border: 2px solid #0f766e;
        }

        .bingkai-dalam {
            position: absolute;
            top: 24mm;
            left: 18mm;
            right: 18mm;
            bottom: 27mm;
            border: 1px solid #f59e0b;
        }

        .isi {
            position: absolute;
            top: 30mm;
            left: 28mm;
            right: 28mm;
            text-align: center;
        }

        .lembaga {
            font-size: 12px;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: #0f766e;
        }

        .judul {
            font-size: 38px;
            font-weight: bold;
            color: #0f766e;
            margin-top: 4mm;
            letter-spacing: 1px;
        }

        .garis-hias {
            width: 60mm;
            height: 2px;
            background-color: #f59e0b;
            margin: 5mm auto 0 auto;
        }

        .pengantar {
            margin-top: 7mm;
            font-size: 12px;
            color: #4b5563;
        }

        .nama {
            margin-top: 3mm;
            font-size: 32px;
            font-weight: bold;
            color: #14303f;
            border-bottom: 1px solid #cbd5e1;
            display: inline-block;
            padding: 0 12mm 2mm 12mm;
        }

        .keterangan {
            margin-top: 6mm;
            font-size: 12px;
            color: #374151;
            line-height: 1.7;
        }

        .kotak-nilai {
            margin-top: 7mm;
        }

        .kotak-nilai table {
            margin: 0 auto;
            border-collapse: collapse;
        }

        .kotak-nilai td {
            padding: 0 9mm;
            text-align: center;
        }

        .angka {
            font-size: 26px;
            font-weight: bold;
            color: #0f766e;
        }

        .angka-label {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .aspek-unggul {
            margin-top: 6mm;
            font-size: 11px;
            color: #374151;
        }

        .aspek-unggul strong {
            color: #0f766e;
        }

        .kaki {
            position: absolute;
            bottom: 30mm;
            left: 30mm;
            right: 30mm;
        }

        .kaki table {
            width: 100%;
        }

        .kaki td {
            font-size: 10px;
            color: #4b5563;
            vertical-align: bottom;
        }

        .ttd-garis {
            border-top: 1px solid #14303f;
            padding-top: 2mm;
            width: 60mm;
        }

        .stempel {
            width: 26mm;
            height: 26mm;
            border: 2px solid #f59e0b;
            border-radius: 13mm;
            color: #b45309;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            padding-top: 8mm;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    @php
        $persen = $rapor['ringkasan']['persen'];
        $unggul = collect($rapor['per_aspek'])->sortByDesc('persen')->take(3);
    @endphp

    <div class="lembar">
        <div class="pita-atas"></div>
        <div class="pita-atas-tipis"></div>

        <div class="bingkai"></div>
        <div class="bingkai-dalam"></div>

        <div class="isi">
            <div class="lembaga">E-Ling Course</div>
            <div class="judul">{{ $judul }}</div>
            <div class="garis-hias"></div>

            <div class="pengantar">Diberikan dengan bangga kepada</div>
            <div class="nama">{{ $rapor['siswa']['nama'] }}</div>

            <div class="keterangan">
                atas penyelesaian <strong>{{ $rapor['ringkasan']['total_pertemuan'] }}</strong> pertemuan
                @if ($rapor['siswa']['kelas'])
                    pada kelas <strong>{{ $rapor['siswa']['kelas'] }}</strong>
                @endif
                periode <strong>{{ $rapor['periode']['label'] }}</strong>
                @if ($rapor['siswa']['kemampuan'])
                    <br>{{ $rapor['siswa']['kemampuan'] }}
                @endif
            </div>

            <div class="kotak-nilai">
                <table>
                    <tr>
                        <td>
                            <div class="angka">{{ $persen !== null ? $persen.'%' : '-' }}</div>
                            <div class="angka-label">Nilai Keseluruhan</div>
                        </td>
                        <td>
                            <div class="angka">{{ $rapor['ringkasan']['predikat'] }}</div>
                            <div class="angka-label">Predikat</div>
                        </td>
                        <td>
                            <div class="angka">{{ $rapor['ringkasan']['persen_kehadiran'] }}%</div>
                            <div class="angka-label">Kehadiran</div>
                        </td>
                    </tr>
                </table>
            </div>

            @if ($unggul->isNotEmpty())
                <div class="aspek-unggul">
                    Aspek terbaik:
                    @foreach ($unggul as $aspek)
                        <strong>{{ $aspek['nama'] }}</strong> ({{ $aspek['persen'] }}%){{ ! $loop->last ? ' · ' : '' }}
                    @endforeach
                </div>
            @endif
        </div>

        <div class="kaki">
            <table>
                <tr>
                    <td style="width: 38%;">
                        <div class="ttd-garis">{{ $rapor['guru'] }}<br><span style="font-size: 9px;">Teacher</span></div>
                    </td>
                    <td style="width: 24%; text-align: center;">
                        <div class="stempel">E-LING<br>COURSE</div>
                    </td>
                    <td style="width: 38%; text-align: right;">
                        <div style="font-size: 9px;">Diterbitkan {{ $dicetakPada }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="pita-bawah-tipis"></div>
        <div class="pita-bawah"></div>
    </div>
</body>

</html>
