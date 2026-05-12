<!DOCTYPE html>
<html>

<head>
    <title>Laporan Pembayaran</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        .page-break {
            page-break-after: always;
        }

        .page-break:last-child {
            page-break-after: never;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        .status-0 {
            background-color: #EF4444;
            color: white;
        }

        .status-1 {
            background-color: #F97316;
            color: white;
        }

        .status-2 {
            background-color: #10B981;
            color: white;
        }

        .text-left {
            text-align: left;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    @foreach ($allData as $statusName => $data)
        <div class="page-break">
            <div class="header">
                <h2>LAPORAN PEMBAYARAN - {{ strtoupper($statusName) }}</h2>
                <p>PERIODE:
                    {{ $bulan == 'all' ? 'SEMUA BULAN' : strtoupper(\Carbon\Carbon::create()->month($bulan)->translatedFormat('F')) }}
                </p>
            </div>

            <table>
                <thead>
                    <tr class="status-{{ $data['code'] }}">
                        <th>NAMA SISWA</th>
                        <th>KETERANGAN</th>
                        <th>NOMINAL</th>
                        <th>METODE</th>
                        <th>TANGGAL</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($data['groups']->isEmpty())
                        <tr>
                            <td colspan="5">Tidak ada data untuk status ini</td>
                        </tr>
                    @else
                        @foreach ($data['groups'] as $idSiswa => $pembayarans)
                            @foreach ($pembayarans as $index => $item)
                                <tr>
                                    @if ($index === 0)
                                        <td rowspan="{{ $pembayarans->count() }}" class="font-bold">
                                            {{ $item->siswa->name ?? 'N/A' }}
                                        </td>
                                    @endif
                                    <td class="text-left">{{ $item->keterangan ?? '-' }}</td>
                                    <td>Rp {{ number_format($item->harga ?? 0, 0, ',', '.') }}</td>
                                    <td>
                                        {{ $item->pembayaran_via === 1 ? 'Transfer' : ($item->pembayaran_via === 0 ? 'Cash' : '-') }}
                                    </td>
                                    <td>{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
