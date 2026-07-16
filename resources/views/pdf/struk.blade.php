<!DOCTYPE html>
<html>

<head>
    <title>Struk Pembayaran</title>
    <style>
        body {
            font-family: monospace;
            font-size: 10px;
            width: 100%;
            margin: 0;
            padding: 5px;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo-img {
            width: 60px;
            height: auto;
        }
    </style>
</head>

<body>
    <div class="logo-container">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/Logo.png'))) }}"
            class="logo-img">
    </div>

    <div class="text-center bold">
        E-LING COURSE BIMBEL<br>
        NOTA PELUNASAN RESMI
    </div>
    <div class="line"></div>
    <table>
        <tr>
            <td>No HP:</td>
            <td class="text-right">{{ $no_hp }}</td>
        </tr>
        <tr>
            <td>Siswa:</td>
            <td class="text-right font-bold">
                {{ $pembayarans->map(fn($p) => $p->siswa->name ?? '')->unique()->implode(', ') }}</td>
        </tr>
        <tr>
            <td>Tanggal:</td>
            <td class="text-right">{{ now()->translatedFormat('d M Y H:i') }}</td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="bold">RINCIAN ITEM TAGIHAN:</div>
    <table>
        @foreach ($pembayarans as $p)
            <tr>
                <td>{{ $p->keterangan }}</td>
                <td class="text-right">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
    <div class="line"></div>
    @php
        $totalKotor = $pembayarans->sum('harga');
        $totalAkhir = $totalKotor - $nominalDiskon;
        if ($totalAkhir < 0) {
            $totalAkhir = 0;
        }
    @endphp
    <table>
        <tr>
            <td>Total Tagihan:</td>
            <td class="text-right">Rp {{ number_format($totalKotor, 0, ',', '.') }}</td>
        </tr>
        @if ($nominalDiskon > 0)
            <tr>
                <td>Diskon ({{ $diskon->keterangan }}):</td>
                <td class="text-right">-Rp {{ number_format($nominalDiskon, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="bold">
            <td>TOTAL BERSIH:</td>
            <td class="text-right">Rp {{ number_format($totalAkhir, 0, ',', '.') }}</td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="text-center bold" style="font-size: 11px; margin-top: 5px;">
        STATUS: LUNAS<br>
        TERIMA KASIH
    </div>
</body>

</html>
