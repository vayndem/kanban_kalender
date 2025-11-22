<!DOCTYPE html>
<html>

<head>
    <title>Jadwal Pelajaran</title>
    <style>
        /* Setting Kertas A4 Landscape */
        @page {
            size: a4 landscape;
            margin: 10mm;
        }

        body {
            font-family: sans-serif;
            font-size: 10pt;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Agar lebar kolom stabil & rata */
        }

        th,
        td {
            border: 1px solid #444;
            /* Border tabel lebih tegas */
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
            height: 30px;
        }

        /* Kolom Sesi (Kiri) */
        .sesi-col {
            width: 70px;
            text-align: center;
            background-color: #f9f9f9;
            font-weight: bold;
        }

        /* Style Kartu Kelas dalam Cell */
        .card {
            border: 1px solid #ccc;
            padding: 4px;
            margin-bottom: 5px;
            border-radius: 3px;
            background-color: #fff;
            page-break-inside: avoid;
            /* Mencegah kartu terpotong halaman */
        }

        .mapel {
            font-weight: bold;
            font-size: 9pt;
            color: #000;
            margin-bottom: 2px;
        }

        .guru {
            font-size: 8pt;
            color: #333;
            display: block;
        }

        .ruang {
            font-size: 8pt;
            color: #555;
            display: block;
            margin-bottom: 2px;
        }

        .siswa-list {
            margin-top: 2px;
            font-size: 7pt;
            padding-left: 12px;
            color: #444;
        }

        .siswa-item {
            margin-bottom: 1px;
        }

        /* Warna teks kuning gelap/oranye untuk siswa bertanda (agar terbaca di kertas putih) */
        .tanda-text {
            color: #d97706;
            /* Kode warna Oranye/Kuning Gelap */
            font-weight: bold;
            text-decoration: underline;
            /* Garis bawah agar lebih jelas di cetakan hitam putih */
        }
    </style>
</head>

<body>

    <h2>Jadwal Pelajaran</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Sesi</th>
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
                        <span style="font-size: 8pt; font-weight: normal;">
                            {{ \Carbon\Carbon::parse($sesi->start_time)->format('H:i') }}<br>
                            s/d<br>
                            {{ \Carbon\Carbon::parse($sesi->end_time)->format('H:i') }}
                        </span>
                    </td>

                    @foreach ($haris as $hari)
                        <td>
                            @if (isset($jadwals[$hari->id][$sesi->id]))
                                @foreach ($jadwals[$hari->id][$sesi->id] as $groupedClass)
                                    {{-- Border kiri berwarna sesuai mapel --}}
                                    <div class="card"
                                        style="border-left: 4px solid {{ $groupedClass['mapel']->border_color ?? '#000' }};">

                                        <div class="mapel">{{ $groupedClass['mapel']->name }}</div>
                                        <span class="guru">{{ $groupedClass['guru']->name }}</span>
                                        <span class="ruang">R: {{ $groupedClass['ruang']->name }}</span>

                                        <ol class="siswa-list">
                                            @foreach ($groupedClass['siswa_list'] as $siswa)
                                                @php
                                                    // Cek apakah siswa punya tanda
                                                    $hasTanda = $siswa->tandas && $siswa->tandas->count() > 0;
                                                @endphp
                                                <li class="siswa-item {{ $hasTanda ? 'tanda-text' : '' }}">
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

</body>

</html>
